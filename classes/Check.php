<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/04/14
 * Time: 09:34
 */

use Logs\Alert;

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

	/**
	 * Valide un encodage en UTF-8
	 *
	 * @from <http://us2.php.net/manual/fr/function.utf8-encode.php#39932>
	 *
	 * @param string $Str Chaîne à vérifier
	 *
	 * @return bool
	 */
	public static function isUtf8($Str) {
		for ($i=0; $i<strlen($Str); $i++) {
			if (ord($Str[$i]) < 0x80) $n=0; # 0bbbbbbb
			elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif ((ord($Str[$i]) & 0xF0) == 0xF0) $n=3; # 1111bbbb
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n octets that match 10bbbbbb follow ?
				if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80)) return false;
			}
		}
		return true;
	}

	/**
	 * Vérifie qu'une valeur est un nombre flottant ou non, même s'il est de type chaîne (string)
	 *
	 * `is_float()` ne permet que de vérifier si un nombre est un flottant ou non, alors que cette fonction traite également les chaînes de caractères et permet   * `is_float()` ne permet que de vérifier si un nombre est un flottant ou non, alors que cette fonction traite également les chaînes de caractères.
	 *  Elle permet par exemple de récupérer une valeur de formulaire et de savoir comment la typer avec précision.
	 *
	 * @from <http://php.net/manual/fr/function.is-float.php#118169>
	 *
	 * @param string|int|float $value Valeur à tester
	 *
	 * @return bool
	 */
	public static function isFloat($value){
		return ((int)$value != $value) ;
	}

	/**
	 * Vérifie qu'un serveur est joignable par le réseau
	 *
	 * @param string $server Nom ou adresse Ip du serveur
	 *
	 * @return bool
	 */
	public static function isOnline($server){
		//exec('ping -c 1 -W 5 '.$server, $out);
		// Plus rapide que le ping
		exec('fping -c 1 -q '.$server.' 2>&1', $out);
		if (!empty($out)){
			// 172.32.1.125 : xmt/rcv/%loss = 1/1/0%, min/avg/max = 0.48/0.48/0.48
			// 172.32.1.124 : xmt/rcv/%loss = 1/0/100%
			// 172.32.1.124 is unreachable
			//srv-tina : xmt/rcv/%loss = 1/1/0%, min/avg/max = 0.17/0.17/0.17
			preg_match('/\d\/(\d)\/(\d{1,3})\%/i', $out[0], $matches);
			if ($matches[1] == '1') return true;
		}
		return false;
	}

	/**
	 * Vérifie qu'une URL est joignable
	 *
	 * Nécessite curl activé sur le serveur
	 *
	 * @from https://stackoverflow.com/a/7051633/1749967
	 * @param $url
	 *
	 * @return bool
	 */
	public static function isOnlineURL($url) {
		$ch = @curl_init($url);
		@curl_setopt($ch, CURLOPT_HEADER, TRUE);
		@curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		@curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		if(curl_exec($ch)!==FALSE) {
			return true;
		}
		return false;
	}

	/**
	 * Vérifie qu'une valeur est une date valide
	 *
	 * @from https://stackoverflow.com/a/19271434/1749967
	 *
	 * @param string  $date   Date à valider (si timestamp, doit être supérieur à 0 - les dates avant le 01/01/1970 ne sont pas donc pas validées)
	 * @param string  $format Format de la date (`d/m/Y` par défaut)
	 *
	 * @return bool
	 */
	public static function isDate($date, $format = 'd/m/Y'){
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}

	/**
	 *
	 * Compare deux valeurs
	 *
	 * @from https://stackoverflow.com/a/5579231/1749967
	 *
	 * @param $var1
	 * @param $op
	 * @param $var2
	 *
	 * @return bool
	 */
	public static function compare($var1, $op, $var2){
		if (!in_array($op, array('>', '<', '=', '==', '>=', '<=', '!=', '~'))){
			new Alert('error', 'Erreur : l\'opérateur de comparaison <code>'.$op.'</code> est invalide !');
			return false;
		}
		switch ($op) {
			case "="  :
			case "==" : return $var1 == $var2;
			case "!=" : return $var1 != $var2;
			case ">=" : return $var1 >= $var2;
			case "<=" : return $var1 <= $var2;
			case ">"  : return $var1 >  $var2;
			case "<"  : return $var1 <  $var2;
			case "~"  : return (bool)stristr($var2, $var1); // équivalent de like en sql
			default   : return false;
		}
	}
} 