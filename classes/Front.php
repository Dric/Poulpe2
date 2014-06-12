<?php
/**
 * Classe d'initialisation du site
 *
 *
 * @package General
 */



use Components\Item;
use Components\Menu;
use Logs\Alert;
use Logs\AlertsManager;
use Modules\ModulesManagement;
use Users\ACL;

/**
 * Class Front
 *
 * @package General
 */
class Front {

	/**
	 * Menu général
	 * @var Menu
	 */
	public static $mainMenu = null;

	protected static $defaultTitle = SITE_NAME;

	protected static $header = array();
	protected static $jsHeader = array();
	protected static $cssHeader = array();
	protected static $footer = array();
	protected static $jsFooter = array();
	protected static $secondaryMenus = array();
	protected static $breadCrumb = array(
		'title' => 'Accueil',
	  'link'  => '.'
	);
	protected static $absolutePath = '';

	/**
	 * Initialise le menu général
	 */
	public static function initMainMenu(){
		global $cUser;
		self::$mainMenu = new Menu('main', 'Menu');
		// Menu vers l'administration
		if (ACL::canAccess('admin', 0)){
			self::$mainMenu->add(new Item('admin', 'Administration', '?module=Admin', 'Administration', null, 'menu-warning'), 98);
		}
		self::$mainMenu->add(new Item('home', 'Accueil', '.', 'Revenir à l\'accueil', null, 'menu-highlight'), 2);
		self::$mainMenu->add(new Item('portail', 'Portail', 'http://glpi', 'Retour au Petit Portail Informatique'), 97);
		self::$mainMenu->add(new Item('logoff', 'Déconnexion', '?action=logoff', 'Déconnexion de '.$cUser->getName()), 99);
		ModulesManagement::getModulesMenuItems();
	}


	/**
	 * Affiche le menu principal
	 */
	public static function displayMainMenu(){
		global $cUser;
		//Affichage de l'avatar
		?>
		<h4 class="text-center">
			<a href="?module=profil">
			<?php echo $cUser->getAvatar(false, 'Profil de '.$cUser->getName()); ?>
			</a>
		</h4>
		<?php
		self::$mainMenu->build('sidebar-nav');
	}

	/**
	 * Affiche la partie <HEAD> du html du site
	 * @param string $title
	 */
	public static function htmlHead($title = ''){
		?>
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta name="description" content="<?php echo self::$defaultTitle; ?>">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
			<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
			<!--[if lt IE 9]>
			<script src="js/html5shiv.js"></script>
			<script src="js/respond.min.js"></script>
			<![endif]-->
			<title><?php echo self::$defaultTitle.' - '.$title; ?></title>

			<!-- The CSS -->
			<link href="css/poulpe2.css" rel="stylesheet">
			<?php self::cssHeader(); ?>
			<?php
			// On ajoute le contenu de $header
			foreach (self::$header as $headerLine){
				echo $headerLine.PHP_EOL;
			}
			?>
			<link rel="shortcut icon" href="img/favicons/favicon.ico">
			<link rel="apple-touch-icon" sizes="57x57" href="img/favicons/apple-touch-icon-57x57.png">
			<link rel="apple-touch-icon" sizes="114x114" href="img/favicons/apple-touch-icon-114x114.png">
			<link rel="apple-touch-icon" sizes="72x72" href="img/favicons/apple-touch-icon-72x72.png">
			<link rel="apple-touch-icon" sizes="144x144" href="img/favicons/apple-touch-icon-144x144.png">
			<link rel="apple-touch-icon" sizes="60x60" href="img/favicons/apple-touch-icon-60x60.png">
			<link rel="apple-touch-icon" sizes="120x120" href="img/favicons/apple-touch-icon-120x120.png">
			<link rel="apple-touch-icon" sizes="76x76" href="img/favicons/apple-touch-icon-76x76.png">
			<link rel="apple-touch-icon" sizes="152x152" href="img/favicons/apple-touch-icon-152x152.png">
			<link rel="icon" type="image/png" href="img/favicons/favicon-196x196.png" sizes="196x196">
			<link rel="icon" type="image/png" href="img/favicons/favicon-160x160.png" sizes="160x160">
			<link rel="icon" type="image/png" href="img/favicons/favicon-96x96.png" sizes="96x96">
			<link rel="icon" type="image/png" href="img/favicons/favicon-16x16.png" sizes="16x16">
			<link rel="icon" type="image/png" href="img/favicons/favicon-32x32.png" sizes="32x32">
			<meta name="msapplication-TileColor" content="#2d89ef">
			<meta name="msapplication-TileImage" content="img/favicons/mstile-144x144.png">
			<meta name="msapplication-config" content="img/favicons/browserconfig.xml">
		</head>
		<?php
	}

	/**
	 * Affiche les scripts en haut de page
	 */
	public static function jsHeader(){
		foreach (self::$jsHeader as $headerLine){
			echo $headerLine.PHP_EOL;
		}
	}

	/**
	 * Affiche les css en haut de page
	 */
	public static function cssHeader(){
		foreach (self::$cssHeader as $headerLine){
			echo $headerLine.PHP_EOL;
		}
	}

	/**
	 * Ajoute le contenu de $footer
	 */
	public static function footer(){
		foreach (self::$footer as $footerLine){
			echo $footerLine.PHP_EOL;
		}
	}

	/**
	 * Affiche les scripts de bas de page
	 */
	public static function jsFooter(){
		?>
		<!-- JavaScript -->
		<script src="js/jquery-1.11.0.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/pnotify/jquery.pnotify.min.js"></script>
		<script src="js/bootstrap-switch/bootstrap-switch.min.js"></script>
		<script src="js/Bootstrap-Confirmation/bootstrap-confirmation.min.js"></script>
		<script src="js/poulpe2.js"></script>

		<!-- Custom JavaScript for the Menu Toggle -->
		<script>
			$("#menu-toggle").click(function(e) {
				e.preventDefault();
				$("#wrapper").toggleClass("active");
			});
		</script>
		<?php
		foreach (self::$jsFooter as $footerLine){
			echo $footerLine.PHP_EOL;
		}
		?>
		<!-- Affichage des alertes -->
		<?php AlertsManager::getAlerts(); ?>

		<?php
	}

	/**
	 * Ajoute une ou plusieurs lignes html au header
	 * @param string $header
	 */
	public static function setHeader($header) {
		self::$header[] = $header;
	}

	/**
	 * Ajoute une ou plusieurs lignes html au footer
	 * @param string $footer
	 */
	public static function setFooter($footer) {
		self::$footer[] = $footer;
	}

	/**
	 * @param string $js
	 */
	public static function setJsHeader($js) {
		self::$jsHeader[] = $js;
	}

	/**
	 * @param string $css
	 */
	public static function setCssHeader($css) {
		self::$cssHeader[] = $css;
	}

	/**
	 * @param string $js
	 */
	public static function setJsFooter($js) {
		self::$jsFooter[] = $js;
	}

	/**
	 * Ajoute un menu secondaire
	 * @param Menu $secondaryMenu
	 */
	public static function setSecondaryMenus(Menu $secondaryMenu) {
		self::$secondaryMenus[] = $secondaryMenu;
	}


	public static function displaySecondaryMenus(){
		foreach (self::$secondaryMenus as $menu){
			/** @var Menu $menu */
			$menu->build('sidebar-nav', null, true);
		}
	}

	/**
	 * @param array $breadCrumb
	 */
	public static function displayBreadCrumb($breadCrumb) {
		if (!empty($breadCrumb)) self::$breadCrumb['children'] = $breadCrumb;
		?>
		<ol class="breadcrumb">
			<?php
			echo self::breadCrumbLevel(self::$breadCrumb);
			?>
		</ol>
		<?php
	}

	protected static function breadCrumbLevel($breadCrumb, $retLine = ''){
		$retLine .= '<li'.((!isset($breadCrumb['children'])) ? ' class="active"' : '').'><a href="'.$breadCrumb['link'].'">'.ucfirst($breadCrumb['title']).'</a></li>';
		if (isset($breadCrumb['children']) and is_array($breadCrumb['children'])) return self::breadCrumbLevel($breadCrumb['children'], $retLine);
		return $retLine;
	}

	/**
	 * @return string
	 */
	public static function getAbsolutePath() {
		return self::$absolutePath;
	}

	/**
	 * @param string $absolutePath
	 */
	public static function setAbsolutePath($absolutePath) {
		self::$absolutePath = $absolutePath;
	}

	/**
	 * Affiche une pagination
	 *
	 * Les url de pages sont accessibles avec `&itemsPage=<page>`, car le paramètre `&page` est utilisé par les modules pour les sous-pages
	 *
	 * @param int    $page Page actuelle
	 * @param int    $postsPerPage Nombre d'items par page
	 * @param int    $total Nombre total d'items à paginer
	 * @param string $url URL des liens
	 *
	 */
	public static function paginate($page, $postsPerPage, $total, $url){
		$pagesNumber = ceil($total / $postsPerPage);
		if ($page == 0){
			$page = 1;
		}
		?>
		<div>
			<ul class="pagination">
				<li<?php	if ($page == 1){ ?> class="disabled"<?php	}	?>>
					<a href="<?php echo $url; ?>&itemsPage=1">&laquo;</a>
				</li>
				<?php
				for ($x=1; $x<=$pagesNumber; $x++){
					?>
				<li<?php if ($x == $page){ ?> class="active"<?php	}	?>>
					<a href="<?php echo $url.'&itemsPage='.$x; ?>"><?php echo $x; ?></a>
				</li>
				<?php	}	?>
				<li<?php if ($page == $pagesNumber){ ?> class="disabled"<?php	}	?>>
					<a href="<?php echo $url.'&itemsPage='.($page+1); ?>">&raquo;</a>
				</li>
			</ul>
		</div>
		<?php
	}

}