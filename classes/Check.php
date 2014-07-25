<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/04/14
 * Time: 09:34
 */

/**
 * Class Check
 * Contient les fonctions de test
 *
 * @package Check
 */
class Check {

	/**
	 * Détermine si un tableau est associatif ou non
	 * Ex :
	 * - array('pouet', 'canard') est séquentiel
	 * - array(1 => 'plouf', 2 => 'pouet') est séquentiel
	 * - array(1 => 'canard', 'id' => 2) est associatif
	 * @from <http://stackoverflow.com/a/4254008/1749967>
	 * @param array $array Tableau
	 *
	 * @return bool
	 */
	public static function isAssoc($array) {
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}

	/**
	 * Valide une adresse IP
	 *
	 * @param string $ipAddr Adresse IP
	 * @return bool
	 */
	public static function isIpAddress($ipAddr) {
		$long = ip2long($ipAddr);
		return !($long == -1 || $long === FALSE);
	}

	/**
	 * Valide une adresse email
	 *
	 * Cette méthode utilise la fonction php filter_vars(), ce qui peut d'après les commentaires provoquer de mauvaises vérifications
	 *
	 * @use filter_var()
	 *
	 * @param string $email Adresse email à vérifier
	 * @return bool
	 */
	public static function isEmail($email){
		return (filter_var($email, FILTER_VALIDATE_EMAIL) === false) ? false : true;
	}

	/**
	 * Equivalent de in_array() dans un tableau multi-dimensionnel
	 *
	 * @param string $elem Valeur à trouver
	 * @param array $array Tableau dans lequel chercher
	 *
	 * @return bool
	 */
	public static function inMultiArray($elem, $array) {
		foreach ($array as $value){
			if (is_array($value) and self::inMultiArray($elem, ($value))) return true;
			if ($value === $elem) return true;
		}
		return false;
	}

} 