<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 13/05/14
 * Time: 13:34
 */

namespace Forms;


use Logs\Alert;
use Sanitize;
use Users\Login;

/**
 * Classe de récupération des données envoyées par les formulaires
 *
 * Cette classe gère également les jetons de sécurité des formulaires
 *
 * @see Form
 *
 * @package Forms
 */
class PostedData {

	/**
	 * Préfixe des champs récupérés
	 * @var string
	 */
	protected static $prefix = 'field';

	/**
	 * Préfixe des champs de table de bdd récupérés
	 * @var string
	 */
	protected static $dbTablePrefix = 'dbTable';

	/**
	 * Récupère l'envoi d'un formulaire créé via un objet Form et effectue le typage des données retournées
	 *
	 * Les données ne sont pas traitées hors de leur typage, elles ne sont donc pas sécurisées pour un ajout en bdd par exemple.
	 *
	 * Les champs sont au format suivant : `préfixe_type-de-champ_nom-de-variable(_détails)`
	 * Les champs de table de bdd sont au format suivant : `préfixe-dbTable_nom-de-table_type-de-champ_nom-de-variable(_détails)_id-de-la-ligne`
	 * Le tableau retourné comporte les noms des variables en clés. Les tableaux ont un sous-index pour indiquer s'il faut sérialiser les valeurs, et un index `values` pour les valeurs.
	 * Les tables de bdd sont dans un tableau `nom-de-table => array(ligne => array champs)`
	 *
	 * @param string $prefix Préfixe optionnel des champs
	 * @param string $dbTablePrefix Préfixe optionnel des tables de bdd
	 *
	 * @return array
	 */
	public static function get($prefix = null, $dbTablePrefix = null){
		$prefix = (!empty($prefix)) ? $prefix : self::$prefix;
		$dbTablePrefix = (!empty($dbTablePrefix)) ? $dbTablePrefix : self::$dbTablePrefix;
		$ret = array();
		foreach ($_REQUEST as $request => $value){
			$tab = explode('_', $request);
			if (count($tab) > 1 and in_array($tab[0], array($prefix, $dbTablePrefix))){
				if ($tab[0] == $dbTablePrefix){
					$tableName = $tab[1];
					$rowId = $tab[4];
					unset($tab[0]);
					unset($tab[4]);
					$tab = array_values($tab);
				}
				$req = null;
				switch ($tab[1]){
					case 'int':
						$req = (int)$value;
						break;
					case 'float':
						$req = (float)$value;
						break;
					case 'date':
						$req = Sanitize::date($value);
						break;
					case 'bool':
						if ($tab[3] == 'checkbox') {
							unset($_REQUEST[str_replace('_checkbox', '', $request).'_hidden']);
							$req = (bool)$value;
						}elseif ($tab[3] == 'hidden'){
							if (isset($_REQUEST[str_replace('_hidden', '', $request).'_checkbox'])){
								$req = (bool)$_REQUEST[str_replace('_hidden', '', $request).'_checkbox'];
								unset($_REQUEST[str_replace('_hidden', '', $request).'_checkbox']);
							}else{
								$req = (bool)$value;
							}
						}
						break;
					case 'array':
						if (isset($tab[3]) and $value == 1){
							$req['serialize'] = true;
						}elseif (isset($_REQUEST[$request.'_serialize'])){
							if ($_REQUEST[$request.'_serialize'] == 1){
								$req['serialize'] = true;
							}else{
								$req['serialize'] = false;
							}
							unset($_REQUEST[$request.'_serialize']);
						}
						$valueTab = explode(PHP_EOL, $value);
						// Si les valeurs dans le tableau sont numériques, elles récupèrent un type entier
						array_walk($valueTab, function(&$value, $key) {
							$value = trim($value);
							if (is_numeric($value)){
								$value = (int)$value;
							}
						});
						// On enlève les valeurs nulles
						$valueTab = array_filter($valueTab);
						if (!empty($valueTab)) $req['values'] = $valueTab;
						break;
					default:
						switch ($value){
							case 'true':
								$req = true;
								break;
							case 'false':
								$req = false;
								break;
							default:
								$req = $value;
						}
				}
				if ($tab[0] == $prefix){
					// Si on a affaire à un tableau, on fusionne avec le tableau existant
					if ((is_array($req) or $tab[1] == 'array') and isset($ret[$tab[2]])){
						if (!empty($req)) $ret[$tab[2]] = array_merge($req, $ret[$tab[2]]);
					}else{
						$ret[$tab[2]] = ($req !== '') ? $req : null;
					}
				}else{
					$ret[$dbTablePrefix][$tableName][$rowId][$tab[2]] = $req;
				}
			}elseif($request == 'action'){
				$ret['action'] = $value;
			}
		}
		// Vérification du jeton de sécurité - si celui-ci n'est pas bon, on ne renvoie pas les données
		if (isset($ret['token']) and isset($ret['formName']) and self::checkToken($ret['formName'], $ret['token'])){
			unset($ret['token']);
			unset($ret['formName']);
			return $ret;
		}elseif(isset($ret['noToken']) and $ret['noToken']){
			unset($ret['noToken']);
			return $ret;
		}
		return null;
	}

	/**
	 * Vérifie un jeton de sécurité de formulaire
	 *
	 * @param string $formName Nom du formulaire
	 * @param string $token Jeton de sécurité
	 *
	 * @return bool
	 */
	static protected function checkToken($formName, $token){
		self::delOldTokens();
		if (isset($_SESSION[$formName.'_Token'])){
			if ($_SESSION[$formName.'_Token'] == $token){
				unset($_SESSION[$formName.'_Token']);
				return true;
			}
		}
		new Alert('error', 'Erreur de traitement : Ce formulaire a déjà été envoyé, ou bien vous n\'êtes pas la personne qui a initié l\'envoi !');
		return false;
	}

	/**
	 * Crée et renvoie un jeton de sécurité pour éviter les failles CSRF
	 *
	 * @param string $name Nom du formulaire
	 * @return string
	 */
	static public function setToken($name){
		$hash = sha1($name.time());
		$_SESSION[$name.'_Token'] = $hash;
		$_SESSION['tokens'][$name.'_Token'] = time();
		return $hash;
	}

	/**
	 * Supprime les jetons de sécurité qui ont été émis il y a plus de 4 heures
	 */
	static public function delOldTokens(){
		foreach ($_SESSION['tokens'] as $token => $time){
			if ($time < time()-14400){
				unset($_SESSION[$token]);
				unset($_SESSION['tokens'][$token]);
			}
		}
	}

	/**
	 * Supprime les données envoyés par les formulaires de la mémoire
	 *
	 * @param string $prefix Préfixe optionnel des champs
	 * @param string $dbTablePrefix Préfixe optionnel des tables de bdd
	 */
	static public function reset($prefix = null, $dbTablePrefix = null){
		$prefix = (!empty($prefix)) ? $prefix : self::$prefix;
		$dbTablePrefix = (!empty($dbTablePrefix)) ? $dbTablePrefix : self::$dbTablePrefix;
		foreach ($_REQUEST as $request => $value){
			$tab = explode('_', $request);
			if (count($tab) > 1 and in_array($tab[0], array($prefix, $dbTablePrefix))){
				unset($_REQUEST[$request]);
			}
		}
	}
} 