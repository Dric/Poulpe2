<?php
/**
 * Creator: Dric 
 * Date: 24/03/2015
 * Time: 14:48
 */

namespace API;
use Front;
use Modules\Module;
use Modules\ModulesManagement;

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
	 *
	 * Cette méthode est appelée via index.php à chaque chargement de page
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
					$moduleName = $API->moduleClass;
					if (ModulesManagement::isActiveModule($moduleName, true)){
						header('Content-Type: application/json; charset=utf-8');
						/** @var Module $module */
						$module = new $moduleName($API->bypassACL);
						$module->{$API->methodName}();
						exit();
					}
				}
			}
		}
	}

	/**
	 * Envoie une requête vers une API via cURL (sorte de requête asynchrone avec PHP)
	 *
	 * Si l'url commence par `api/`, elle est automatiquement complétée par l'url de base des POIs
	 *
	 * @param string $url URL de la requête vers l'API
	 *
	 * @return array
	 */
	public static function sendAPIRequest($url){
		if (ltrim(substr($url, 0, 4), '/') == 'api/'){
			$url = Front::getBaseUrl().'/'.ltrim($url, '/');
		}
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// On fait passer le cookie d'authentification
		curl_setopt($curl, CURLOPT_COOKIE, \Settings::COOKIE_NAME.'='.urlencode($_COOKIE[\Settings::COOKIE_NAME]));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		$curlResult = curl_exec($curl);
		curl_close($curl);
		return json_decode($curlResult, true);
	}
}