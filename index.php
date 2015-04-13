<?php
/**
 * Fichier index
 *
 * @package Index
 */

/**
 * Si un paramètre `debug` est présent, on active le mode debug.
 * Dans le fichier de config, il faut que la définition de la constante `DEBUG` soit préfixée par un `@`, sans quoi un message d'erreur peut s'afficher si la constante est définie deux fois.
 */
if (isset($_REQUEST['debug'])){
 define('DEBUG', true);
}

/**
 * On charge les paramètres du site
 */
if (!file_exists('classes/Settings/config.php')){
	die('<h1>Erreur : le fichier <code>config.php</code> n\'existe pas !</h1><p>Vous n\'avez probablement pas renomm&eacute; ni param&eacute;tr&eacute; le fichier <code>classes/Settings/config.default.php</code> en <code>classes/Settings/config.php</code></p>');
}
require_once 'classes/Settings/config.php';

if (DEBUG) {
	// Permet de faire du profilage avec XDebug et (<http://github.com/jokkedk/webgrind/>), à condition d'avoir activé le profilage XDebug
	setcookie('XDEBUG_PROFILE');
	// Pour obtenir le temps passé à générer la page.
	$startTime = microtime(true);
}

if (DETAILED_DEBUG) $classesUsed = array();

/**
 * Auto-Loading des classes
 *
 * Les fichiers des classes ne sont chargés qu'en cas de besoin
 */
spl_autoload_register(function ($class) {
	if (DETAILED_DEBUG) {
		global $classesUsed;
		$classesUsed[] = $class;
	}
	$tab = explode('\\', $class);
	// Les modules sont dans un répertoire à part
	if ($tab[0] == 'Modules' and !in_array($tab[1], array('Module', 'ModulesManagement'))) {
		@include_once str_replace("\\", "/", $class) . '.php';
	}elseif($tab[0] == 'phpseclib'){
		@include_once 'classes/FileSystem/' . str_replace("\\", "/", $class) . '.php';
	}else{
		@include_once 'classes/' . str_replace("\\", "/", $class) . '.php';
	}
});

use Db\Db;
use Ldap\Ldap;
use Logs\AlertsManager;
use Modules\Module;
use Modules\ModulesManagement;
use Users\ACL;
use Users\CurrentUser;
use Users\Login;

session_start();
//unset($_SESSION['baseUrl']);
if (!isset($_SESSION['absolutePath']) or !isset($_SESSION['baseUrl'])){
	Front::setAbsolutePath(realpath(dirname(__FILE__)));
	Front::setBaseUrl(trim(str_replace('index.php', '', $_SERVER['SCRIPT_NAME']), '/'));
}else{
	Front::setAbsolutePath($_SESSION['absolutePath']);
	Front::setBaseUrl($_SESSION['baseUrl']);
}
//var_dump($_SERVER);

/**
 * Connexion à la base de données
 * @var $db Db
 */
$db = new Db();
$ldap = new Ldap();
$ret = $param = null;
$redirectToLogin = false;

if (isset($_REQUEST['action'])){
	switch ($_REQUEST['action']){
		case 'tryLogin':
			$ret = Login::tryLogin();
			break;
		case 'logoff':
			Login::deleteCookie();
			$redirectToLogin = true;
			break;
		case 'loginForm':
			Login::loginForm((isset($_REQUEST['from']) ? $_REQUEST['from'] : null));
			break;
	}
}

/**
 * On instancie l'utilisateur courant
 * @var $cUser CurrentUser
 */
$cUser = new CurrentUser();

/** On gère les api */
Front::initModulesLoading();
\API\APIManagement::checkAPIRequest();

/**
 * Si l'utilisateur courant n'est pas authentifié et que l'authentification est obligatoire, on redirige l'utilisateur vers la page de connexion
 */
if ((!$cUser->isLoggedIn() or $redirectToLogin) and AUTH_MANDATORY){
	header('location: index.php?action=loginForm&from='.urlencode($_SERVER['REQUEST_URI']));
}else{
	/**
	 * Utilisateur connecté, on passe à la suite !
	 */

	/**
	 * Les ACL étant des formulaires particuliers à gérer, leur traitement et leur sécurisation se fait directement dans la classe ACL et non dans PostedData.
	 *
	 * Par ailleurs, la sécurisation du traitement impose d'avoir instancié l'utilisateur courant
	 */
	if (isset($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'saveACL':
				ACL::requestACLSave();
				break;
			case 'restoreDefaultACL':
				ACL::requestACLSave('restoreDefault');
		}
	}
	/**
	 * Récupération du module demandé (affiche l'index par défaut)
	 * @var $module Module
	 */
	$module = ModulesManagement::getModule();
	$title = $module->getTitle();

	/**
	 * Traitement des actions ($_REQUEST['action'])
	 */
	$module->getAction();

	/**
	 * Initialisation du menu principal
	 */
	Front::initMainMenu();

	/**
	 * Affichage de la page
	 */
	?>
	<!DOCTYPE html>
	<html lang="fr">

	<?php Front::htmlHead($title); ?>
	<body>
		<div id="wrapper">
			<!-- Sidebar -->
			<div id="sidebar-wrapper">
				<!-- Affichage du menu principal -->
				<?php Front::displayMainMenu(); ?>
				<!-- Affichage du menu secondaire -->
				<?php Front::displaySecondaryMenus(); ?>
			</div>
			<!-- Fin de la Sidebar -->
			<!-- Page content -->
			<div id="page-content-wrapper" class="container">
				<!-- Si javascript n'est pas activé, on prévient l'utilisateur que ça peut merder... -->
				<noscript>
					<div class="alert alert-info">
						<p>Pour un plus grand confort d'utilisation, veuillez activer Javascript. (Ce site reste entièrement fonctionnel sans Javascript)</p>
					</div>
					<style>
						.tab-content>.tab-pane{
							display: block;
						}
					</style>
				</noscript>
				<div class="content-header row">
					<div class="col-md-12">
						<h1>
							<a id="menu-toggle" href="#" class="btn btn-default btn-sm">Menu</a>
							<a href="<?php echo Front::getBaseUrl(); ?>"><?php echo SITE_NAME; ?></a>
						</h1>
					</div>
				</div>
				<div class="page-content inset row">
					<div class="col-md-12">
						<div>
						</div>
						<!-- Affichage du module demandé ! -->
						<?php $module->display(); ?>
					</div>
				</div>
			</div>
			<!-- Fin de Page content -->
			<!-- Pied de page -->
			<footer>
				<?php Front::footer(); ?> <?php if (DEBUG) echo ' | Mode debug activé | '; ?>
				<img class="tooltip-top" alt="Je suis Monsieur Poulpe !" src="<?php echo Front::getBaseUrl(); ?>/img/poulpe2-logo-23x32.png" style="vertical-align: text-bottom;"/> <span class="logo-highlight">P</span>oulpe<span class="logo-highlight">2</span> 2012-2015
			</footer>
			<?php
			if (DEBUG){
				AlertsManager::debug();
			}
			?>
			<noscript>
				<div class="row">
					<div class="col-md-6 col-md-offset-5">
						<?php AlertsManager::getAlerts(null, 'html'); ?>
					</div>
				</div>
			</noscript>
		</div>
		<?php Front::jsFooter(); ?>
		<!-- Fin du pied de page -->
	</body>
	</html>
<?php } ?>