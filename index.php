<?php
/**
 * Fichier index
 *
 * @package Index
 */

/**
 * On charge les paramètres du site
 */
require_once 'classes/Settings/config.php';

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
	if ($tab[0] == 'Modules'){
		include_once str_replace("\\", "/", $class) . '.php';
	}else{
		include_once 'classes/' . str_replace("\\", "/", $class) . '.php';
	}
});

use Db\Db;
use Ldap\Ldap;
use Logs\Alert;
use Logs\AlertsManager;
use Modules\ModulesManagement;
use Modules\Module;
use Users\ACL;
use Users\CurrentUser;
use Users\Login;

Front::setAbsolutePath(realpath(dirname(__FILE__)));

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
		case 'saveACL':
			ACL::requestACLSave();
	}
}
$cUser = new CurrentUser();
if (!$cUser->isLoggedIn() or $redirectToLogin){
	header('location: index.php?action=loginForm&from='.urlencode($_SERVER['REQUEST_URI']));
}else{
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
							<a href="."><?php echo SITE_NAME; ?></a>
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
			<footer>
				<?php Front::footer(); ?> <?php if (DEBUG) echo ' | Mode debug activé | '; ?>
				<img class="tooltip-top" alt="Je suis Monsieur Poulpe !" src="img/poulpe2-logo-23x32.png" style="vertical-align: text-bottom;"/> <span class="logo-highlight">P</span>oulpe<span class="logo-highlight">2</span> 2012-2014
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
	</body>
<?php } ?>