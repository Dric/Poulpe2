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
			if (DETAILED_DEBUG) new Alert('debug', '<code>CurrentUser constructor</code> : Authentification réussie par le cookie !');
			// On appelle la construction de la classe User
			parent::__construct($cookie->id);
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

} 