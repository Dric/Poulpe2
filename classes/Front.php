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
 * Classe de méthodes relatives à l'affichage de Poulpe2
 *
 * @package General
 */
class Front {

	/**
	 * Menu général
	 * @var Menu
	 */
	public static $mainMenu = null;

	/**
	 * Titre par défaut de la page
	 * @var string
	 */
	protected static $defaultTitle = \Settings::SITE_NAME;

	/**
	 * Tableau de lignes html à inclure dans la partie `<head>` de la page
	 * @var string[]
	 */
	protected static $header = array();
	/**
	 * Tableau de lignes html à inclure dans les chargement de scripts javascript dans la partie `<head>` de la page
	 *
	 * Afin d'améliorer la vitesse d'affichage de la page, mieux vaut charger les scripts js à la fin de la page, et donc les ajouter à {@link $jsFooter}
	 * @var string[]
	 */
	protected static $jsHeader = array();
	/**
	 * Tableau de lignes html à inclure dans les chargement de fichiers CSS dans la partie `<head>` de la page
	 * @var string[]
	 */
	protected static $cssHeader = array();
	/**
	 * Tableau de lignes html à inclure dans la partie `<footer>` de la page
	 * @var string[]
	 */
	protected static $footer = array();
	/**
	 * Tableau de lignes html à inclure dans les chargement de scripts javascript dans la partie `<footer>` de la page
	 *
	 * Les scripts js devraient être dans la mesure du possible ajoutés au maximum dans ce tableau.
	 *
	 * @var string[]
	 */
	protected static $jsFooter = array();
	/**
	 * Tableau contenant les menus secondaires
	 * @var Menu[]
	 */
	protected static $secondaryMenus = array();
	/**
	 * Fil d'ariane (navigation)
	 *
	 * Un niveau hiérarchique est constitué d'un tableau avec les index suivants :
	 *  - `title`     => titre du niveau
	 *  - `link`      => lien du niveau
	 *  - `children`  => tableau contenant les éventuels niveaux inférieurs de navigation (l'imbrication de ces tableaux forme un fil d'ariane complet)
	 *
	 * @var array
	 */
	protected static $breadCrumb = array(
		'title' => 'Accueil',
	  'link'  => '.'
	);
	/**
	 * Chemin absolu vers les fichiers de Poulpe2
	 *
	 * Généré automatiquement.
	 * @var string
	 */
	protected static $absolutePath = '';

	/**
	 * URL de base
	 *
	 * Généré automatiquement
	 * @var string
	 */
	protected static $baseUrl = '';

	/**
	 * URL de base pour accéder au module
	 *
	 * @var string
	 */
	protected static $moduleUrl = \Settings::MODULE_URL;

	/**
	 * Charge les éventuels traitements globaux des modules
	 */
	public static function initModulesLoading(){
		ModulesManagement::initModulesLoading();
	}

	/**
	 * Initialise le menu général
	 */
	public static function initMainMenu(){
		global $cUser, $module;
		self::$mainMenu = new Menu('main', 'Menu');
		// Menu vers l'administration
		if (ACL::canAccess('admin', 0)){
			self::$mainMenu->add(new Item('admin', 'Administration', Front::getModuleUrl().'Admin', 'Administration', null, 'menu-warning'), 98);
		}
		if (\Settings::DISPLAY_HOME or (!\Settings::DISPLAY_HOME and $module->getName() != 'home')){
			self::$mainMenu->add(new Item('home', 'Accueil', self::$baseUrl, 'Revenir à l\'accueil', null, 'menu-highlight'), 2);
		}
		if ($cUser->isLoggedIn()){
			self::$mainMenu->add(new Item('logoff', 'Déconnexion', self::$baseUrl.'/action/logoff', 'Déconnexion de '.$cUser->getName()), 99);
		}else{
			self::$mainMenu->add(new Item('login', 'Connexion', self::$baseUrl.'/action/loginForm', 'Authentification'), 99);
		}
		ModulesManagement::getModulesMenuItems();
	}


	/**
	 * Affiche le menu principal
	 */
	public static function displayMainMenu(){
		global $cUser;
		$title = ($cUser->isLoggedIn()) ? 'Profil de '.$cUser->getName() : 'Bienvenue !';
		//Affichage de l'avatar
		?>
		<h4 class="text-center">
			<a href="<?php echo Front::getModuleUrl(); ?>profil">
			<?php echo $cUser->getAvatar(false, $title); ?>
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
			<script src="<?php echo self::$baseUrl; ?>/js/html5shiv.js"></script>
			<script src="<?php echo self::$baseUrl; ?>/js/respond.min.js"></script>
			<![endif]-->
			<title><?php echo self::$defaultTitle.' - '.$title; ?></title>

			<!-- The CSS -->
			<link href="<?php echo self::$baseUrl; ?>/css/poulpe2.css" rel="stylesheet">
			<?php self::cssHeader(); ?>
			<?php
			// On ajoute le contenu de $header
			foreach (self::$header as $headerLine){
				echo $headerLine.PHP_EOL;
			}
			?>
			<link rel="shortcut icon" href="<?php echo self::$baseUrl; ?>/img/favicons/favicon.ico">
			<link rel="apple-touch-icon" sizes="57x57" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-57x57.png">
			<link rel="apple-touch-icon" sizes="114x114" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-114x114.png">
			<link rel="apple-touch-icon" sizes="72x72" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-72x72.png">
			<link rel="apple-touch-icon" sizes="144x144" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-144x144.png">
			<link rel="apple-touch-icon" sizes="60x60" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-60x60.png">
			<link rel="apple-touch-icon" sizes="120x120" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-120x120.png">
			<link rel="apple-touch-icon" sizes="76x76" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-76x76.png">
			<link rel="apple-touch-icon" sizes="152x152" href="<?php echo self::$baseUrl; ?>/img/favicons/apple-touch-icon-152x152.png">
			<link rel="icon" type="image/png" href="<?php echo self::$baseUrl; ?>/img/favicons/favicon-196x196.png" sizes="196x196">
			<link rel="icon" type="image/png" href="<?php echo self::$baseUrl; ?>/img/favicons/favicon-160x160.png" sizes="160x160">
			<link rel="icon" type="image/png" href="<?php echo self::$baseUrl; ?>/img/favicons/favicon-96x96.png" sizes="96x96">
			<link rel="icon" type="image/png" href="<?php echo self::$baseUrl; ?>/img/favicons/favicon-16x16.png" sizes="16x16">
			<link rel="icon" type="image/png" href="<?php echo self::$baseUrl; ?>/img/favicons/favicon-32x32.png" sizes="32x32">
			<meta name="msapplication-TileColor" content="#2d89ef">
			<meta name="msapplication-TileImage" content="<?php echo self::$baseUrl; ?>/img/favicons/mstile-144x144.png">
			<meta name="msapplication-config" content="<?php echo self::$baseUrl; ?>/img/favicons/browserconfig.xml">
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
		<script src="<?php echo self::$baseUrl; ?>/js/jquery-1.11.0.min.js"></script>
		<script src="<?php echo self::$baseUrl; ?>/js/bootstrap.min.js"></script>
		<script src="<?php echo self::$baseUrl; ?>/js/pnotify/jquery.pnotify.min.js"></script>
		<script src="<?php echo self::$baseUrl; ?>/js/bootstrap-switch/bootstrap-switch.min.js"></script>
		<script src="<?php echo self::$baseUrl; ?>/js/Bootstrap-Confirmation/bootstrap-confirmation.min.js"></script>
		<script src="<?php echo self::$baseUrl; ?>/js/bootstrap-validator/validator.min.js"></script>
		<script src="<?php echo self::$baseUrl; ?>/js/bootstrap-waitingfor.min.js"></script>
		<script src="<?php echo self::$baseUrl; ?>/js/poulpe2.js"></script>

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
	 * Ajoute une ligne dans le tableau des chargements de scripts javascript dans la partie `<head>` de la page
	 * @warning Privilégiez plutôt le chargement des scripts en fin de page via {@link setJsFooter()}
	 * @param string $js
	 */
	public static function setJsHeader($js) {
		if (!in_array($js, self::$jsHeader)) self::$jsHeader[] = $js;
	}

	/**
	 * Ajoute une ligne dans le tableau des chargements de fichiers CSS dans la partie `<head>` de la page
	 * @param string $css
	 */
	public static function setCssHeader($css) {
		if (!in_array($css, self::$cssHeader)) self::$cssHeader[] = $css;
	}

	/**
	 * Ajoute une ligne dans le tableau des chargements de scripts javascript dans la partie `<footer>` de la page
	 * @param string $js
	 */
	public static function setJsFooter($js) {
		if (!in_array($js, self::$jsFooter)) self::$jsFooter[] = $js;
	}

	/**
	 * Ajoute un menu secondaire
	 * @param Menu $secondaryMenu
	 */
	public static function setSecondaryMenus(Menu $secondaryMenu) {
		self::$secondaryMenus[] = $secondaryMenu;
	}

	/**
	 * Affiche les menus secondaires
	 */
	public static function displaySecondaryMenus(){
		foreach (self::$secondaryMenus as $menu){
			/** @var Menu $menu */
			$menu->build('sidebar-nav secondary-menu-ul', null, true);
		}
	}

	/**
	 * Affiche le fil d'ariane
	 * @param array $breadCrumb
	 */
	public static function displayBreadCrumb($breadCrumb, $moduleVersion = '0') {
		if (self::$breadCrumb['link'] == '.') self::$breadCrumb['link'] = Front::getBaseUrl();
		if (!empty($breadCrumb)) self::$breadCrumb['children'] = $breadCrumb;
		?>
		<ol class="breadcrumb">
			<?php
			echo self::breadCrumbLevel(self::$breadCrumb);
			?>
			<?php
				if ($moduleVersion != '0'){
					?><li class="moduleVersion">v<?php echo $moduleVersion; ?></li><?php
				}
			?>
		</ol>
		<?php
	}

	/**
	 * Affiche un niveau hiérarchique dans le fil d'ariane, et ses éventuels sous-niveaux
	 *
	 * Cette fonction est récursive et permet de créer un fil d'ariane.
	 *
	 * @param  array $breadCrumb Niveau hiérarchique
	 * @param string $retLine code html déjà généré par les niveaux supérieurs
	 *
	 * @return string
	 */
	protected static function breadCrumbLevel($breadCrumb, $retLine = ''){
		$retLine .= '<li'.((!isset($breadCrumb['children'])) ? ' class="active"' : '').'><a href="'.$breadCrumb['link'].'">'.ucfirst($breadCrumb['title']).'</a></li>';
		if (isset($breadCrumb['children']) and is_array($breadCrumb['children'])) return self::breadCrumbLevel($breadCrumb['children'], $retLine);
		return $retLine;
	}

	/**
	 * Retourne le chemin absolu du script
	 * @return string
	 */
	public static function getAbsolutePath() {
		return self::$absolutePath;
	}

	/**
	 * Définit le chemin absolu du script
	 * @param string $absolutePath
	 */
	public static function setAbsolutePath($absolutePath) {
		self::$absolutePath = $absolutePath;
		if (isset($_SESSION)) $_SESSION['absolutePath'] = $absolutePath;
	}

	/**
	 * Retourne l'URL de base du script
	 *
	 * Cette méthode permet de ne pas avoir de problèmes en cas d'utilisation des Pretty URL (de type `http://poulpe2/module/Admin`)
	 *
	 * Méthodes associées :
	 *  * Dans un module, `this->buildArgsUrl()` permet d'appeler un module en passant des arguments sans se soucier de si les pretty url sont utilisées
	 *  * `Front::getModuleUrl()` retourne toute l'URL pour appeler un module (lorsqu'on l'appelle hors du module)
	 *
	 * @return string
	 */
	public static function getBaseUrl() {
		return self::$baseUrl;
	}

	/**
	 * Définit le chemin absolu du script
	 *
	 * @param string $baseUrl
	 */
	public static function setBaseUrl($baseUrl) {
		if (!isset($_SESSION) or !isset($_SESSION['baseUrl'])) {
			self::$baseUrl = sprintf(
				"%s://%s/%s",
				isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
				$_SERVER['SERVER_NAME'],
				$baseUrl
			);
			$_SESSION['baseUrl'] = self::$baseUrl;
		}elseif (isset($_SESSION)){
			self::$baseUrl = $_SESSION['baseUrl'];
		}
		self::$moduleUrl = self::$baseUrl.'/'.\Settings::MODULE_URL;
	}


	/**
	 * Retourne l'url de base pour accéder aux modules
	 * @return string
	 */
	public static function getModuleUrl() {
		return self::$moduleUrl;
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