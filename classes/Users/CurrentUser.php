<?php
/**
 * Classe de l'utilisateur connecté
 *
 * User: cedric.gallard
 * Date: 21/03/14
 * Time: 12:32
 */

namespace Users;
use Logs\Alert;

/**
 * Classe de l'utilisateur actuellement connecté
 *
 * @package Users
 */
class CurrentUser extends User{
	/**
	 * Mot de passe hashé
	 * @var string
	 */
	protected $pwd = '';

	/**
	 * Définit si l'utilisateur courant est connecté ou non
	 * @var bool
	 */
	protected $isLoggedIn = false;

	/**
	 * Construction de la classe de l'utilisateur courant
	 */
	public function __construct(){
		$cookie = Login::getCookie();
		if ($cookie !== false and Login::isLoggedIn($cookie->id)){
			// On appelle la construction de la classe User
			parent::__construct($cookie->id, 1);
			$this->isLoggedIn = true;
		}else{
			new Alert('debug', '<code>CurrentUser constructor</code> : User non connecté !');
			// On appelle la construction de la classe User
			parent::__construct(0);
		}
	}

	/**
	 * Retourne le statut de connexion de l'utilisateur courant
	 * @return bool
	 */
	public function isLoggedIn(){
		return $this->isLoggedIn;
	}

	/**
	 * Retourne le mot de passe crypté de l'utilisateur actuel
	 * @return string
	 */
	public function getPwd() {
		return $this->pwd;
	}

	/**
	 * Retourne la dernière connexion à LDAP de l'utilisateur actuel
	 * @return int
	 */
	public function getLDAPLastLogon(){
		global $ldap;
		if (!isset($this->LDAPLastLogon)) $this->LDAPLastLogon = $ldap->lastLogon($this->name);
		return $this->LDAPLastLogon;
	}

	/**
	 * Retourne les groupes LDAP dont fait partie l'utilisateur actuel
	 * @return array|bool
	 */
	public function getLDAPUserMembership(){
		global $ldap;
		if (!isset($this->LDAPUserMembership)) $this->LDAPUserMembership = $ldap->userMembership($this->name);
		return $this->LDAPUserMembership;
	}
} 