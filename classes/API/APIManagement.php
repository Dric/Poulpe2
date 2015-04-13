<?php
/**
 * Creator: Dric 
 * Date: 24/03/2015
 * Time: 14:48
 */

namespace API;
use Modules\Module;

/**
 * Classe de gestion des APIs
 *
 * @package API
 */
class APIManagement {
	static protected $APIs = array();

	/**
	 * Retourne la liste des API surveillées
	 * @return API[]
	 */
	public static function getAPIs() {
		return self::$APIs;
	}

	/**
	 * Retourne une API
	 *
	 * @param $apiName
	 *
	 * @return API
	 */
	public static function getAPI($apiName){
		return self::$APIs[$apiName];
	}

	/**
	 * Ajoute une API à la liste des API surveillées
	 * @param API $API
	 */
	public static function setAPIs(API $API) {
		self::$APIs[$API->name] = $API;
	}

	/**
	 * Vérifie si une API est appelée
	 */
	public static function checkAPIRequest(){
		$requestURI = htmlspecialchars(trim(str_replace(basename(dirname($_SERVER['PHP_SELF'])), '', trim($_SERVER['REQUEST_URI'], '/')), '/'));
		$reqTab = explode('/', $requestURI);

		if (strtolower($reqTab[0]) == 'api'){
			$requestedAPI = $reqTab[1];
			if (isset(self::$APIs[$requestedAPI])){
				/** @var API $API */
				$API = self::$APIs[$requestedAPI];
				if ($API->isActive){
					$API->populateParams(str_replace('api/', '', $requestURI));
					/** @var Module $module */
					$module = new $API->moduleClass;
					$module->runAPI();
				}
			}
		}
	}

}