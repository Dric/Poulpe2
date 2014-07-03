<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 31/03/14
 * Time: 09:38
 */

namespace Modules;
use Logs\Alert;
use Components\Help;
use Components\Item;
use Components\Menu;
use Front;
use Get;
use Sanitize;
use Settings\Field;
use Settings\Form;
use Settings\PostedData;
use Settings\Setting;
use Users\ACL;

/**
 * Class Module
 *
 * Modèle de module
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
	protected $title = 'Petits Outils Informatiques';

	/**
	 * Chemin du module - définit automatiquement à l'instantiation du module
	 * @var string
	 */
	protected $path = '';

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

	protected $url = '';
	/**
	 * Instantiation du module
	 */
	public function __construct(){
		// On vérifie si le module est activé dans la bdd
		if (!$this->inDb() and !in_array($this->name, array('home', 'admin', 'userProfile'))){
			// S'il ne l'est pas, on l'installe (s'il est demandé et non actif, c'est forcément qu'on veut l'installer)
			new Alert('info', 'Installation du module <code>'.$this->name.'</code>');
			$this->id = ModulesManagement::activateModule($this);
			if (!$this->install()) ModulesManagement::disableModule($this);
		}
		if ($this->name != 'home'){
			$this->checkACL();
		}
		// Fil d'Ariane. Si la page demandée est l'accueil, on ne la raffiche pas étant donné qu'elle est systématiquement indiquée
		if ($this->name != 'home'){
			$this->url = MODULE_URL.end(explode('\\', get_class($this)));
			$this->breadCrumb = array(
				'title' => $this->name,
				'link'  => $this->url
			);
		}
		// Création du menu si besoin
		$this->moduleMenu();
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
	 * @return bool
	 */
	protected function getPage(){
		$component = (isset($this->type)) ? $this->type : 'module';
		if (isset($_REQUEST['page'])){
			switch ($_REQUEST['page']){
				default:
					if (method_exists($this, $component.ucfirst($_REQUEST['page']))) {
						$this->breadCrumb['children'] = array(
							'title' => $_REQUEST['page'],
							'link'  => $this->url.'&page='.$_REQUEST['page']
						);
						Front::displayBreadCrumb($this->breadCrumb);
						$this->{$component.ucfirst($_REQUEST['page'])}();
						return true;
					}
					new Alert('error', 'La page demandée n\'existe pas !');
			}
		}
		return false;
	}

	/**
	 * Affiche la page d'accueil du module
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
				<p>
					Ces petits outils sont là pour faciliter la vie des informaticiens.<br />
				</p>
				<h2>Notice d'utilisation</h2>
				<ul>
					<li>
						Si vous avez un doute quand à ce qu'il convient de faire, n'hésitez pas à passer la souris sur les petits symboles d'information :  <?php Help::iconHelp('Et si vraiment vous êtes paumé(e), demandez à Cédric'); ?><br />
					</li>
					<li>Certains modules peuvent être paramétrés pour vos besoins. Il vous suffit pour cela de cliquer sur le bouton <a href="#" class="btn btn-default btn-xs" title="Inutile de cliquer sur ce bouton, il ne vous emmènera nulle part..."><span class="glyphicon glyphicon-cog"></span> Paramètres</a> qui apparaît à côté du titre du module.</li>
					<li>Ce produit ne convient pas aux fosses septiques.</li>
					<li>Vous pouvez retourner sur le <a href="http://glpi" title="L'Intranet est une légende urbaine de la fin du XXè siècle. Aucune donnée fiable ne permet aujourd'hui d'affirmer qu'une telle chose existe.">Petit Portail Informatique</a> en cliquant sur <code>Portail</code> dans le menu à gauche, et vous pouvez vous déconnecter en cliquant sur <code class="tooltip-bottom" title="Captain Obvious à la rescousse !">Déconnexion</code>.</li>
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
		/* Exemple de paramétrage :

		$this->settings['vbsPath'] = new Field('vbsPath', 'string', 'global', null, 'Chemin des scripts de lancement VBS', '\\intra.epsi.fr\profils\xen\xenlogin', null, null, null, null, true);

		// @see \Db\Db->createTable pour plus de détails sur la création d'une table.

		$this->dbTables['module_applis'] = array(
			'name'        => 'module_applis',
			'fields'      => array(
				'id'    => array(
					'type'          => 'int',
					'length'        => 11,
					'null'          => false,
					'autoIncrement' => true,
				),
				'label' => array(
					'type'    => 'string',
					'length'  => 150,
					'null'    => false
				),
				'title' => array(
					'type'    => 'string',
					'length'  => 150,
					'null'    => false
				),
				'file'  => array(
					'type'    => 'string',
					'length'  => 255,
					'null'    => false
				)
			),
			'primaryKey'  => 'id',
			'uniqueKey'   => 'label'
		);
		*/
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
	 * Affiche les boutons de gestion des ACl et des paramètres
	 */
	protected function manageModuleButtons(){
		if (!empty($this->settings) and (ACL::canAdmin('module', $this->id) or $this->allowUsersSettings)) {
			?>&nbsp;<a class="settingsButton btn btn-default btn-xs" title="Paramètres du module" href="<?php echo $this->url; ?>&page=settings"><span class="glyphicon glyphicon-cog"></span> Paramètres</a><?php
		}
		if (ACL::canAdmin('module', $this->id)){
			?>&nbsp;<a class="ACLButton btn btn-default btn-xs" title="Autorisations du module" href="<?php echo $this->url; ?>&page=ACL"><span class="glyphicon glyphicon-user"></span> Autorisations</a><?php
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
				$form = new Form($this->name.'Settings', null, array('fields' => $this->settings, 'userSettings' => true));
				$form->addField(new Field('usersSettings', 'hidden', 'global', 'true'));
				$form->addField(new Field('action', 'button', 'global', 'saveSettings', 'Sauvegarder', null, null, null, null, null, false, null, null, 'btn-primary'));
				$form->addField(new Field('cancel', 'linkButton', 'global', $this->url, 'Revenir au module', null, null, 'Annuler et revenir au module'));
				$form->display();
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
					$form->addField(new Field('allowUsersSettings', 'bool', 'global', $this->allowUsersSettings, 'Autoriser les utilisateurs à personnaliser certains paramètres', null, array('switch' => true, 'size' => 'small')));
				}
				$form->addField(new Field('action', 'button', 'global', 'saveSettings', 'Sauvegarder', null, null, null, null, null, false, null, null, 'btn-primary'));
				$form->addField(new Field('cancel', 'linkButton', 'global', $this->url, 'Revenir au module', null, null, 'Annuler et revenir au module'));
				$form->display();
				?>
			</div>
			<?php } ?>
		</div>

		<?php
	}

	protected function moduleACL(){
		ACL::adminACL('module', $this->id, 'le module '.$this->name);
	}

	/**
	 * Récupère l'envoi du formulaire des paramètres et sauvegarde le tout
	 * @return bool
	 */
	protected function saveSettings(){
		if ((!$this->allowUsersSettings and !ACL::canModify('module', $this->id)) or ($this->allowUsersSettings and !ACL::canAccess('module', $this->id))){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$tableToSave = array();
		$req = PostedData::get();
		$usersSettings = false;
		if (isset($req['usersSettings'])){
			$usersSettings = true;
			unset($req['usersSettings']);
		}
		if (isset($req['allowUsersSettings'])){
			$this->allowUsersSettings = (bool)$req['allowUsersSettings'];
			unset($req['allowUsersSettings']);
		}
		foreach ($req as $field => $value){
			if ($field == 'dbTable'){
				foreach ($value as $tableId => $tableRow){
					$table = $this->settings[$tableId]->getValue();
					// On supprime les valeurs nulles du tableau
					$tableRow = array_filter(array_map('array_filter', $tableRow));
					$tableToSave[$table] = $tableRow;
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
		if ($ret === false) return false;
		if (!empty($tableToSave)) $ret = $this->saveDbTables($tableToSave);
		if ($ret === false) return false;
		return true;
	}

	/**
	 * Sauvegarde des items d'une table dans la bdd
	 * @param array $tables
	 *  - array 'table' La table doit être définie dans $this->dbTables
	 *    - array 'ligne' ('id' => array('field1' => value, 'field2' => value2, etc))
	 *
	 * @see \Db\Db->createTable pour la structure des tables dans $this->dbTables
	 * @return bool
	 */
	protected function saveDbTables(array $tables){
		global $db;
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		foreach ($tables as $table => $lines){
			if (!isset($this->dbTables[$table])){
				new Alert('debug', '<code>Module->saveDbTables()</code> : La table <code>'.$table.'</code> n\'existe pas !');
				return false;
			}
			$itemsIdsDb = $db->get($table, 'id');

			$itemsToDelete = array();
			foreach ($itemsIdsDb as $itemId){
				$itemsToDelete[] = $itemId->id;
			}
			$sql = 'INSERT INTO `'.$table.'` (';
			// Définissons un peu les colonnes à mettre à jour
			foreach ($this->dbTables[$table]['fields'] as $field => $args){
				$sql .= '`'.$field.'`, ';
			}
			$sql = rtrim($sql, ', ').') VALUES ';
			// Les valeurs à présent
			foreach ($lines as $line => $items){
				$sql .= '(';
				// Si l'ID n'est pas affichée (et donc pas renvoyée par le formulaire), il va falloir la renseigner quand même via le nom du champ renvoyé
				if (!isset($items['id'])){
					if ($line != 'new' or empty($line)){
						$sql .= $line.', ';
						// On enlève l'id du tableau des ids d'items, afin de pouvoir supprimer les lignes restantes
						unset($itemsToDelete[array_search($line, $itemsToDelete)]);
					}else{
						// Si l'ID est 'new', c'est un nouveau champ. Pour laisser faire l'auto-incrémentation, on envoie une valeur nulle
						$sql .= 'NULL, ';
					}
				}
				// Valeurs de chaque colonne pour une ligne
				foreach ($this->dbTables[$table]['fields'] as $field => $args){
					// On vient de traiter l'id juste avant la boucle, on l'enlève donc des champs à traiter
					if (!isset($items['id']) and $field != 'id'){
						$sql .= ((isset($items[$field])) ? '"'.str_replace('\\', '\\\\', $items[$field]).'"' : 'NULL').', ';
					}
				}
				$sql = rtrim($sql, ', ');
				$sql .= '),';
			}
			$sql = rtrim($sql, ', ');
			// Évidemment, on va trouver des enregistrements déjà présents. Grâce à 'onDuplicateKeyUpdate', on va savoir quels colonnes mettre à jour en cas d'enregistrements déjà présents
			if (isset($this->dbTables[$table]['onDuplicateKeyUpdate'])){
				$updates = $this->dbTables[$table]['onDuplicateKeyUpdate'];
				$sql .= ' ON DUPLICATE KEY UPDATE ';
				if (!is_array($updates)){
					$sql .= '`'.$updates.'` = VALUES(`'.$updates.'`)';
				}else{
					foreach ($updates as $update){
						$sql.= '`'.$update.'` = VALUES(`'.$update.'`), ';
					}
					$sql = rtrim($sql, ', ');
				}
			}
			// Enfin, on exécute la requête SQL.
			$ret = $db->query($sql);
			// On supprime les enregistrements qui n'ont pas été renvoyés par le formulaire, ce qui signifie qu'ils ont été effacés.
			$ret2 = true;
			if (!empty($itemsToDelete)){
				foreach ($itemsToDelete as $item){
					$ret2 = $db->delete($table, array('id'=>$item));
				}
			}
			if ($ret === false or $ret2 === false){
				new Alert('error', 'Impossible de sauvegarder les enregistrements de la table <code>'.$table.'</code> !');
			}else{
				new Alert('success', 'Les enregistrements de la table <code>'.$table.'</code> ont été mis à jour.');
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
		$this->settings['allowUsersSettings'] = new Setting('allowUsersSettings', 'bool', 'global', $this->allowUsersSettings, null, true);
		$settingsArr = $userSettingsArr = array();
		// On parcoure tous les paramètres du module
		foreach ($this->settings as $setting){
			if ($setting instanceof Setting){
				$value = Sanitize::SanitizeForDb($setting->getValue());
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
	 */
	public function install(){
		/* Ceci est un exemple d'installation de module
		* Ne pas oublier de déclarer les utilisations de classes en haut du fichier :
		* - use Modules\Module;
		* - use Modules\ModulesManagement;
		* - use Settings\Setting;
		* - use Users\ACL;
		*
		// On renseigne le chemin du module
		$this->path = basename(__DIR__).DIRECTORY_SEPARATOR.basename(__FILE__);

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
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return array
	 */
	public function getDbTables() {
		return $this->dbTables;
	}
} 