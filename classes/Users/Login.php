<?php
/**
 * Classe de connexion au site
 *
 * User: cedric.gallard
 * Date: 19/03/14
 * Time: 09:10
 *
 * @package Users
 */

namespace Users;
use Components\Avatar;
use Forms\Fields\Button;
use Forms\Fields\Email;
use Forms\Fields\Password;
use Forms\Fields\StringField;
use Forms\Form;
use Forms\Pattern;
use Forms\PostedData;
use Logs\Alert;
use Front;
use Get;
use Sanitize;
use Settings\Version;

/**
 * Classe de gestion de l'authentification
 *
 * @package Users
 */
class Login {

	/**
	 * Clé de salage
	 *
	 * Générateur de clé de salage : <https://api.wordpress.org/secret-key/1.1/salt/>
	 * @var string
	 */
	protected static $salt = \Settings::SALT_AUTH;

	/**
	 * Nom du cookie utilisé pour l'authentification
	 * @var string
	 */
	protected static $cookieName = \Settings::COOKIE_NAME;


	/**
	 * Mode d'authentification
	 * - ldap
	 * - sql
	 *
	 * @var string
	 */
	protected static $authMode = \Settings::AUTH_MODE;

	/**
	 * Suppression du cookie d'authentification et de la session PHP
	 */
	static function deleteCookie(){
		setcookie(self::$cookieName, "", time()-3600, '/', '', FALSE, TRUE); //On supprime le cookie
		unset($_COOKIE[self::$cookieName]);
		$_SESSION = array();
		session_destroy();
	}

	/**
	 * Retourne les infos du cookie d'authentification sous forme d'objet
	 * - $cookie->userID
	 * - $cookie->userHash
	 *
	 * @return bool|Object
	 */
	static function getCookie(){
		if (isset($_COOKIE[self::$cookieName])){
			$cookieArray = unserialize($_COOKIE[self::$cookieName]);
			$cookie = new \stdClass();
			$cookie->id = $cookieArray[0];
			$cookie->hash = $cookieArray[1];
			return $cookie;
		}
		return false;
	}

	/**
	 * Vérifie que l'utilisateur est connecté
	 * @param int|string $user Nom ou ID de l'utilisateur à vérifier
	 *
	 * @return bool
	 */
	static function isLoggedIn($user){
		if ($cookie = self::getCookie()){
			$userLogin = new User($user, 0);
			if ($cookie->id === $user and $userLogin->getHash() == $cookie->hash)	return true;
		}
		return false;
	}

	/**
	 * Mélange le mot de passe avec une clé de salage pour ne pas le mettre en clair dans la base de données
	 * @param string $pwd Mot de passe
	 *
	 * @return string
	 */
	static function saltPwd($pwd){
		return sha1($pwd.self::$salt);
	}

	/**
	 * Valide la connexion d'un utilisateur
	 *
	 * Le nombre de tentatives de connexions est enregistré au niveau de l'utilisateur. Si celui-ci dépasse le nombre maximum autorisé, le compte est verrouillé pour une durée définie dans `$timeToWaitAfterLock`
	 * Si la connexion se passe bien, le compteur est réinitialisé (dans la méthode `doLogin`)
	 *
	 * @return bool
	 */
	static function tryLogin(){
		global $ldap, $db;
		$maxLoginAttempts = 6;
		$timeToWaitAfterLock = 3600*12;
		$userDb = false;
		$from = (isset($_REQUEST['from'])) ? $_REQUEST['from'] : '';
		if (!isset($_REQUEST['loginName']) or empty($_REQUEST['loginName']) or !isset($_REQUEST['loginPwd']) or empty($_REQUEST['loginPwd'])) {
			new Alert('error', 'Le nom ou le mot de passe est vide !');
			return false;
		}
		$loginName = htmlspecialchars($_REQUEST['loginName']);
		$loginPwd = htmlspecialchars($_REQUEST['loginPwd']);
		$stayConnected = (isset($_REQUEST['stayConnected'])) ? true : false;
		if (!empty($loginName) and !empty($loginPwd)){
			if (\Settings::AUTH_MODE == 'sql'){
				if ($userDb = UsersManagement::getDBUsers($loginName, true)){
					// On réinitialise les tentatives de connexions au bout de 12h
					if (version_compare(Version::getDbVersion(), '1.1', '>=') and $userDb->lastLogin < (time() - $timeToWaitAfterLock)){
						$ret = $db->update('users', array('loginAttempts' => 0), array('id' => $userDb->id));
						$userDb->loginAttempts = 0;
					}
					// On bloque au bout de 6 tentatives
					if (version_compare(Version::getDbVersion(), '1.1', '>=') and $userDb->loginAttempts > ($maxLoginAttempts - 1)){
						new Alert('error', 'Ce compte est verrouillé !<br>Cause : Tentatives de connexion en échec trop élevées.');
						return false;
					}
					if ($userDb->pwd == self::saltPwd($loginPwd)) self::doLogin($loginName, $from, $stayConnected);
				}else{
					new Alert('error', 'Ce nom de connexion est inconnu !');
					return false;
				}
				$ret = $db->update('users', array('loginAttempts' => ($userDb->loginAttempts + 1), 'lastLogin' => time()), array('id' => $userDb->id));
			}else{
				if (version_compare(Version::getDbVersion(), '1.1', '>=') and $userDb = UsersManagement::getDBUsers($loginName, false)){
					// On réinitialise les tentatives de connexions au bout de 12h
					if ($userDb->lastLogin < (time() - $timeToWaitAfterLock)){
						$ret = $db->update('users', array('loginAttempts' => 0), array('id' => $userDb->id));
						$userDb->loginAttempts = 0;
					}
					// On bloque au bout de 6 tentatives
					if ($userDb->loginAttempts > ($maxLoginAttempts - 1)){
						new Alert('error', 'Ce compte est verrouillé !<br>Cause : Tentatives de connexion en échec trop élevées.');
						return false;
					}
				}
				if ($ldap->tryLDAPLogin($loginName, $loginPwd)){
					if (!UsersManagement::getDBUsers($loginName, false)){
						if (UsersManagement::createDBUser($loginName)){
							return self::doLogin($loginName, $from, $stayConnected);
						}else{
							new Alert('error', 'Impossible de créer l\'utilisateur dans la base de données !');
							return false;
						}
					}else{
						return self::doLogin($loginName, $from, $stayConnected);
					}
				}else{
					new Alert('debug', '<code>Login->tryLogin()</code> : Connexion LDAP échouée !');
					if (version_compare(Version::getDbVersion(), '1.1', '>=') and $userDb){
						$ret = $db->update('users', array('loginAttempts' => ($userDb->loginAttempts + 1), 'lastLogin' => time()), array('id' => $userDb->id));
					}
				}
			}
		}
		new Alert('error', 'Les identifiants sont incorrects !');
		return false;
	}

	/**
	 * Effectue la connexion d'un utilisateur
	 *
	 * @param int|string $user ID ou nom de l'utilisateur
	 * @param string     $from URL vers laquelle rediriger l'utilisateur
	 * @param bool       $stayConnected Si false, le cookie expire à la fin de la session
	 *
	 * @return bool
	 */
	static protected function doLogin($user, $from = '/', $stayConnected){
		$cookieDuration = ($stayConnected) ? (time()+(\Settings::COOKIE_DURATION*3600)) : 0;
		$hash = UsersManagement::updateUserHash($user);
		$ret = setcookie(\Settings::COOKIE_NAME, serialize(array($user,$hash)), $cookieDuration, '/', '', FALSE, TRUE);
		if (!$ret){
			new Alert('error', 'Impossible de créer le cookie d\'authentification !');
			return false;
		}
		if ($from != '/') {
			$args = Get::urlParamsToArray($from);
			unset($args['from']);
			unset($args['action']);
			$urlArgs = http_build_query($args);
			header('location: index.php'. ((!empty($urlArgs)) ? '?'.$urlArgs : ''));
		}else{
			header('location: index.php');
		}
		exit();
	}

	/**
	 * Crée le premier utilisateur du site, si l'authentification se fait via sql.
	 *
	 * @return bool
	 */
	protected static function createFirstUser(){
		$req = PostedData::get();
		if (!isset($req['name'])){
			new Alert('error', 'Vous n\'avez pas indiqué le nom d\'utilisateur !');
			return false;
		}
		if (!isset($req['email'])){
			new Alert('error', 'Vous n\'avez pas indiqué l\'adresse email !');
			return false;
		}
		if (!isset($req['pwd'])){
			new Alert('error', 'Le mot de passe est vide !');
			return false;
		}
		$name = htmlspecialchars($req['name']);
		$email = $req['email'];
		if (!\Check::isEmail($email)){
			new Alert('error', 'Le format de l\'adresse email que vous avez saisi est incorrect !');
			return false;
		}
		$pwd = $req['pwd'];
		// On vérifie que le nouveau mot de passe comporte bien le nombre minimum de caractères requis
		if (strlen($pwd) < \Settings::PWD_MIN_SIZE){
			new Alert('error', 'Le mot de passe doit comporter au moins '.\Settings::PWD_MIN_SIZE.' caractères !');
			return false;
		}
		if (UsersManagement::createDBUser($name, $email, $pwd)){
			new Alert('success', 'L\'utilisateur <code>'.$name.'</code> a été créé ! It\'s alive !');
			return true;
		}else{
			new Alert('error', 'Impossible de créer l\'utilisateur <code>'.$name.'</code> !');
			return false;
		}
	}

	/**
	 * Affiche le formulaire de connexion
	 *
	 * @param string $from URL encodé de la page vers laquelle rediriger l'utilisateur après sa connexion
	 */
	static function loginForm($from = ''){
		if (isset($_REQUEST['action2']) and $_REQUEST['action2'] == 'createUser'){
			self::createFirstUser();
		}
		if (isset($_REQUEST['tryLogin'])){
			self::tryLogin();
		}
		if (isset($_REQUEST['from'])) $from = $_REQUEST['from'];

		$users = UsersManagement::getDBUsers();
		if (\Settings::AUTH_MODE == 'sql' and empty($users)){
			?>
			<!DOCTYPE html>
			<html lang="fr">
			<?php Front::htmlHead('Connexion'); ?>
			<body id="loginBody">
			<div id="">
				<!-- Page content -->
				<div id="page-content-wrapper" class="login-wrap container">
					<!-- Si javascript n'est pas activé, on prévient l'utilisateur que ça va merder... -->
					<noscript>
						<div class="alert alert-danger">
							<p class="text-center">Ce site fonctionne sans Javascript, mais vous devriez quand même l'activer pour un plus grand confort d'utilisation.</p>
						</div>
					</noscript>
					<div class="row">
						<div class="col-md-8 col-md-offset-2">
							<div class="text-center">
								<h1>
									<?php echo \Settings::SITE_NAME; ?> - Création d'un premier utilisateur
								</h1>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
							<!-- Keep all page content within the page-content inset div! -->
							<div class="page-content inset" id="loginPanel">
								<div class="text-center"><?php echo Avatar::display(null, 'Connectez-vous !'); ?></div>
								<br>
								<p>Il semble que vous soyez le premier à vouloir vous connecter, ce qui va faire de vous l'heureux administrateur de ce site !</p>
								<h3>Création de votre compte</h3>
								<?php
								$form = new Form('createUser');
								$form->addField(new StringField('name', null, 'Nom/Pseudo', 'Veuillez saisir un nom ou un pseudonyme', null, new Pattern('text', true, 4, 150), true));
								$form->addField(new Email('email', null, 'Adresse email', 'nom@domaine.extension', null, new Pattern('email', true, 0, 250), true));
								$form->addField(new Password('pwd', null, 'Mot de passe', 'Mot de passe de '.\Settings::PWD_MIN_SIZE.' caractères minimum', null, new Pattern('password', true, \Settings::PWD_MIN_SIZE, 100), true));
								$form->addField(new Button('action2', 'createUser', 'Créer l\'utilisateur'));
								$form->display();
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php Front::jsFooter(); ?>
			<script src="<?php echo Front::getBaseUrl(); ?>/js/granim.min.js"></script>
			</body>
			<?php
			exit;
		}else{
			?>
			<!DOCTYPE html>
			<html lang="fr">
			<?php Front::htmlHead('Connexion'); ?>
			<body id="loginBody">
				<!-- Page content -->
				<div id="page-content-wrapper" class="login-wrap container">
					<!-- Si javascript n'est pas activé, on prévient l'utilisateur que ça va merder... -->
					<noscript>
						<div class="alert alert-danger">
							<p class="text-center">Ce site fonctionne sans Javascript, mais vous devriez quand même l'activer pour un plus grand confort d'utilisation.</p>
						</div>
					</noscript>
					<div class="row">
						<div class="col-md-8 col-md-offset-2">
							<div class="text-center">
								<h1>
									<?php echo \Settings::SITE_NAME; ?> - Connexion
								</h1>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
							<!-- Keep all page content within the page-content inset div! -->
							<div class="page-content inset" id="loginPanel">
								<div class="text-center"><?php echo Avatar::display(null, 'Connectez-vous !'); ?></div>
								<br>
								<?php if (\Settings::AUTH_MODE == 'ldap'){ ?>
								<div class="alert alert-info text-center col">Saisissez vos identifiants Active Directory</div>
								<?php } ?>
								<form id="loginForm" class="" method="post" role="form" action="index.php?action=loginForm&tryLogin=true">
									<div class="form-group">
										<label for="loginName">Nom d'utilisateur</label>
										<input type="text" class="form-control input-lg" id="loginName" name="loginName" placeholder="Saisissez votre nom d'utilisateur">
									</div>
									<div class="form-group">
										<label for="loginPwd">Password</label>
										<div class="input-group">
											<input type="password" class="form-control pwd input-lg" id="loginPwd" name="loginPwd" placeholder="Saisissez votre mot de passe">
											<span class="input-group-btn">
	                      <button class="btn btn-default reveal tooltip-bottom input-lg" title="Afficher les caractères" type="button"><i class="fa fa-eye"></i></button>
	                    </span>
										</div>
									</div>
									<div class="checkbox">
										<label>
											<input type="checkbox" name="stayConnected" checked>
											Rester connecté
										</label>
									</div>
									<?php if (\Settings::AUTH_MODE == 'ldap'){ ?>
									<div class="pull-right">
										<span class="fa fa-sitemap"></span> Authentification sur <code><?php echo \Settings::LDAP_DOMAIN; ?></code>
									</div>
									<?php } ?>
									<?php if (!empty($from)){ ?>
									<input type="hidden" name="from" value="<?php echo $from; ?>">
									<?php } ?>
									<button type="submit" class="btn btn-primary btn-lg">Connexion</button>
								</form>
							</div>
						</div>
					</div>
				</div>
				<?php Front::jsFooter(); ?>
			</body>
			<?php
			exit;
		}
	}
} 