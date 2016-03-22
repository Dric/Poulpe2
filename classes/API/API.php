<?php
/**
 * Creator: Dric 
 * Date: 24/03/2015
 * Time: 14:32
 */

namespace API;


use Logs\Alert;
use Modules\ModulesManagement;

/**
 * Classe de déclaration d'une API
 *
 * <h4>Exemples</h4>
 * Pour la suite, nous déclarons une API `traces`.
 * <h5>Déclaration</h5>
 * <code>$API = new API('traces', 'Modules\UsersTraces2\UsersTraces2', ':server/:app/:user/:event/:data');</code>
 *
 * Depuis un module, pour déclarer et activer une API, il suffit de redéfinir la méthode `initModuleLoading()` :
 * <code>
 * public static function initModuleLoading(){
 *  \API\APIManagement::setAPIs(new API('traces', get_class(), 'APIgetEvents', ':server/:app/:user/:event/:data'));
 * }
 * </code>
 * <h5>Usage</h5>
 * La récupération des appels à l'API est automatiquement traitée par la classe `APIManagement`.
 *
 * Il faut définir une méthode `APIgetEvents()` au sein du module dans lequel est déclaré l'API pour pouvoir traiter les requêtes.
 * On peut ainsi définir plusieurs API pour un module grâce au nom de la méthode.
 *
 * Cette méthode pourra récupérer l'API avec `APIManagement::getAPI('traces')` (ou `traces` est le nom déclaré de l'API)
 *
 * On peut accéder aux variables de la requête stockées dans un tableau indexé avec les paramètres de l'API grâce à `$API->params`
 *
 * <code>
 * 'params' =>
 *  array (size=5)
		* 'server' => string 'srv-xatest' (length=10)
		* 'app' => string 'Login' (length=5)
		* 'user' => string 'testxa' (length=6)
		* 'event' => string 'Logon' (length=5)
		* 'data' => string 'success' (length=7)
 * </code>
 *
 * Si `$API->isActive` est à `false`, alors l'API est désactivée et ne fonctionnera pas
 *
 * @property-read bool    $isActive     Vérifie qu'une API est active
 * @property-read string  $name         Nom de l'API
 * @property-read string  $moduleClass  Module propriétaire de l'API
 * @property-read string  $methodName   Nom de la méthode appelée par l'API au sein du module
 * @property-read string  $params       Paramètres de l'API
 * @property-read string  $bypassACL    Passe outre les ACL si `true` pour permettre un accès anonyme
 *
 * @package API
 */
class API {

	/** @var string Nom de l'API */
	protected $name = null;
	/** @var string Classe de module vers laquelle rediriger l'API */
	protected $moduleClass = null;
	/** @var int Version de l'API */
	public $version = 0;
	/** @var bool Passer outre les ACL pour avoir une API accessible en anonyme */
	protected $bypassACL = false;
	/** @var string Fonction appelée par l'API au sein du module */
	protected $methodName = 'runAPI';
	/** @var bool Si `false`, l'API est désactivée car le module vers lequel elle est redirigée n'est pas actif */
	protected $active = false;
	/** @var string Arguments passés dans l'URL */
	protected $queryString = '';
	/** @var string Gabarit de l'API */
	protected $args = '';
	/** @var array Paramètres de l'API */
	protected $params = array();

	/**
	 * Déclaration d'une API
	 *
	 * @param string $name        Nom de l'API
	 * @param string $moduleClass Classe de module vers laquelle rediriger l'API
	 * @param string $methodName  Nom de la méthode appelée par l'API au sein du module. `runAPI` par défaut
	 * @param string $args        Gabarit de l'API (de type :var1/:var2/:var3)
	 * @param bool   $bypassACL   Passe outre les ACL si `true` pour permettre un accès anonyme
	 * @param int    $version     Version de l'API (facultatif)
	 */
	public function __construct($name, $moduleClass, $methodName, $args = '', $bypassACL = false, $version = 1){
		$this->name = $name;
		$activeModules = ModulesManagement::getActiveModules();
		foreach ($activeModules as $activeModule){
			if ($activeModule->class == $moduleClass) {
				$this->moduleClass = $moduleClass;
				$this->active = true;
				break;
			}
		}
		$this->methodName = $methodName;
		$this->bypassACL = (bool)$bypassACL;
		if (!$this->active) {
			new Alert('debug', 'La classe de module <code>'.$moduleClass.'</code> est introuvable dans les modules actifs !');
		}
		$this->setArgs($args);
	}

	/**
	 * Définit le gabarit de l'URL d'appel à l'API
	 *
	 * Pour définir une propriété, il faut précéder son nom de `:`.
	 *
	 * Les propriétés sont séparées par un slash `/`
	 *
	 * Exemple :
	 *  <code>:server/:app/:user/:event/:data</code>
	 *  Va définir les propriétés suivantes : `$server`, `$app`, `$user`, `$event`, `$data`
	 *
	 * @param string $args Gabarit de l'url de l'API
	 *
	 */
	public function setArgs($args = ''){
		$this->args = $args;
		if (!empty($args)){
			$tab = explode('/', $args);
			foreach ($tab as $item){
				if (stripos($item, ':') === 0){
					$item = ltrim($item, ':');
					$this->params[$item] = null;
				}
			}
		}
	}

	/**
	 * Récupère la requête d'API et assigne les valeurs aux paramètres de l'API
	 *
	 * @param string $queryString
	 */
	public function populateParams($queryString){
		$this->queryString = $queryString;
		$tabArgs = explode('/', $this->args);
		$tabQuery = explode('/', $queryString);
		array_shift($tabQuery);
		foreach ($tabArgs as $key => $arg){
			if (stripos($arg, ':') === 0){
				$arg = ltrim($arg, ':');
				if (isset($tabQuery[$key])) $this->params[$arg] = $tabQuery[$key];
			}
		}
	}

	/**
	 * Retourne des propriétés de l'objet API
	 *
	 * @param $arg
	 *
	 * @return mixed|null
	 */
	public function __get($arg){
		switch ($arg){
			case 'isActive':    return $this->active;
			case 'name':        return $this->name;
			case 'moduleClass': return $this->moduleClass;
			case 'methodName' : return $this->methodName;
			case 'params':      return $this->params;
			case 'bypassACL' :  return $this->bypassACL;
			default:            return null;
		}
	}


}