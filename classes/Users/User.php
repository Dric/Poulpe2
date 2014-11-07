<?php
/**
 * Classe d'utilisateur
 *
 * User: cedric.gallard
 * Date: 19/03/14
 * Time: 08:54
 *
 */

namespace Users;
use Components\Avatar;

/**
 * Classe utilisateur
 *
 * @package Users
 */
class User {

	/**
	 * ID de l'utilisateur
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Pseudonyme ou prénom de l'utilisateur
	 * @var string
	 */
	protected $name = '';

	/**
	 * Nom du fichier image de l'avatar
	 * @var string
	 */
	protected $avatar = null;

	/**
	 * Adresse email de l'utilisateur
	 * @var string
	 */
	protected $email = '';

	/**
	 * Mot de passe crypté
	 * @var string
	 */
	protected $pwd = null;

	/**
	 * Hash d'authentification de l'utilisateur
	 * @var string
	 */
	protected $hash = '';

	/**
	 * Propriétés utilisateurs provenant d'un annuaire LDAP
	 * @var array
	 */
	protected $LDAPProps = array();

	/**
	 * Tableau des permissions de l'utilisateur
	 * @var array
	 */
	protected $ACL = array();

	/**
	 * Construction d'un objet User
	 *
	 * @param int|string  $user ID de l'utilisateur
	 * @param bool $loginOnly Si true, on zappe toutes les infos inutiles (typiquement pour vérifier une authentification)
	 */
	public function __construct($user, $loginOnly = false){
		// Si l'ID de l'utilisateur est différente de 0 (0 = connexion anonyme)
		if ($user !== 0){
			$userDB = UsersManagement::getDBUsers($user);
			foreach(get_object_vars($this) as $prop => $value){
				if (isset($userDB->$prop) and !empty($userDB->$prop)) {
					$this->$prop = $userDB->$prop;
				}
			}
			$this->id = (int)$this->id;
			if (!$loginOnly){
				if (AUTH_MODE == 'ldap'){
					/*
					 * On détermine si on est en train d'instancier l'utilisateur actuel, auquel cas on ne retourne pas les infos longues à charger
					 *
					 * Les propriétés longues à charger sont accessibles à la demande via des méthodes `$cUser->getLDAP<propriété>`
					 */
					if (isset($this->isLoggedIn)){
						$this->LDAPProps = UsersManagement::getLDAPUser($this->name, false);
					}else{
						$this->LDAPProps = UsersManagement::getLDAPUser($this->name);
					}
					if (isset($this->LDAPProps->email)) $this->email = strtolower($this->LDAPProps->email); //Sur Active Directory, les adresses email comportent parfois des majuscules, inutile de s'en encombrer.
				}
			}
		}
		if (!$loginOnly){
			$this->ACL = ACL::getUserACL($this->id);
		}
	}

	/**
	 * Retourne l'ID de l'utilisateur
	 * @return int
	 */
	public function getId(){
		return (int)$this->id;
	}

	/**
	 * Retourne le nom de l'utilisateur
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Retourne l'avatar de l'utilisateur
	 *
	 * @param bool   $realValue Retourne la valeur brute de la propriété si true
	 *
	 * @param string $title Contenu de la propriété alt de l'image (titre affiché sur l'infobulle de l'avatar)
	 *
	 * @return string
	 */
	public function getAvatar($realValue = false, $title = '') {
		if ($realValue) return $this->avatar;
		if (empty($title)) $title = $this->name;
		switch($this->avatar){
			case 'ldap':      $avatar = $this->LDAPProps->avatar; break;
			case 'gravatar':  $avatar = $this->email; break;
			case 'default':   $avatar = null; break;
			default:          $avatar = $this->avatar;
		}
		return Avatar::display($avatar, $title);
	}

	/**
	 * Retourne l'adresse email de l'utilisateur
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Retourne les permissions de l'utilisateur
	 * @return array
	 */
	public function getACL() {
		return $this->ACL;
	}

	/**
	 * retourne le hash de l'utilisateur
	 *
	 * Le hash est stocké en base de données et permet d'authentifier l'utilisateur via un cookie
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * Retourne les propriétés LDAP de l'utilisateur
	 * @return array
	 */
	public function getLDAPProps() {
		return $this->LDAPProps;
	}

	/**
	 * Défini les propriétés LDAP de l'utilisateur
	 * @param array $LDAPProps
	 */
	public function setLDAPProps($LDAPProps) {
		$this->LDAPProps = $LDAPProps;
	}

	/**
	 * Défini l'avatar de l'utilisateur
	 * @param string $avatar
	 */
	public function setAvatar($avatar) {
		$this->avatar = $avatar;
	}

	/**
	 * Retourne le mot de passe crypté de l'utilisateur
	 * @return string
	 */
	public function getPwd() {
		return $this->pwd;
	}

	/**
	 * Défini un mot de passe pour l'utilisateur
	 *
	 * Le mot de passe est crypté et salé avec la constante SALT_AUTH
	 * @param string $pwd
	 */
	public function setPwd($pwd) {
		$this->pwd = Login::saltPwd($pwd);
	}

}