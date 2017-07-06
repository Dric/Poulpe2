<?php
/**
 * Classe de gestion des connexions et requêtes LDAP
 *
 * Les instances de connexions LDAP ne sont instanciées que si nécessaire, afin d'éviter des requêtes LDAP inutiles.
 *
 * User: cedric.gallard
 * Date: 18/03/14
 * Time: 10:25
 *
 */

namespace Ldap;

use Logs\Alert;
use Sanitize;

/**
 * Classe de gestion des connexions et requêtes LDAP
 *
 * Les instances de connexions LDAP ne sont instanciées que si nécessaire, afin d'éviter des requêtes LDAP inutiles.
 *
 * Les mêmes identifiants sont utilisés pour tous les serveurs ldap
 *
 * <h4>Exemple</h4>
 * <code>
 * use \Ldap\Ldap;
 * $ldapServers = array('dc1', 'dc2', 'dc3');
 * $ldap = new Ldap('contoso.com', 'bob.morane', 'happyPwd', $ldapServers);
 * </code>
 *
 * @package Ldap
 */
class Ldap {

	/**
	 * Domaine LDAP
	 * @var string
	 */
	protected $domain = '';

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
	 * Nom de domaine au format DC.
	 * Est créé automatiquement à partir de $domain
	 * @var string
	 */
	protected $domainDCName = '';

	/**
	 * Liste des connexions LDAP
	 * @var array
	 */
	protected $connections = array();

	/**
	 * Liste des serveurs LDAP
	 * @var array
	 */
	protected $ldapServers = array();

	/**
	 * Liste des groupes présents dans l'AD
	 * @var array
	 */
	protected $ldapGroups = array();

	/**
	 * Classe de gestion des requêtes LDAP
	 *
	 * @param string  $domain Nom DNS du domaine (facultatif)
	 * @param string  $bindName Nom du compte utilisé pour les connexions aux serveurs LDAP (facultatif)
	 * @param string  $bindPwd Mot de passe du compte utilisé pour les connexions LDAP (facultatif)
	 * @param array   $ldapServers Liste des serveurs ldap sur lesquels on peut ouvrir des connexions (facultatif)
	 */
	public function __construct($domain = null, $bindName = null, $bindPwd = null, $ldapServers = array()){
		$this->domain = (!empty($domain)) ? $domain : \Settings::LDAP_DOMAIN;
		$this->bindName = (!empty($bindName)) ? $bindName : \Settings::LDAP_BIND_NAME;
		$this->bindPwd = (!empty($bindPwd)) ? $bindPwd : \Settings::LDAP_BIND_PWD;
		$this->ldapServers = (!empty($ldapServers)) ? (array)$ldapServers : \Settings::LDAP_SERVERS;

		// On crée $domainDCName à partir du nom de domaine
		$DCtab = explode('.', $this->domain);
		foreach ($DCtab as &$DC){
			$DC = 'DC='.$DC;
		}
		$this->domainDCName = implode(',', $DCtab);
	}

	/**
	 * Chercher un objet dans LDAP
	 *
	 * @param string $type Type d'objet à chercher (user, group). (facultatif)
	 * @param string $searched Nom de l'objet à chercher (nom sam). Si nul, tous les objets correspondants au type d'objet seront retournés. (facultatif)
	 * @param string $where OU dans laquelle chercher l'objet (facultatif)
	 * @param array $attributes Tableau des attributs à retourner. (facultatif)
	 * @param bool $strict Effectue la recherche en cherchant le terme $searched exact (par défaut, les termes retournés contiennent $searched) (facultatif)
	 * @param string $dc Contrôleur de domaine sur lequel effectuer la recherche. Si nul, récupère la valeur de $pdc (facultatif)
	 *
	 * @return array Tableau des informations de l'objet.(ex : sAMAccountName (nom unique), whenCreated, thumbnailPhoto, sn (nom), givenName (prénom), cn (prénom nom), description, displayName (nom affiché), lastLogon, mail (adresse mail), memberOf (liste des groupes)) (facultatif)
	 *
	 */
	public function search($type = 'user', $searched = null, $where = null, $attributes = array(), $strict = false, $dc = null){
		$params = '(cn=*)';
		// Si $dc n'est pas renseigné, on prend le premier de la liste des serveurs LDAP
		if (empty($dc))	$dc = $this->ldapServers[0];
		// Si la connexion à ce serveur LDAP n'a pas été ouverte, on l'initie
		if (!isset($this->connections[$dc])){
			$this->connections[$dc] = new Connection($dc, $this->bindName, $this->bindPwd, $this->domain);
		}
		$connection = $this->connections[$dc]->connection();
		// On indique dans quelle OU effectuer la recherche
		if (!empty($where)){
			$where = rtrim($where, ',').','.$this->domainDCName;
		}else{
			$where = $this->domainDCName;
		}
		if (!empty($searched)){
			//Recherche d'un terme
			if ($strict){
				$params = '(samaccountname='.$searched.')';
			}else{
				$params = '(samaccountname=*'.$searched.'*)';
			}
		}
		$filter = '(&(objectCategory='.$type.')'.$params.')';
		$sr=ldap_search($connection, $where, $filter, $attributes);

		return ldap_get_entries($connection, $sr);
	}

	/**
	 * Teste une connexion à un serveur LDAP avec les identifiants fournis
	 *
	 * @param string $user Login de l'utilisateur
	 * @param string $pwd Mot de passe de l'utilisateur
	 * @param string $dc Contrôleur de domaine (facultatif)
	 *
	 * @return bool true si connexion OK, false sinon
	 */
	public function tryLDAPLogin($user, $pwd, $dc = null){
		// Si $dc n'est pas renseigné, on prend le premier de la liste des serveurs LDAP
		if (empty($dc))	$dc = $this->ldapServers[0];
		$connect = new Connection($dc, $user, $pwd, $this->domain);
		// Si badCreds est à false, alors la connexion a réussi
		if ($connect->badCreds() !== false){
			new Alert('error', 'Les identifiants sont incorrects !');
			return false ;
		}else{
			$filter = null;
			If (!empty(\Settings::LDAP_AUTH_OU) and \Settings::LDAP_AUTH_OU != '*'){
				$filter = 'OU='.\Settings::LDAP_AUTH_OU;
			}
			$userLDAP = $this->search('person', $user, $filter, array(), true);
			if ($userLDAP['count'] == 0){
				new Alert('error', 'Cet utilisateur n\'est pas autorisé à se connecter !');
				return false;
			}
			$groups = $userLDAP[0]['memberof'];
			unset ($groups['count']);
			if (!empty(\Settings::LDAP_GROUP) and \Settings::LDAP_GROUP != '*'){
				$allowed = false;
				foreach ($groups as $group){
					if (preg_match('/CN=(.+?),/i', $group, $matches)){
						if (strtolower($matches[1]) == strtolower(\Settings::LDAP_GROUP))	$allowed = true;
					}
				}
				if (!$allowed){
					new Alert('error', 'Cet utilisateur n\'est pas autorisé à se connecter !');
					return false;
				}
			}
			return true;
		}
	}

	/**
	 * Liste des utilisateurs d'un domaine LDAP
	 *
	 * @param string $format Format de renvoi de la liste (JSON, array, object) (facultatif)
	 * @param string $searched Critères de recherche (partie d'un nom) (facultatif)
	 * @param string $where OU dans laquelle effectuer la recherche. (facultatif)
	 * @param bool  $strict Recherche stricte (facultatif)
	 *
	 * @return mixed
	 */
	public function users($format = null, $searched = null, $where = null, $strict = false){
		$attributes[] = 'sAMAccountName';
		$res = $this->search('person', $searched, $where, $attributes, $strict);
		switch($format){
			case 'json':
				$users = '{"options" :[';
				foreach ($res as $user){
					if (isset($user['samaccountname'][0])){
						$users .= '"'.$user['samaccountname'][0].'",';
					}
				}
				$users = rtrim($users, ',');
				$users .= ']}';
				return $users;
			case 'array':
			case 'object':
				var_dump($res);
		}
		return false;
	}

	/**
	 * Recherche à quels groupes appartient un utilisateur
	 * La recherche peut être récursive.
	 *
	 * @param string $userSAM Nom SAM (unique) de l'utilisateur
	 * @param string $searched Nom du groupe cherché, pour la récursivité - Ne pas utiliser lors d'un appel direct (facultatif)
	 * @param bool   $recursive Active ou non la récursivité dans la recherche (facultatif)
	 * @param int    $recLevel Utilisé pour savoir à quel degré de récursivité on est. Afin de ne pas surcharger le serveur, on s'arrête à 50 niveaux par défaut. - Ne pas utiliser lors d'un appel direct. (facultatif)
	 *
	 * @return array|bool liste des groupes ou false
	 */
	public function userMembership($userSAM, $searched = null, $recursive = true, $recLevel = 0){
		if(empty($searched)){
			$res = $this->search('user', $userSAM, null, array('memberOf'));
		}else{
			// On met en cache tous les groupes LDAP, afin d'éviter de faire des requêtes LDAP
			if (empty($this->ldapGroups)){
				$ldapGroups = $this->search('group', null, null, array('memberOf'));
				foreach ($ldapGroups as $group){
					if (is_array($group)) {
						$tab = explode(',', $group['dn']);
						$groupName = substr($tab[0], 3);
						$this->ldapGroups[strtolower($groupName)] = array(array(
							'memberof'  => (isset($group['memberof'])) ? $group['memberof'] : null),
						  'count'     => $group['count']
						);
					}
				}
			}
			$res = $this->ldapGroups[strtolower($searched)];
		}
		$recLevel++;
		// Afin d'éviter de potentiels boucles infinies, on stoppe après le 50e niveau
		if ($res['count'] > 0 and $recLevel < 50){
			foreach ($res as $item){
				if (isset($item['memberof'])){
					foreach ($item['memberof'] as $group){
						//On parcoure la liste des groupes
						if (is_string($group)){
							$tab = explode(',', $group);
							$group = substr($tab[0], 3);
							$membership[] = ucfirst($group);
							if ($recursive){
								//On récupère les groupes auxquels appartient le groupe
								$recurs = array();
								$recurs = $this->userMembership($userSAM, $group, true, $recLevel);
								if (!empty($recurs)){
									//On fusionne le tableau des groupes avec celui des groupes trouvés par récursivité
									$membership = array_unique(array_merge($membership, $recurs));
								}
							}
						}
					}
					sort($membership);
					return $membership;
				}
			}
		}
		return false;
	}

	/**
	 * Récupère la date de dernière connexion d'un utilisateur
	 *
	 * Le champ lastLogon n'est pas répliqué entre les différents DC, il faut donc tous les interroger et retourner la valeur la plus élevée.
	 *
	 * @link http://blogs.technet.com/b/askds/archive/2009/04/15/the-lastlogontimestamp-attribute-what-it-was-designed-for-and-how-it-works.aspx
	 *
	 * @param string $searched Utilisateur recherché
	 *
	 * @return int timestamp Unix
	 */
	public function lastLogon($searched){
		$lastLogon = 0;
		foreach ($this->ldapServers as $ldapServer){
			$res = $this->search('person', $searched, null, array('lastLogon'), false, $ldapServer);
			if (isset($res[0]['lastlogon'][0])){
				$lastl = $res[0]['lastlogon'][0];
				if ($lastl > $lastLogon){
					$lastLogon = $lastl;
				}
			}
		}
		return ($lastLogon > 0 ) ? Sanitize::ADToUnixTimestamp($lastLogon) : 0;
	}

	/**
	 * Retourne le SID d'un object Active Directory à partir de son format binaire
	 *
	 * Les SID sont codés en binaire lorsqu'on effectue une requête LDAP. Pour obtenir un SID lisible, il faut donc le convertir en chaîne de caractères
	 *
	 * @param string $binsid ObjectSID en binaire
	 *
	 * @return string
	 */
	public function getADObjectSID($binsid) {
		$hex_sid = bin2hex($binsid);
		$rev = hexdec(substr($hex_sid, 0, 2));
		$subcount = hexdec(substr($hex_sid, 2, 2));
		$auth = hexdec(substr($hex_sid, 4, 12));
		$result = "$rev-$auth";

		for ($x=0;$x < $subcount; $x++) {
			$subauth[$x] =
				hexdec($this->little_endian(substr($hex_sid, 16 + ($x * 8), 8)));
			$result .= "-" . $subauth[$x];
		}

		// Cheat by tacking on the S-
		return 'S-' . $result;
	}

	// Converts a little-endian hex-number to one, that 'hexdec' can convert
	protected function little_endian($hex) {
		$result = null;
		for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2) {
			$result .= substr($hex, $x, 2);
		}
		return $result;
	}

}