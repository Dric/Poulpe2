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
/*if (isset($_REQUEST['debug'])){
 define('DEBUG', true);
}*/

/**
 * On crée le fichier de config si besoin
 */
if (!file_exists('classes/Settings.php')){
	header('Location: install.php');
}


/**
 * Auto-Loading des classes
 *
 * Les fichiers des classes ne sont chargés qu'en cas de besoin
 */
spl_autoload_register(function ($class) {
	$tab = explode('\\', $class);
	// Les modules sont dans un répertoire à part
	if ($tab[0] == 'Modules' and !in_array($tab[1], array('Module', 'ModulesManagement'))) {
		@include_once str_replace("\\", "/", str_replace('Modules', \Settings::MODULE_DIR, $class)) . '.php';
	}elseif($tab[0] == 'phpseclib'){
		@include_once 'classes/FileSystem/' . str_replace("\\", "/", $class) . '.php';
	}else{
		@include_once 'classes/' . str_replace("\\", "/", $class) . '.php';
	}
});

if (\Settings::DEBUG) {
	/*
	* Permet de faire du profilage avec XDebug et (<http://github.com/jokkedk/webgrind/>), à condition d'avoir activé le profilage XDebug dans php.ini (ou conf.d/20-xdebug.ini) avec les commandes :
	*   xdebug.profiler_enable = 0
	*   xdebug.profiler_enable_trigger = 1
	*/
	setcookie('XDEBUG_PROFILE', true);
	// Pour obtenir le temps passé à générer la page.
	$startTime = microtime(true);
}

use Db\Db;
use Ldap\Ldap;
use Logs\Alert;
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

/**
 * On vérifie que les paramètres ont été bien définis dans la classe Settings
 */
if (empty(\Settings::get_class_constants(false))){
	if (file_exists('classes/Settings/config.php')){
		header('Location: install.php');
	}
	die('<h1>Attention : Les paramètres n\'ont pas été définis pour cette instance !</h1><p>Veuillez renseigner la classe <code>Settings</code>, ou bien lancer l\'<a href="install.php">installeur</a>.</p>');
}
//var_dump($_SERVER);

/**
 * Connexion à la base de données
 * @var $db Db
 */
$db = new Db();
$ldap = new Ldap();

// Vérification du schéma de base de données
if (!\Settings\Version::checkDbVersion()){
	\Settings\Version::updateDbSchema();
}

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

// Vérification des mises à jour de code
\Settings\Version::checkUpdates();


/** On gère les api */
Front::initModulesLoading();
\API\APIManagement::checkAPIRequest();

/**
 * Si l'utilisateur courant n'est pas authentifié ou que la connexion à l'annuaire LDAP est perdue - encas d'autehtnification LDAP) et que l'authentification est obligatoire, on redirige l'utilisateur vers la page de connexion
 */
if ((!$cUser->isLoggedIn() or $redirectToLogin or (!$ldap->isConnected() and Settings::AUTH_MODE == 'ldap')) and \Settings::AUTH_MANDATORY){
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

				<span id="top-link-block" class="hidden">
			    <a href="#wrapper" class="btn btn-default btn-xs tooltip-top" title="Retourner en haut de la page" onclick="$('html,body').animate({scrollTop:0});return false;">
				    <i class="fa fa-chevron-up"></i> Haut de page
			    </a>
				</span><!-- /top-link-block -->
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
					<div class="col-xs-10">
						<h1 class="content-header-title">
							<a href="<?php echo Front::getBaseUrl(); ?>"><?php echo \Settings::SITE_NAME; ?></a>
						</h1>
					</div>
					<div class="col-xs-2">
						<a id="menu-toggle" href="#" class="btn btn-default btn-sm pull-right">Menu</a>
					</div>
				</div>
				<div class="page-content inset row">
					<div class="col-md-12">
						<div>
						</div>
						<!-- Affichage du module demandé ! -->
						<?php $module->initDisplay(); ?>
					</div>
				</div>
			</div>
			<!-- Fin de Page content -->
			<!-- Pied de page -->
			<footer>
				<?php Front::footer(); ?> <?php if (\Settings::DEBUG) echo ' | Mode debug activé | '; ?>
				<img class="tooltip-top" alt="Je suis Monsieur Poulpe !" src="<?php echo Front::getBaseUrl(); ?>/img/poulpe2-logo-23x32.png" style="vertical-align: text-bottom;"/> <span class="logo-highlight">P</span>oulpe<span class="logo-highlight">2</span> 2012-<?php echo date('Y'); ?>
			</footer>
			<?php
			if (\Settings::DEBUG){
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
