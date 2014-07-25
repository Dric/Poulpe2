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
		if ($this->connection = ldap_connect($this->dc.'.'.$this->domain, $this->port)){
			ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3); //Option à ajouter si vous utilisez Windows server2k3
			ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0); //Option à ajouter si vous utilisez Windows server2k3
			/** Connexion à l'AD avec les identifiants saisis à la connexion.. */
			$this->badCreds = false;
			// Méfiance, si le mot de passe est vide, une connexion anonyme sera tentée et la connexion peut retourner true...
			$r = @ldap_bind($this->connection, $this->bindName.'@'.$this->domain, $this->bindPwd);
			if (!$r) {
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
}

?>