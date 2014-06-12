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
 * @package Ldap
 */

namespace Ldap;
use Logs\Alert;
use Sanitize;
use Users\UsersManagement;

/**
 * Class Ldap
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
	 * Classe de gestion des requêtes LDAP
	 *
	 * @param string  $domain Nom DNS du domaine
	 * @param string  $bindName Nom du compte utilisé pour les connexions aux serveurs LDAP
	 * @param string  $bindPwd Mot de passe du compte utilisé pour les connexions LDAP
	 * @param array   $ldapServers Liste des serveurs ldap sur lesquels on peut ouvrir des connexions
	 */
	public function __construct($domain = null, $bindName = null, $bindPwd = null, $ldapServers = array()){
		$this->domain = (!empty($domain)) ? $domain : LDAP_DOMAIN;
		$this->bindName = (!empty($bindName)) ? $bindName : LDAP_BIND_NAME;
		$this->bindPwd = (!empty($bindPwd)) ? $bindPwd : LDAP_BIND_PWD;
		$this->ldapServers = (!empty($ldapServers)) ? (array)$ldapServers : explode(', ', LDAP_SERVERS);

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
	 * @param string $type Type d'objet à chercher (user, group).
	 * @param string $searched Nom de l'objet à chercher (nom sam). Si nul, tous les objets correspondants au type d'objet seront retournés.
	 * @param string $where OU dans laquelle chercher l'objet
	 * @param array $attributes Tableau des attributs à retourner.
	 * @param bool $strict Effectue la recherche en cherchant le terme $searched exact (par défaut, les termes retournés contiennent $searched)
	 * @param string $dc Contrôleur de domaine sur lequel effectuer la recherche. Si nul, récupère la valeur de $pdc
	 * @return array Tableau des informations de l'objet.(ex : sAMAccountName (nom unique), whenCreated, thumbnailPhoto, sn (nom), givenName (prénom), cn (prénom nom), description, displayName (nom affiché), lastLogon, mail (adresse mail), memberOf (liste des groupes))
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
	 * @param string $dc Contrôleur de domaine
	 *
	 * @return bool true si connexion OK, false sinon
	 */
	public function tryLDAPLogin($user, $pwd, $dc = null){
		// Si $dc n'est pas renseigné, on prend le premier de la liste des serveurs LDAP
		if (empty($dc))	$dc = $this->ldapServers[0];
		$connect = new Connection($dc, $user, $pwd, $this->domain);
		// Si badCreds est à false, alors la connexion a réussi
		if ($connect->badCreds() !== false){
			return false ;
		}else{
			$userLDAP = $this->search('person', $user, 'OU='.LDAP_AUTH_OU, array(), true);
			if ($userLDAP['count'] == 0){
				new Alert('error', 'L\'utilisateur n\'est pas autorisé à se connecter car il n\'est pas dans la bonne OU !');
				return false;
			}
			return true;
		}
	}

	/**
	 * Liste des utilisateurs d'un domaine LDAP
	 *
	 * @param string $format Format de renvoi de la liste (JSON, array, object)
	 * @param string $searched Critères de recherche (partie d'un nom)
	 * @param string $where OU dans laquelle effectuer la recherche.
	 * @param bool  $strict Recherche stricte
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
	 * @param string $searched Nom du groupe cherché, pour la récursivité - Ne pas utiliser lors d'un appel direct
	 * @param bool   $recursive Active ou non la récursivité dans la recherche
	 * @param int    $recLevel Utilisé pour savoir à quel degré de récursivité on est. Afin de ne pas surcharger le serveur, on s'arrête à 50 niveaux par défaut. - Ne pas utiliser lors d'un appel direct.
	 *
	 * @return array|bool liste des groupes ou false
	 */
	public function userMembership($userSAM, $searched = null, $recursive = true, $recLevel = 0){
		if(empty($searched)){
			$res = $this->search('user', $userSAM, null, array('memberOf'));
		}else{
			$res = $this->search('group', strtolower($searched), null, array('memberOf'), true);
		}
		$recLevel++;
		// Afin d'éviter de potentiels boucles infinies, on stoppe après le 50e niveau
		if ($res['count'] > 0 and $recLevel < 50){
			foreach ($res as $item){
				if (isset($item['memberof'])){
					foreach ($item['memberof'] as $group){
						//On parcoure la liste des groupes
						if (!(int)$group){
							$tab = explode(',', $group);
							$group = substr($tab[0], 3);
							$membership[] = ucfirst($group);
							if ($recursive){
								//On récupère les groupes auxquel appartient le groupe
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
	 * @see http://blogs.technet.com/b/askds/archive/2009/04/15/the-lastlogontimestamp-attribute-what-it-was-designed-for-and-how-it-works.aspx
	 * @param string $searched Utilisateur recherché
	 * @return int timestamp Unix
	 *
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
}