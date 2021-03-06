<?php
/**
 * Classe de gestion des utilisateurs
 *
 * User: cedric.gallard
 * Date: 19/03/14
 * Time: 09:13
 *
 * @package Users
 */

namespace Users;
use Logs\Alert;
use Sanitize;

/**
 * Classe de gestion des utilisateurs
 *
 * @package Users
 */
class UsersManagement {

	/**
	 * Liste des utilisateurs
	 * @var User[]
	 */
	static protected $usersList = array();

	/**
	 * Retourne les infos d'un utilisateur de la bdd
	 *
	 * Afin d'éviter de lancer une requête chaque fois qu'on veut un nom d'utilisateur, on les stocke dans une propriété statique
	 *
	 * @param int|string $user ID, nom ou adresse email de l'utilisateur
	 * @param bool $nameOrEmail Si vrai, on cherche le nom ou le mail. Si faux, on ne cherche que le nom.
	 *
	 * @return object
	 */
	static function getDBUsers($user = null, $nameOrEmail = false){
		global $db;
		if (empty(self::$usersList)) self::$usersList = $db->query('SELECT * FROM `users`');
		if (empty($user)){
			return self::$usersList;
		}elseif (is_numeric($user)){
			$userObj = \Get::getObjectsInList(self::$usersList, 'id', $user);
			return (isset($userObj[0])) ? $userObj[0] : false;
		}else{
			$userObj = \Get::getObjectsInList(self::$usersList, 'name', $user);
			if (empty($userObj) and $nameOrEmail) $userObj = \Get::getObjectsInList(self::$usersList, 'email', $user);
			return (isset($userObj[0])) ? $userObj[0] : false;
		}
	}

	/**
	 * Retourne le nom d'un utilisateur
	 *
	 * @param int $userId Id de l'utilisateur
	 *
	 * @return string|bool
	 */
	static function getUserName($userId){
		$user = self::getDBUsers($userId);
		if ($user === false){
			return false;
		}
		return $user->name;
	}

	/**
	 * Met à jour le hash de l'utilisateur
	 * @param int|string $user ID ou nom de l'utilisateur
	 *
	 * @return string|bool Hash de l'utilisateur ou false en cas d'échec
	 */
	static function updateUserHash($user){
		global $db;
		$hash = hash('sha256', $user.\Settings::SALT_COOKIE);
		if (is_numeric($user)){
			$where = array('id' => $user);
		}else{
			$where = array('name' => $user);
		}
		$ret = $db->update('users', array('hash' => $hash, 'lastLogin' => time(), 'loginAttempts' => 0), $where);
		return ($ret) ? $hash : false;
	}

	/**
	 * Retourne les infos d'un utilisateur LDAP
	 *
	 * @param string $userName Nom de l'utilisateur
	 * @param bool   $complete Retourne toutes les propriétés. Passer à `false` pour éviter de remplir les propriétés qui demandent beaucoup de ressources à rechercher
	 *
	 * @return object|bool Informations de l'utilisateur LDAP ou false en cas d'erreur
	 */
	static function getLDAPUser($userName, $complete = true){
		global $ldap;
		$filter = null;
		If (!empty(\Settings::LDAP_AUTH_OU) and \Settings::LDAP_AUTH_OU != '*'){
			$filter = 'OU='.\Settings::LDAP_AUTH_OU;
		}
		$user = $ldap->search('person', $userName, $filter, array(), true);
		// Comme on a cherché un seul résultat, on ne veut que le premier item
		if ($user['count'] == 0){
			new Alert('error', 'Erreur : Impossible de retrouver l\'utilisateur <code>'.$userName.'</code> dans l\'annuaire LDAP !');
			return false;
		}
		$user = $user[0];
		$LDAPUser = new LDAPUser();
		$LDAPUser->cn = $user['cn'][0];
		$LDAPUser->employeeID = (isset($user['employeeid'])) ? $user['employeeid'][0] : null;
		$LDAPUser->givenName = (isset($user['givenname'])) ? $user['givenname'][0] : null;
		$LDAPUser->sn = (isset($user['sn'])) ? $user['sn'][0] : null;
		$LDAPUser->isDisabled = ($user["useraccountcontrol"][0] == "514" or $user["useraccountcontrol"][0] == "66050") ? true : false;
		$LDAPUser->displayName = $user['displayname'][0];
		if (isset($user['proxyaddresses'])){
			foreach ($user['proxyaddresses'] as $emailAdr){
				if (substr($emailAdr, 0, 4) == 'SMTP'){
					$LDAPUser->email = substr($emailAdr, 5);
				}
			}
			$LDAPUser->exchangeAlias = $user['mailnickname'][0];
			$mdbs = explode(',', $user['homemdb'][0]);
			$LDAPUser->exchangeBdd = ltrim($mdbs[0], 'CN=');
		}
		$LDAPUser->created = (isset($user['whencreated'])) ? Sanitize::ADToUnixTimestamp($user['whencreated'][0]) : null;
		if ($complete){
			$LDAPUser->lastLogon = $ldap->lastLogon($userName);
			$LDAPUser->groups = $ldap->userMembership($userName);
		}
		$LDAPUser->avatar = (isset($user['thumbnailphoto'])) ? $user['thumbnailphoto'][0] : null;
		return $LDAPUser;
	}

	/**
	 * Ajoute un utilisateur dans la base de données
	 * @param string $name Nom de l'utilisateur
	 * @param string $email Adresse email
	 * @param string $pwd Mot de passe non hashé (en clair)
	 * @param string $hash hash de cookie
	 * @param string $avatar Avatar (image)
	 *
	 * @TODO : retourner un objet User
	 * @return bool
	 */
	static function createDBUser($name, $email = null, $pwd = null, $hash = null, $avatar = null){
		global $db;
		$fields = array();
		$fields['name'] = $name;
		if (!empty($email)) $fields['email'] = $email;
		if (!empty($pwd)) $fields['pwd'] = Login::saltPwd($pwd);
		if (!empty($hash)) $fields['hash'] = $hash;
		if (!empty($avatar)) $fields['avatar'] = $avatar;
		return ($db->insert('users', $fields) === false) ? false : true;
	}

	/**
	 * Supprime un utilisateur de la base de données
	 * @param User|CurrentUser $User
	 *
	 * @return bool
	 */
	static function deleteUser($User){
		/** Si $User n'est ni un User ni un CurrentUser (User étant parent de CurrentUser), on retourne false.
		 * @see <http://www.php.net/manual/fr/language.operators.type.php#example-139>
		 */
		if (!($User instanceof User)){
			new Alert('debug', '<code>UserManagement::deleteDBUser()</code> : $User n\'est ni un objet User, ni un objet CurrentUser !');
			return false;
		}
		global $db;
		$where = array('id'=>$User->getId(), 'name'=>$User->getName());
		return $db->delete('users', $where);
	}

	/**
	 * Met à jour les informations d'un utilisateur dans la base de données
	 * @param User|CurrentUser $User Objet utilisateur
	 *
	 * @return bool
	 */
	static function updateDBUser($User){
		/** Si $User n'est ni un User ni un CurrentUser (User étant parent de CurrentUser), on retourne false.
		* @see <http://www.php.net/manual/fr/language.operators.type.php#example-139>
		*/
		if (!($User instanceof User)){
			new Alert('debug', '<code>UserManagement::updateDBUser()</code> : $User n\'est ni un objet User, ni un objet CurrentUser !');
			return false;
		}
		global $db;
		$fields = array();
		$fields['name'] = $User->getName();
		if ($User->getEmail() != '') $fields['email'] = $User->getEmail();
		if (method_exists($User, 'getPwd') and $User->getPwd() != null) $fields['pwd'] = $User->getPwd();
		if ($User->getHash() != '') $fields['hash'] = $User->getHash();
		if ($User->getAvatar(true) != '') $fields['avatar'] = $User->getAvatar(true);
		$where = array('id'=>$User->getId());
		return $db->update('users', $fields, $where);
	}

	/**
	 * Retourne la liste des utilisateurs
	 * @return User[]
	 */
	public static function getUsersList() {
		return self::$usersList;
	}
}