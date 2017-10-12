<?php
/**
 * Classe de connexion à un annuaire LDAP
 *
 * User: cedric.gallard
 * Date: 18/03/14
 * Time: 09:07
 *
 */

namespace Ldap;
use Logs\Alert;

/**
 * Connexion à un annuaire LDAP
 *
 * La connexion à l'annuaire LDAP est fermée à la fin de l'exécution du script.
 *
 * Bien que cette classe n'ait été testée qu'avec des annuaires Active Directory, elle devrait fonctionner avec des annuaires LDAP autres que ceux de Microsoft.
 *
 * <h4>Exemple</h4>
 * <code>
 * use \Ldap\Connection;
 * $LDAPConnection = new Connection('dc1', 'bob.morane', 'happyPwd', 'contoso.com');
 * </code>
 *
 * <h4>Usage</h4>
 * La connexion est stockée dans la propriété `connection` de l'objet, et est accessible via la méthode `connection()` :
 * <code>
 * $connection = $LDAPConnection->connection();
 * </code>
 *
 * Si la connexion a échoué, la propriété `badCreds` accessible par la méthode `badCreds()` prendra la valeur `true` :
 * <code>
 * use \Ldap\Connection;
 * // le mot de passe de bob.morane n'est pas `badPwd`
 * $LDAPConnection = new Connection('dc1', 'bob.morane', 'badPwd', 'contoso.com');
 * // Une alerte est générée pour l'utilisateur et $LDAPConnection->badCreds passe à `true`.
 * echo $LDAPConnection->badCreds; // renvoie true
 * </code>
 *
 * @package Ldap
 */
class Connection {

	/**
	 * Domain Controller
	 * @var string
	 */
	protected $dc = '';

	/**
	 * Nom du compte utilisé pour se connecter à l'annuaire LDAP
	 * @var string
	 */
	protected $bindName = '';

	/**
	 * Mot de passe du compte utilisé pour se connecter à l'annuaire LDAP
	 * @var string
	 */
	protected $bindPwd = '';

	/**
	 * Domaine LDAP
	 * @var string
	 */
	protected $domain = '';

	/**
	 * Port utilisé pour se connecter à l'annuaire LDAP
	 * @var int
	 */
	protected $port = 389;

	/**
	 * Connexion LDAP
	 * @var object
	 */
	protected $connection = null;

	/**
	 * Si true, les identifiants de connexion sont mauvais
	 * @var bool
	 */
	protected $badCreds = false;

	/**
	 * @var string Message d'erreur de connexion
	 */
	protected $errorMsg = null;

	/**
	 * Construction de l'objet
	 *
	 * @param string $dc Contrôleur de domaine sur lequel ouvrir la connexion
	 * @param string $bindName Nom du compte utilisé pour ouvrir la connexion sur l'annuaire LDAP
	 * @param string $bindPwd Mot de passe du compte utilisé pour ouvrir la connexion
	 * @param string $domain Domaine LDAP (facultatif)
	 */
	public function __construct($dc, $bindName, $bindPwd, $domain = ''){
		if (!empty($domain)){
			/**
			 * TODO faire une meilleure vérification du domaine LDAP
			 */
			$this->domain = htmlspecialchars($domain);
		}
		$this->bindName = htmlspecialchars($bindName);
		$this->bindPwd  = htmlspecialchars($bindPwd);
		$this->dc       = htmlspecialchars($dc);
		// on regarde si c'est une adresse IP ou un nom. si c'est un nom, on complète avec le nom de domaine
		if (\Check::isIpAddress($this->dc)){
			$this->connection = ldap_connect($this->dc, $this->port);
		}else{
			$this->connection = ldap_connect($this->dc.'.'.$this->domain, $this->port);
		}
		if ($this->connection){
			ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3); //Option à ajouter si vous utilisez Windows server2k3 minimum
			ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0); //Option à ajouter si vous utilisez Windows server2k3 minimum
			/** Connexion à l'AD avec les identifiants saisis à la connexion.. */
			$this->badCreds = false;
			$r = false;
			if (\Settings::LDAP_SECURE_BIND) {
				$retTls = @ldap_start_tls($this->connection);
				if (!$retTls) {
					$this->badCreds = true;
					new Alert('debug', 'Erreur : la connexion sécurisée à l\'annuaire LDAP a échoué.<br>');
					$this->errorMsg = 'La connexion à l\'annuaire LDAP en mode sécurisé a échoué !';
				} else {
					$r = @ldap_bind($this->connection, $this->bindName.'@'.$this->domain, $this->bindPwd);
				}
			} else {
				// Méfiance, si le mot de passe est vide, une connexion anonyme sera tentée et la connexion peut retourner true...
				$r = @ldap_bind($this->connection, $this->bindName.'@'.$this->domain, $this->bindPwd);
			}
			if (!$r) {
				// On récupère un code d'erreur plus parlant que celui qui est renvoyé normalement
				ldap_get_option($this->connection, LDAP_OPT_ERROR_STRING, $diagnosticMsg);
				if (preg_match('/data (\w{3}),/', $diagnosticMsg, $match)){
					// Afin d'éviter que des petits malins ne fassent des essais tordus, on ne renvoie pas les erreurs de type compte inexistant ou mauvais mot de passe pour ce compte mais un message plus générique.
					// D'un point de vue sécurité on ne devrait d'ailleurs pas renvoyer les autres messages puisqu'ils sont révélateurs de l'existence de comptes AD.
					$errorCodes = array(
						//'525' => 'Cet utilisateur n\'existe pas',
						//'52e' => 'Votre mot de passe est incorrect',
						'530' => 'Vous ne pouvez pas vous connecter à cette heure-ci',
						'531' => 'Vous ne pouvez pas vous connecter depuis ce serveur',
						'532' => 'Votre mot de passe est expiré',
						'533' => 'Votre compte a été désactivé',
						'701' => 'Votre compte a expiré',
						'773' => 'Votre mot de passe a expiré, vous devez le changer avant de pouvoir vous connecter',
						'775' => 'Votre compte est verrouillé',
					);
					$this->errorMsg = (isset($errorCodes[$match[1]])) ? $errorCodes[$match[1]] : null;
				}
				new Alert('debug', '<code>Connection constructor</code> : Impossible de se connecter au serveur LDAP <code>'.$this->dc.'</code> avec les identifiants saisis !');
				$this->badCreds = true;
			}
		}
	}

	/**
	 * On clos la connexion au serveur LDAP à la fin du script
	 */
	public function __destruct(){
		ldap_close($this->connection);
	}

	/**
	 * Retourne la connexion à l'annuaire LDAP
	 * @return object|resource
	 */
	public function connection(){
		return $this->connection;
	}

	/**
	 * Renvoie true si la connexion à échoué à cause de mauvais identifiants
	 * @return bool
	 */
	public function badCreds(){
		return $this->badCreds;
	}

	/**
	 * @return string
	 */
	public function getErrorMsg() {
		return $this->errorMsg;
	}

}

?>