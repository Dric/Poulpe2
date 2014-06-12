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
use Logs\Alert;
use Front;
use Get;
use Sanitize;

/**
 * Class Login
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
	protected static $salt = SALT_AUTH;

	/**
	 * Nom du cookie utilisé pour l'authentification
	 * @var string
	 */
	protected static $cookieName = COOKIE_NAME;


	/**
	 * Mode d'authentification
	 * - ldap
	 * - sql
	 *
	 * @var string
	 */
	protected static $authMode = AUTH_MODE;

	/**
	 * Suppression du cookie d'authentification
	 */
	static function deleteCookie(){
		setcookie(self::$cookieName, "", time()-3600, '/', '', FALSE, TRUE); //On supprime le cookie
		unset($_COOKIE[self::$cookieName]);
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
			$userLogin = new User($user, true);
			if ($cookie->id === $user and $userLogin->getHash() == $cookie->hash)	return true;
		}
		return false;
	}

	/**
	 * Mélange le mot de passe avec une clé de salage pour ne pas le mettre en clair dans la base de données
	 * @param $pwd
	 *
	 * @return string
	 */
	static function saltPwd($pwd){
		return sha1($pwd.self::$salt);
	}

	/**
	 * Valide la connexion d'un utilisateur
	 * @return bool
	 */
	static function tryLogin(){
		global $ldap;
		$from = (isset($_REQUEST['from'])) ? $_REQUEST['from'] : '';
		if (!isset($_REQUEST['loginName']) or empty($_REQUEST['loginName']) or !isset($_REQUEST['loginPwd']) or empty($_REQUEST['loginPwd'])) {
			new Alert('error', 'Le nom ou le mot de passe est vide !');
			return false;
		}
		$loginName = htmlspecialchars($_REQUEST['loginName']);
		$loginPwd = htmlspecialchars($_REQUEST['loginPwd']);
		if (!empty($loginName) and !empty($loginPwd)){
			if (AUTH_MODE == 'sql'){
				if ($userDb = UsersManagement::getDBUsers($loginName, true)){
					if ($userDb->pwd == self::saltPwd($loginPwd)) self::doLogin($loginName, $from);
				}else{
					new Alert('error', 'Ce nom de connexion est inconnu !');
					return false;
				}
			}else{
				if ($ldap->tryLDAPLogin($loginName, $loginPwd)){
					if (!UsersManagement::getDBUsers($loginName, false)){
						if (UsersManagement::createDBUser($loginName)){
							return self::doLogin($loginName, $from);
						}else{
							new Alert('error', 'Impossible de créer l\'utilisateur dans la base de données !');
							return false;
						}
					}else{
						return self::doLogin($loginName, $from);
					}
				}else{
					new Alert('debug', '<code>Login->tryLogin()</code> : Connexion LDAP échouée !');
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
	 * @param string $from URL vers laquelle rediriger l'utilisateur
	 *
	 * @return bool
	 */
	static protected function doLogin($user, $from = '/'){
		$cookieDuration = time()+(COOKIE_DURATION*3600);
		$hash = UsersManagement::updateUserHash($user);
		$ret = setcookie(COOKIE_NAME, serialize(array($user,$hash)), $cookieDuration, '/', '', FALSE, TRUE);
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
	 * Affiche le formulaire de connexion
	 */
	static function loginForm($from = ''){
		if (isset($_REQUEST['tryLogin'])){
			self::tryLogin();
		}
		if (isset($_REQUEST['from'])) $from = $_REQUEST['from'];
		?>
		<!DOCTYPE html>
		<html lang="fr">
		<?php Front::htmlHead('Connexion'); ?>
		<body>
			<div id="">
				<!-- Page content -->
				<div id="page-content-wrapper" class="login-wrap container">
					<!-- Si javascript n'est pas activé, on prévient l'utilisateur que ça va merder... -->
					<noscript>
						<div class="alert alert-danger">
							<h2>Javascript est désactivé !</h2>
							<p>Ce site ne fonctionne pas sans Javascript, il y a de fortes chances que ça foire !</p>
						</div>
					</noscript>
					<div class="col-md-4 col-md-offset-4">
						<div class="">
							<div class="content-header row">
								<h1>
									<?php echo SITE_NAME; ?> - Connexion
								</h1>
							</div>
							<!-- Keep all page content within the page-content inset div! -->
							<div class="page-content inset">
								<?php if (AUTH_MODE == 'ldap'){ ?>
								<div class="alert alert-warning text-center col">Saisissez vos identifiants Active Directory</div>
								<?php } ?>
								<form id="loginForm" class="" method="post" role="form" action="index.php?action=loginForm&tryLogin=true">
									<div class="form-group">
										<label for="loginName">Nom d'utilisateur</label>
										<input type="text" class="form-control" id="loginName" name="loginName" placeholder="Saisissez votre nom d'utilisateur">
									</div>
									<div class="form-group">
										<label for="loginPwd">Password</label>
										<div class="input-group">
											<input type="password" class="form-control pwd" id="loginPwd" name="loginPwd" placeholder="Saisissez votre mot de passe">
											<span class="input-group-btn">
	                      <button class="btn btn-default reveal" type="button"><i class="glyphicon glyphicon-eye-open"></i></button>
	                    </span>
										</div>
									</div>

									<?php if (AUTH_MODE == 'ldap'){ ?>
									<span class="pull-right"><span class="glyphicon glyphicon-lock"></span> Authentification sur <code><?php echo LDAP_DOMAIN; ?></code></span>
									<?php } ?>
									<?php if (!empty($from)){ ?>
									<input type="hidden" name="from" value="<?php echo $from; ?>">
									<?php } ?>
									<button type="submit" class="btn btn-primary">Connexion</button>
								</form>
							</div>
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