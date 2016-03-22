<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 31/03/14
 * Time: 10:35
 */

namespace Modules;
use Admin\Admin;
use Db\DbTable;
use Logs\Alert;
use Get;
use Profiles\UserProfile;
use Sanitize;
use Settings\Setting;
use Users\ACL;
use Users\CurrentUser;

/**
 * Classe de gestion des modules
 *
 * @package Modules
 */
class ModulesManagement {

	/**
	 * Liste des modules actifs
	 * @var array
	 */
	protected static $activeModules = null;

	/**
	 * Initialise un module
	 * @return Module
	 */
	public static function getModule(){
		if (isset($_REQUEST['module'])){
			// Modules spéciaux :
			if ($_REQUEST['module'] == 'Admin'){
				$module = new Admin();
			}elseif($_REQUEST['module'] == 'profil'){
				$module = new UserProfile();
			}else{
				// Modules normaux
				$requested = htmlspecialchars($_REQUEST['module']);
				$activeModules = self::getActiveModules();
				foreach ($activeModules as $activeModule){
					$class = $activeModule->class;
					$tab = explode('\\', $class);
					if (end($tab) == $requested){
						$module = new $class;
					}
				}
				if (empty($module)) new Alert('error', 'Le module demandé n\'existe pas !');
			}
		}
		// Affichage du module par défaut
		if (!isset($module)) {
			if (HOME_MODULE == 'home') {
				$module = new Module();
			}else{
				$class = 'Modules\\'.HOME_MODULE.'\\'.HOME_MODULE;
				$module = new $class();
			}

		}
		return $module;
	}

	/**
	 * Retourne les modules actifs dans la base de données
	 *
	 * @param bool $refresh Forcer le rafraîchissement des modules actifs
	 *
	 * @return object
	 */
	public static function getActiveModules($refresh = false){
		global $db;
		if (empty(self::$activeModules) or $refresh) {
			self::$activeModules = $db->query('SELECT * FROM modules');
		}
		return self::$activeModules;
	}

	/**
	 * Vérifie si un module est activé
	 *
	 * @param string $moduleClass Classe du module
	 * @return bool
	 */
	public static function isActiveModule($moduleClass){
		foreach (self::getActiveModules() as $activeModule){
			if ($moduleClass == $activeModule->class) return true;
		}
		return false;
	}

	/**
	 * Retourne le chemin du fichier principal d'un module
	 * @param string $moduleName Nom du module
	 *
	 * @return string
	 */
	protected static function modulePath($moduleName){
		global $db;
		return $db->getVal('modules', 'path', array('name' => $moduleName));
	}


	/**
	 * Exécute toutes les commandes sql liées à l'installation d'un module.
	 *
	 * @param array|string $sql Commandes SQL à lancer. Si plusieurs commandes sont à lancer, il faut les passer via un tableau.
	 *
	 * @return bool
	 */
	public static function dbSetup($sql){
		global $db;
		if (!empty($sql)){
			if (!is_array($sql)) $sql = array($sql);
			foreach ($sql as $query){
				$ret = $db->query($query);
				if ($ret === false){
					new Alert('debug', '<code>ModulesManagement::dbSetup()</code> : Les commandes SQL d\'installation du module ont échoué !');
					return false;
				}
			}
			return true;
		}else{
			new Alert('debug', '<code>ModulesManagement::dbSetup()</code> : Aucune commande SQL à passer !');
			return false;
		}
	}

	/**
	 * Active un module dans la bdd
	 * @param Module $module
	 *
	 * @return bool|int false si erreur, l'ID du module en cas de succès
	 */
	public static function activateModule($module){
		global $db;
		return $db->insert('modules', array('name' => str_replace('\'', '&#39;', $module->getName()), 'class' => get_class($module)));
	}

	/**
	 * Désactive un module dans la bdd
	 * @param Module $module
	 *
	 * @return bool
	 */
	public static function disableModule($module){
		global $db;
		$db->delete('ACL', array('component' => 'module', 'id' => $module->getId()));
		// Grâce à la magie des clés étrangères, les paramètres de modules seront également supprimés !
		return $db->delete('modules', array('id' => $module->getId()));
	}

	/**
	 * Récupère les items de chaque module à ajouter au menu principal
	 */
	public static function getModulesMenuItems(){
		global $cUser;
		$modules = self::getActiveModules();
		foreach ($modules as $module){
			if (ACL::canAccess('module', $module->id, $cUser->getId())){
				/**
				 * @var Module $class
				 */
				$class = $module->class;
				$class::getMainMenuItems();
			}
		}
	}

	/**
	 * Charge les éventuels traitements globaux des modules
	 */
	public static function initModulesLoading(){
		$modules = self::getActiveModules();
		foreach ($modules as $module){
			/** @var Module $class */
			$class = $module->class;
			$class::initModuleLoading();
		}
	}

	/**
	 * Sauvegarde les paramètres d'un module
	 * @param Module $Module Module dont on veut sauvegarder les paramètres
	 * @param array  $settings Tableau d'objets Setting
	 * @param CurrentUser $User Utilisateur qui sauvegarde (facultatif)
	 *
	 * @return bool
	 */
	public static function settingsSave($Module, array $settings, $User = null){
		global $db;
		if (!($Module instanceof Module)){
			new Alert('debug', '<code>ModulesManagement::settingsSave()</code> : $Module n\'est pas un objet Module !<br >'. Get::varDump($Module));
			return false;
		}
		if (!empty($User) and !($User instanceof CurrentUser)){
			new Alert('debug', '<code>ModulesManagement::settingsSave()</code> : $User n\'est pas un objet User !<br >'. Get::varDump($User));
			return false;
		}

		foreach ($settings as $setting){
			if ($setting instanceof Setting){
				if ($setting->getName() == 'AllowUsersSettings' and $setting->getValue() === false){
					if (!self::delUsersSettings($Module)) new Alert('debug', '<code>ModulesManagement::settingsSave()</code> : Impossible de supprimer les paramètres définis par les utilisateurs');
				}

			}else{
				new Alert('debug', '<code>ModulesManagement::settingsSave()</code> : Un des paramètres n\'est pas un objet Setting !<br >'. Get::varDump($setting));
				return false;
			}
		}
		return true;
	}

	/**
	 * Installe un module
	 *
	 * @param Module $module Module à installer
	 * @param array $acl Tableau de l'autorisation par défaut :
	 *  - `type` (`access`, `modify` ou `admin`)
	 *  - `value` booléen
	 * @param string $sql Commandes SQL à passer
	 *
	 * @return bool
	 */
	public static function installModule(Module $module, $acl = array(), $sql = null){
		// Définition des paramètres du module
		$module->defineSettings();
		if ($module->getDbTables()){
			/**
			 * @var DbTable $table
			 */
			foreach ($module->getDbTables() as $table){
				$ret = $table->createInDb();
				if (!$ret){
					new Alert('error', 'Impossible de créer la table <code>'.$table->getName().'</code> liée au module <code>'.$module->getName().'</code> !');
					return false;
				}
			}
			new Alert('success', 'Les tables du module <code>'.$module->getName().'</code> ont été créées !');
		}
		if (!empty($sql)){
			if (!self::dbSetup($sql)) {
				return false;
			}else{
				new Alert('success', 'Les commandes SQL liées à l\'installation du module <code>'.$module->getName().'</code> se sont correctement déroulées !');
			}
		}

		if (!$module->saveDbSettings()){
			return false;
		}else{
			new Alert('success', 'Les paramètres du module <code>'.$module->getName().'</code> ont été correctement sauvegardés !');
		}
		if (!ACL::set('module', $module->getId(), 10000, $acl['type'], $acl['value'])){
			return false;
		}else{
			new Alert('success', 'Les autorisations du module <code>'.$module->getName().'</code> ont été correctement sauvegardés !');
		}
		return true;
	}

	/**
	 * Supprime les valeurs de paramètres définies par les utilisateurs
	 *
	 * @param Module $Module Module dont on veut effacer les paramètres définis par les utilisateurs
	 * @param CurrentUser $User Utilisateur (facultatif) - Si non renseigné, on supprime les paramètres définis par tous les utilisateurs sur ce module
	 *
	 * @return bool
	 */
	public static function delUsersSettings($Module, $User = null){
		global $db;
		if (!($Module instanceof Module)){
			new Alert('debug', '<code>ModulesManagement::settingsSetup()</code> : $Module n\'est pas un objet Module !<br >'. Get::varDump($Module));
			return false;
		}
		if (!empty($User) and !($User instanceof CurrentUser)){
			new Alert('debug', '<code>ModulesManagement::settingsSetup()</code> : $User n\'est pas un objet User !<br >'. Get::varDump($User));
			return false;
		}
		$where = array('module' => $Module->getId());
		if (!empty($User)) $where['user'] = $User->getId();
		return $db->delete('modules_user_settings', $where);
	}
} 