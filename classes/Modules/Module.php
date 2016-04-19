<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 31/03/14
 * Time: 09:38
 */

namespace Modules;
use Db\DbTable;
use Forms\Fields\Bool;
use Forms\Fields\Button;
use Forms\Fields\Hidden;
use Forms\Fields\LinkButton;
use Forms\JSSwitch;
use Logs\Alert;
use Components\Help;
use Front;
use Get;
use Sanitize;
use Forms\Form;
use Forms\PostedData;
use Settings\Setting;
use Users\ACL;

/**
 * Classe Module
 *
 * Cette classe sert de base pour tous les modules, qui doivent être déclarés en tant qu'enfants de cette classe.
 *
 * @package Modules
 */
class Module {

	/**
	 * ID du module en bdd - définit dans inDb()
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Nom du module (doit être unique pour l'ensemble des composants du site)
	 * @var string
	 */
	protected $name = 'home';

	/**
	 * Titre du module affiché en entête
	 * @var string
	 */
	protected $title = 'Poulpe2';

	/**
	 * Paramètres du module
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Activation de paramètres définissables par les utilisateurs
	 * @var bool
	 */
	protected $allowUsersSettings = false;

	/**
	 * Tables SQL manipulées par le module
	 * @var array
	 */
	protected $dbTables = array();

	/**
	 * Fil d'ariane
	 * @var array
	 */
	protected $breadCrumb = array();

	/**
	 * Données envoyées par les formulaires
	 * @var array
	 */
	protected $postedData = array();

	/**
	 * URL de la page principale du module
	 *
	 * Cette adresse est définie automatiquement
	 *
	 * @var string
	 */
	protected $url = '';

	/**
	 * Instantiation du module
	 *
	 * @param bool $bypassACL Ne vérifie pas les ACL - A utiliser avec prudence
	 */
	public function __construct($bypassACL = false){
		// On vérifie si le module est activé dans la bdd
		if (!$this->inDb() and !in_array($this->name, array('home', 'admin', 'userProfile'))){
			// S'il ne l'est pas, on l'installe (s'il est demandé et non actif, c'est forcément qu'on veut l'installer)
			new Alert('info', 'Installation du module <code>'.$this->name.'</code>');
			$this->id = ModulesManagement::activateModule($this);
			if (!$this->install()) ModulesManagement::disableModule($this);
		}
		if ($this->name != 'home' and !$bypassACL){
			$this->checkACL();
		}
		$module = explode('\\', get_class($this));
		$this->url = Front::getModuleUrl().end($module);
		// Fil d'Ariane. Si la page demandée est l'accueil, on ne la raffiche pas étant donné qu'elle est systématiquement indiquée
		if ($this->name != 'home' and HOME_MODULE != end($module)){
			$this->breadCrumb = array(
				'title' => $this->name,
				'link'  => $this->url
			);
		}
		// Création du menu si besoin
		$this->moduleMenu();

		// Traitement des envois de formulaires
		if (empty($this->postedData)){
			$this->postedData = PostedData::get();
			PostedData::reset();
		}
	}

	/**
	 * Vérifie les droits d'accès sur le module.
	 *
	 * Cette fonction doit être appelée après inDb() car l'id du module doit être renseigné.
	 *
	 * @return bool
	 */
	protected function checkACL(){
		global $cUser;
		$component = (isset($this->type)) ? $this->type : 'module';
		if (!ACL::canAccess($component, $this->id, $cUser->getId())){die('Vous n\'avez pas les droits sur ce module !');}
		return true;
	}

	/**
	 * Gère l'éventuel menu du module
	 */
	protected function moduleMenu(){
		/* Ceci est un exemple de menu de module
		$menu = new Menu($this->name, '', '', '', '');
		$menu->add(new Item('test', 'Test', '&page=test', 'This is a test page', 'info'));
		Front::setSecondaryMenus($menu);*/
	}

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		/* Ex :
		Front::$mainMenu->add(new Item('profil', 'Profil', '?module=profil', 'Profil', null, null), 97);
		*/
	}

	/**
	 * Permet de lancer des traitements globaux sans charger tout le module
	 */
	public static function initModuleLoading(){

	}

	/**
	 * @return array
	 */
	public function getBreadCrumb() {
		return $this->breadCrumb;
	}

	/**
	 * Permet de construire une URL pour appeler le module avec des arguments
	 *
	 * Exemple pour un module `Users` :
	 *  <code>$this->buildModuleQuery(array(id => 2));</code>
	 *  Donne l'URL :
	 *  <code>http://poulpe2/module/Users?id=2</code>
	 *
	 * @param Array $args Arguments à passer dans l'URL de la forme array(`argument` => `valeur`)
	 *
	 * @return string
	 */
	protected function buildArgsURL(Array $args){
		$url = $this->url;
		if (stripos($url, '?') !== false) {
			// Pas de pretty Url du type `poulpe2/module/<moduleName>`
			foreach ($args as $key => $value){
				$url .= '&'.$key.'='.$value;
			}
		}else{
			// Pretty Url
			$isFirst = true;
			foreach ($args as $key => $value){
				if ($isFirst){
					$url .= '?'.$key.'='.$value;
					$isFirst = false;
				}else{
					$url .= '&'.$key.'='.$value;
				}
			}
		}
		return $url;
	}

	/**
	 * Traite les requêtes de formulaire
	 *
	 * Les formulaires doivent envoyer une propriété 'action', qui sera traitée ici.
	 * Les méthodes qui traitent les actions doivent avoir le même nom que la valeur de l'argument 'action'
	 */
	public function getAction(){
		if (isset($_REQUEST['action'])){
			$action = htmlspecialchars($_REQUEST['action']);
			if (method_exists($this, $action)){
				$this->$action();
			}
		}
	}

	/**
	 * Redirige vers la page demandée
	 *
	 * Affiche aussi le fil d'Ariane en dessous du titre
	 * Pour que ça fonctionne correctement, les fonctions appelées doivent être nommées 'module'.$Page (avec la première lettre en majuscules)
	 *
	 * Vous pouvez aussi définir des sous-pages avec un paramètre `subPage`. Dans ce cas, c'est la méthode `module.$subPage` qui est appelée, le paramètre `page` ne servant que pour le fil d'ariane.
	 *
	 * @return bool
	 */
	protected function getPage(){
		$component = (isset($this->type)) ? $this->type : 'module';
		$subPage = false;
		if (isset($_REQUEST['page'])){
			switch ($_REQUEST['page']){
				default:
					if (method_exists($this, $component.ucfirst($_REQUEST['page']))) {
						$this->breadCrumb['children'] = array(
							'title' => $_REQUEST['page'],
							'link'  => $this->url.'&page='.$_REQUEST['page']
						);
						// Recherche d'une sous-page. Si la méthode liée existe, c'est elle qui sera ensuite appelée
						if (isset($_REQUEST['subPage']) and method_exists($this, $component.ucfirst($_REQUEST['subPage']))){
							$subPage = true;
							// Le fil d'ariane est construit sur une imbrication de tableaux référencés par la clé `children`.
							$this->breadCrumb['children']['children'] = array(
								'title' => $_REQUEST['subPage'],
								'link'  => $this->url.'&page='.$_REQUEST['page'].'&subPage='.$_REQUEST['subPage']
							);
						}
						// Si les paramètres `item`, `id` ou `name` existent, on l'ajoute au fil d'ariane.
						if (isset($_REQUEST['item']) or isset($_REQUEST['id']) or isset($_REQUEST['name'])){
							if (isset($_REQUEST['item'])) {
								$item = $_REQUEST['item'];
							}elseif (isset($_REQUEST['id'])){
								$item = $_REQUEST['id'];
							}else{
								$item = $_REQUEST['name'];
							}
							$itemBreadCrumb = array(
								'title' => $item,
								'link'  => null
							);
							if ($subPage) {
								$this->breadCrumb['children']['children']['children'] = $itemBreadCrumb;
							}else{
								$this->breadCrumb['children']['children'] = $itemBreadCrumb;
							}
						}
						// Pas de sous-page ou méthode liée inexistante, on appelle la méthode liée à `page`
						Front::displayBreadCrumb($this->breadCrumb);
						if ($subPage) {
							$this->{$component.ucfirst($_REQUEST['subPage'])}();
						}else{
							$this->{$component.ucfirst($_REQUEST['page'])}();
						}
						return true;
					}
					new Alert('error', 'La page demandée n\'existe pas !');
			}
		}
		// Pas de page demandée, on renvoie `false` pour déclencher l'affichage principal du module
		return false;
	}

	/**
	 * Gestion de l'affichage du module
	 */
	public function display(){
		if (!$this->getPage()){
			if (DISPLAY_BREADCRUMB) Front::displayBreadCrumb($this->breadCrumb);
			$this->mainDisplay();
		}
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Bienvenue</h1>
				</div>
				<h2>Notice d'utilisation</h2>
				<ul>
					<li>
						Si vous avez un doute quand à ce qu'il convient de faire, n'hésitez pas à passer la souris sur les petits symboles d'information :  <?php Help::iconHelp('Je suis un bouton d\'aide !'); ?><br />
					</li>
					<li>Certains modules peuvent être paramétrés pour vos besoins. Il vous suffit pour cela de cliquer sur le bouton <a href="#" class="btn btn-default btn-xs" title="Inutile de cliquer sur ce bouton, il ne vous emmènera nulle part..."><span class="fa fa-cog"></span> Paramètres</a> qui apparaît à côté du titre du module.</li>
					<li>Vous pouvez modifier certaines informations de votre <a href=".?module=profil">profil</a> en cliquant sur votre avatar en haut du menu.</li>
					<li>Ce produit ne convient pas aux fosses septiques.</li>
					<li>Visuel non contractuel.</li>
				</ul>
			</div>
		</div>
	<?php
	}

	/**
	 * Teste si le module est actif en bdd et charge les paramètres si c'est le cas
	 * @return bool
	 */
	public function inDb(){
		global $db;
		$inDb = $db->getVal('modules', 'id', array('name' => $this->name));
		if (!empty($inDb)){
			$this->id = (int)$inDb;
			$this->populateSettings();
			return true;
		}
		return false;
	}

	/**
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){

	}

	/**
	 * Remplit les paramètres
	 *
	 * Cette fonction lance la définition des paramètres, et récupère leurs valeurs stockées en bdd
	 *
	 * @return bool
	 */
	protected function populateSettings(){
		global $db, $cUser;
		$this->defineSettings();
		/*
		 * On récupère les paramètres globaux et les éventuelles valeurs de l'utilisateur courant.
		 * Construction de la requête : <http://stackoverflow.com/a/5426105/1749967>
		 */
		$sql = 'SELECT id, setting, type, ms.value as value, mus.value as userValue FROM modules_settings as ms LEFT JOIN modules_users_settings as mus ON ms.id = mus.moduleSetting AND mus.user IN ( '.$cUser->getId().', NULL ) WHERE ms.module = '.$this->id;
		$dbSettings = $db->query($sql);
		foreach ($dbSettings as $setting){
			if ($setting->setting == 'allowUsersSettings'){
				$this->allowUsersSettings = (bool)$setting->value;
			}else{
				if (isset($this->settings[$setting->setting])){
					if (!is_null($setting->userValue)) $this->settings[$setting->setting]->setUserValue($setting->userValue);
					$this->settings[$setting->setting]->setValue($setting->value);
					$this->settings[$setting->setting]->setId($setting->id);
				}
			}
		}
		return true;
	}

	/**
	 * Affiche les boutons de gestion des ACL et des paramètres
	 */
	protected function manageModuleButtons(){
		if (!empty($this->settings) and (ACL::canAdmin('module', $this->id) or $this->allowUsersSettings)) {
			?>&nbsp;<a class="settingsButton btn btn-default btn-xs" title="Paramètres du module" href="<?php echo $this->buildArgsURL(array('page' => 'settings')); ?>"><span class="fa fa-cog"></span> Paramètres</a><?php
		}
		$module = explode('\\', get_class($this));
		if (ACL::canAdmin('module', $this->id) and HOME_MODULE != end($module)){
			?>&nbsp;<a class="ACLButton btn btn-default btn-xs" title="Autorisations du module" href="<?php echo $this->buildArgsURL(array('page' => 'ACL')); ?>"><span class="fa fa-user"></span> Autorisations</a><?php
		}
	}

	/**
	 * Affichage du formulaire des paramètres du module
	 */
	protected function moduleSettings(){
		?>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs">
			<?php if ($this->allowUsersSettings){ ?><li class="active"><a href="#userSettings" data-toggle="tab">Paramètres utilisateur</a></li><?php } ?>
			<?php if (ACL::canAdmin('module', $this->id)){ ?><li<?php if (!$this->allowUsersSettings) echo ' class="active"'; ?>><a href="#generalSettings" data-toggle="tab">Paramètres généraux</a></li><?php } ?>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content">
			<?php if ($this->allowUsersSettings){ ?>
			<div class="tab-pane active" id="userSettings">
				<h3>Paramètres utilisateur <small><?php Help::iconHelp('Ces paramètres ne concernent que vous.'); ?></small></h3>
				<?php
				$form = new Form($this->name.'UsersSettings', null, array('fields' => $this->settings, 'userSettings' => true));
				$hidden = new Hidden('usersSettings', 'true');
				$hidden->setUserDefinable();
				$form->addField($hidden);
				$form->addField(new Button('action', 'saveSettings', 'Sauvegarder', null, 'btn-primary'));
				$form->addField(new LinkButton('cancel', $this->url, 'Revenir au module'));
				$form->display();
				unset($form);
				?>
			</div>
			<?php } ?>
			<?php if (ACL::canAdmin('module', $this->id)){ ?>
			<div class="tab-pane <?php if (!$this->allowUsersSettings) echo 'active'; ?>" id="generalSettings">
				<h3>Paramètres généraux <small><?php Help::iconHelp('Ces paramètres affectent le module et tous ses utilisateurs.'); ?></small></h3>
				<?php
				$form = new Form($this->name.'Settings', null, array('fields' => $this->settings), 'module', $this->id);
				$hasUsersSettings = false;
				foreach ($this->settings as $setting){
					if ($setting->getCategory() == 'user'){
						$hasUsersSettings = true;
					}
				}
				if ($hasUsersSettings){
					$form->addField(new Bool('allowUsersSettings', $this->allowUsersSettings, 'Autoriser les utilisateurs à personnaliser certains paramètres', null, null, true, null, null, false, new JSSwitch('small')));
				}
				$form->addField(new Button('action', 'saveSettings', 'Sauvegarder', null, 'btn-primary'));
				$form->addField(new LinkButton('cancel', $this->url, 'Revenir au module'));
				$form->display();
				?>
			</div>
			<?php } ?>
		</div>

		<?php
	}

	/**
	 * Affiche l'écran d'administration des permissions sur le module
	 */
	protected function moduleACL(){
		ACL::adminACL('module', $this->id, 'le module '.$this->name);
	}

	/**
	 * Récupère l'envoi du formulaire des paramètres et sauvegarde le tout
	 * @return bool
	 */
	protected function saveSettings(){
		$ret = false;
		if ((!$this->allowUsersSettings and !ACL::canModify('module', $this->id)) or ($this->allowUsersSettings and !ACL::canAccess('module', $this->id))){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$tableToSave = array();
		$req = $this->postedData;
		$usersSettings = false;
		if (isset($req['usersSettings'])){
			$usersSettings = true;
			unset($req['usersSettings']);
		}
		if (isset($req['allowUsersSettings'])){
			$this->allowUsersSettings = (bool)$req['allowUsersSettings'];
			unset($req['allowUsersSettings']);
		}
		if (!empty($req)){
			foreach ($req as $field => $value){
				if ($field == 'dbTable'){
					foreach ($value as $tableId => $tableRow){
						$table = Get::getObjectsInList($this->settings, 'name', $tableId);
						$tableName = $table[0]->getValue();
						// On supprime les valeurs nulles du tableau
						$tableRow = array_filter(array_map('array_filter', $tableRow));
						$tableToSave[$tableName] = $tableRow;
					}
				}elseif ($field != 'action'){
					if ($usersSettings){
						$this->settings[$field]->setUserValue($value);
					}else{
						$this->settings[$field]->setValue($value);
					}
				}
			}
			$ret = $this->saveDbSettings();
		}
		if ($ret === false) return false;
		if (!empty($tableToSave)) $ret = $this->saveDbTables($tableToSave);
		if ($ret === false) return false;
		return true;
	}

	/**
	 * Sauvegarde des items d'une table dans la bdd
	 *
	 * @param array $tables
	 *  - array 'table' La table doit être définie dans $this->dbTables
	 *    - array 'ligne' ('id' => array('field1' => value, 'field2' => value2, etc))
	 *
	 * @see \Db\Db->createTable pour la structure des tables dans $this->dbTables
	 * @return bool
	 */
	protected function saveDbTables(array $tables){
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$ret = array();
		foreach ($tables as $table => $lines){
			/**
			 * @var DbTable $dbTable
			 */
			$dbTable = $this->dbTables[$table];
			if (!isset($dbTable)){
				new Alert('debug', '<code>Module->saveDbTables()</code> : La table <code>'.$table.'</code> n\'existe pas !');
				return false;
			}
			$ret[$table] = $dbTable->insertRows($lines);
		}
		foreach ($ret as $table => $result){
			if (!$result){
				new Alert('error', 'Les enregistrements dans certaines tables n\'ont pas été effectués !');
				return false;
			}
		}
		return true;
	}

	/**
	 * Sauvegarde les paramètres du module dans la bdd
	 *
	 * Les paramètres sont enregistrés via des insertions pour plusieurs raisons :
	 *   - Ça permet de ne faire qu'une seule requête SQL là où il faudrait une requête par update
	 *   - On n'a pas à se soucier de savoir si le paramètre existe dans la base ou pas : s'il y est, ça le met à jour (grâce à ON DUPLICATE KEY UPDATE), sinon ça l'ajoute.
	 *
	 * @return bool
	 */
	public function saveDbSettings(){
		global $db, $cUser;
		// On ajoute le paramètre d'activation des paramétrages utilisateurs
		$this->settings['allowUsersSettings'] = new Setting('allowUsersSettings', 'bool', $this->allowUsersSettings);
		$settingsArr = $userSettingsArr = array();
		// On parcoure tous les paramètres du module
		foreach ($this->settings as $setting){
			if ($setting instanceof Setting){
				$value = Sanitize::SanitizeForDb($setting->getValue(false));
				$settingsArr[] = '('.$this->id.', "'.$setting->getName().'", "'.$setting->getCategory().'", '.$value.')';
				if (!is_null($setting->getUserValue()) and $this->allowUsersSettings){
					$userValue = Sanitize::SanitizeForDb($setting->getUserValue());
					$userSettingsArr[] = '('.$setting->getId().', '.$this->id.', '.$cUser->getId().', '.$userValue.')';
				}
			}else{
				new Alert('debug', '<code>Module->settingsSave()</code> : Un des paramètres n\'est pas un objet Setting !<br >'. Get::varDump($setting));
			}
		}
		// Avant d'enregistrer les paramètres, on vérifie que l'utilisateur a le droit de le faire
		if (ACL::canAdmin('module', $this->getId(), $cUser->getId())){
			$sql = 'INSERT INTO `modules_settings` (`module`, `setting`, `type`, `value`) VALUES '.implode(',', $settingsArr).' ON DUPLICATE KEY UPDATE `type` = VALUES(`type`), `value` = VALUES(`value`)';
			$ret = $db->query($sql);
			if ($ret === false) {
				unset($this->settings['allowUsersSettings']);
				new Alert('error', 'Impossible de sauvegarder les paramètres du module <code>'.$this->name.'</code> !');
				return false;
			}
		}
		// On supprime le paramètre allowUsersSettings afin qu'il ne reste pas dans la liste.
		unset($this->settings['allowUsersSettings']);
		// On enregistre les paramètres utilisateurs s'ils sont autorisés
		if ($this->allowUsersSettings and !empty($userSettingsArr)){
			$sql = 'INSERT INTO `modules_users_settings` (`moduleSetting`, `module`, `user`, `value`) VALUES '.implode(',', $userSettingsArr).' ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
			$ret = $db->query($sql);
			if ($ret === false) {
				new Alert('error', 'Impossible de sauvegarder les paramètres utilisateurs du module <code>'.$this->name.'</code> !');
				return false;
			}
		}
		new Alert('success', 'Les paramètres ont été sauvegardés !');
		return true;
	}

	/**
	 * Supprime les paramètres utilisateur du module
	 * @return bool
	 */
	protected function delUsersSettings(){
		global $db;
		return $db->delete('modules_users_settings', array('module'=>$this->id));
	}

	/**
	 * Installe le module en bdd, avec ses paramètres
	 * @return bool
	 */
	public function install(){
		/* Ceci est un exemple d'installation de module
		* Ne pas oublier de déclarer les utilisations de classes en haut du fichier :
		* - use Modules\Module;
		* - use Modules\ModulesManagement;
		* - use Settings\Setting;
		* - use Users\ACL;
		*

		// Définition des ACL par défaut pour ce module
		$defaultACL = array(
		  'type'  => 'modify',
		  'value' => true
		);
		// Si des commandes SQL complexes sont à passer, c'est ici que ça se fait !
		$sql = '';

		return ModulesManagement::installModule($this, $defaultACL, $sql);
		*/
		return true;
	}

	/**
	 * Retourne le nom du module
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Retourne le titre du module
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Retourne l'ID du module
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Retourne les tables déclarées dans le module
	 * @return DbTable[]
	 */
	public function getDbTables() {
		return $this->dbTables;
	}

	/**
	 * Traite une requête envoyée par API
	 */
	public function runAPI(){

	}
} 