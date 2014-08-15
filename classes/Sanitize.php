<?php
/**
 * Fonctions de base de transformations de chaînes, dates, int, etc.
 *
 * User: cedric.gallard
 * Date: 18/03/14
 * Time: 16:16
 *
 */

/**
 * Classe de fonctions de transformation de données
 *
 * Fonctions de base de transformations de chaînes, dates, int, etc.
 *
 * @package Sanitize
 */
class Sanitize {

	/**
	 * Supprime tout caractère louche, accentué ou autre d'une chaîne
	 *
	 * @from <www.house6.com/blog/?p=83>
	 * @param string $f Chaîne à traiter
	 * @param bool $toLower Retourner la chaîne en minuscules
	 *
	 * @return string
	 */
	static function sanitizeFilename($f, $toLower = true) {
		// a combination of various methods
		// we don't want to convert html entities, or do any url encoding
		// we want to retain the "essence" of the original file name, if possible
		// char replace table found at:
		// http://www.php.net/manual/en/function.strtr.php#98669
		$replace_chars = array(
			'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
			'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
			'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
			'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
			'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
			'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
			'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
		);
		$f = strtr($f, $replace_chars);
		// convert & to "and", @ to "at", and # to "number"
		$f = preg_replace(array('/[\&]/', '/[\@]/', '/[\#]/'), array('-and-', '-at-', '-number-'), $f);
		$f = preg_replace('/[^(\x20-\x7F)]*/','', $f); // removes any special chars we missed
		$f = str_replace(' ', '-', $f); // convert space to hyphen
		$f = str_replace('\'', '', $f); // removes apostrophes
		$f = preg_replace('/[^\w\-\.]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
		$f = preg_replace('/[\-]+/', '-', $f); // converts groups of hyphens into one
		if ($toLower)	return strtolower($f);
		return $f;
		}

	/**
	 * Transforme un tableau en chaîne de caractères
	 * (typiquement pour une sauvegarde en bdd)
	 *
	 * Si le tableau est associatif, alors il sera sérialisé pour conserver les clés.
	 *
	 * @param array  $array Tableau
	 * @param string $glue Chaîne de liaison entre les valeurs
	 *
	 * @param bool|string $sanitizeFor Met en forme les valeurs du tableau. Peut prendre les valeurs suivantes :
	 *  - false (défaut)
	 *  - `db`
	 *  - `js`
	 *  - `file`
	 *
	 * @return string
	 */
	public static function arrayToString(array $array, $glue = ', ', $sanitizeFor = false){
		if ($sanitizeFor !== false){
			if ($sanitizeFor == 'db'){
				foreach ($array as &$value){
					$value = self::SanitizeForDb($value, false);
				}
			}
			if ($sanitizeFor == 'js'){
				foreach ($array as &$value){
					$value = self::SanitizeForJs($value);
				}
			}
			if ($sanitizeFor == 'file'){
				foreach ($array as &$value){
					$value = self::sanitizeFilename($value);
				}
			}
		}
		if (Check::isAssoc($array)){
			return serialize($array);
		}else{
			return implode($glue, $array);
		}
	}

	/**
	 * Transforme un format de date en un autre
	 *
	 * Cette méthode accepte soit un timestamp soit une date normale en entrée
	 *
	 * @param int|string $date Date à transformer
	 * @param string $to Format de retour
	 * - timestamp
	 * - date française
	 * - dateTime française (date et heure)
	 * - fullDateTime française (date, heure, minutes, secondes)
	 * - dateAtTime française (date et heure du type 24/12/2014 à 08h45)
	 * - time Heure, minutes et secondes
	 *
	 * @return bool|int|string|null
	 */
	public static function date($date, $to = 'timestamp'){
		date_default_timezone_set('Europe/Paris');
		if (empty($date)) return null;
		switch ($to){
			case 'timestamp':
				// Au cas où la date soit déjà un timestamp
				if (is_numeric($date)) return $date;
				return strtotime($date);
			case 'date' :       return date('d/m/Y', $date);
			case 'dateTime':    return date('d/m/Y H:i', $date);
			case 'fullDateTime':    return date('d/m/Y H:i:s', $date);
			case 'dateAtTime':  return date('d/m/Y à H:i', $date);
			case 'time' :       return date('H:i:s', $date);
			default:            return false;
		}
	}

	/**
	 * Transforme un format d'heure en un autre
	 *
	 * Cette méthode accepte soit une durée en secondes depuis minuit, soit une heure formatée en hh:mm ou hh:mm:ss
	 *
	 * @param int|string  $time Heure à transformer
	 * @param string      $to   Format de retour
	 *  - `timestamp` nombre de secondes écoulées depuis minuit
	 *  - `time`      hh:mm
	 *  - `fullTime`  hh:mm:ss
	 *
	 * @return bool|int|null|string
	 */
	public static function time($time, $to = 'timestamp'){
		if (empty($time)) return null;
		switch ($to){
			case 'timestamp':
				// Au cas où l'heure soit déjà un timestamp
				if (is_numeric($time)) return $time;
				$parsed = date_parse($time);
				return (int)($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second']);
			case 'time' :       return gmdate('H:i', $time);
			case 'fullTime':    return gmdate('H:i:s', $time);
			default:            return false;
		}
	}

	/**
	 * Transforme unee durée en secondes en durée lisible par l'être humain
	 *
	 * @param int $time Durée en secondes
	 * @return string
	 */
	public static function timeDuration($time){
		$s = $time % 60;
		$m = (floor(($time%3600)/60)>0)?floor(($time%3600)/60) : '';
		if (!empty($m)) $m .= ' minute'.(($m != 1) ? 's' : '');
		$h = (floor(($time % 86400) / 3600)>0)?floor(($time % 86400) / 3600) : '';
		if (!empty($h)) $h .= ' heure'.(($h != 1) ? 's' : '');
		$d = (floor(($time % 2592000) / 86400)>0)?floor(($time % 2592000) / 86400) : '';
		if (!empty($d)) $d .= ' jour'.(($d != 1) ? 's' : '');
		$M = (floor($time / 2592000)>0)?floor($time / 2592000).' mois' : '';
		return $M.' '.$d.' '.$h.' '.$m.' et '.$s.' seconde'.(($s != 1) ? 's': '');
	}

	/**
	 * Trie un tableau d'objets selon les propriétés de ceux-ci.
	 *
	 * Le tableau d'origine n'est pas affecté
	 *
	 * @param array $arrayOrig Tableau d'objets à trier
	 * @param array $props Tableau contenant les propriétés sur lesquelles faire le tri
   * @param string $sortOrder
	 *
	 * @return array Tableau trié
	 */
	public static function sortObjectList($arrayOrig, $props, $sortOrder = 'ASC')	{
		$array = $arrayOrig;

		if (!is_array($props)){
			$props = array($props);
		}
		if (!is_array($sortOrder)){
			$sortOrder = array($sortOrder);
		}
		usort($array, function($a, $b) use (&$props, &$sortOrder) {
			foreach ($props as $i => $prop) {
				if (isset ($sortOrder[$i]) and $sortOrder[$i] == 'DESC'){
					if (isset($a->$prop)){
						if (is_numeric($a->$prop) and is_numeric($b->$prop)){
							return $b->$prop - $a->$prop;
						}
						return strcasecmp($b->$prop, $a->$prop);
					}else{
						if (is_numeric($a->{'get'.ucfirst($prop)}()) and is_numeric($b->{'get'.ucfirst($prop)}())){
							return $b->{'get'.ucfirst($prop)}() - $a->{'get'.ucfirst($prop)}();
						}
						return strcasecmp($b->{'get'.ucfirst($prop)}(), $a->{'get'.ucfirst($prop)}());
					}
				}else{
					if (isset($a->$prop)){
						if (is_numeric($a->$prop) and is_numeric($b->$prop)){
							return $a->$prop - $b->$prop;
						}
						return strcasecmp($a->$prop, $b->$prop);
					}else{
						if (is_numeric($a->{'get'.ucfirst($prop)}()) and is_numeric($b->{'get'.ucfirst($prop)}())){
							return $a->{'get'.ucfirst($prop)}() - $b->{'get'.ucfirst($prop)}();
						}
						return strcasecmp($a->{'get'.ucfirst($prop)}(), $b->{'get'.ucfirst($prop)}());
					}
				}

			}
			return 0;
		});
		return $array;
	}

	/**
	 * Met en forme une valeur pour l'enregistrer en bdd
	 *
	 * @param mixed $value Valeur à mettre en forme
	 * @param bool  $quotes Entoure la valeur avec des guillemets si c'est une chaîne.
	 *
	 * @return string
	 */
	public static function SanitizeForDb($value, $quotes = true){
		if (is_array($value)){
			$value = Sanitize::arrayToString($value, ', ', 'db');
		}elseif (is_bool($value)){
			$value = ($value) ? 1 : 0;
		}else{
			$value = str_replace('\\', '\\\\', $value);
			$value = htmlspecialchars($value);
		}
		if (!is_numeric($value) and $quotes) $value = '\''.$value.'\'';
		return $value;
	}

	/**
	 * Met en forme une chaîne de caractères pour un affichage via javascript
	 * @param string $text Chaîne de caractères
	 *
	 * @return string
	 */
	public static function SanitizeForJs($text){
		$text = str_replace('\'', '&#39;', $text);
		$text = str_replace(PHP_EOL, '<br>', $text);
		return $text;
	}

	/**
	 * Transforme un timestamp Active Directory en timestamp unix
	 *
	 * @see <http://stackoverflow.com/a/4647445/1749967>
	 * @see <http://maxvit.net/convert_ldap_dates>
	 * @param string $date Timestamp Active directory
	 * @return int
	 */
	public static function ADToUnixTimestamp($date){
		if (strpos($date, '.') === false){
			$win_secs = substr($date,0,strlen($date)-7); // divide by 10 000 000 to get seconds
			return ($win_secs - 11644473600); // 1.1.1600 -> 1.1.1970 difference in seconds
		}else{
			// La date dans l'AD est déjà réglée selon le fuseau horaire dans lequel se trouve le serveur, pour obtenir un timestamp correct, il faut remettre tricher avec les fuseaux horaires
			date_default_timezone_set('UTC');
			$tab = explode('.', $date);
			list($millenium, $year, $month, $day, $hours, $minutes, $seconds) = str_split($tab[0], 2);
			$time = mktime($hours, $minutes, $seconds, $month, $day, $millenium.$year);
			date_default_timezone_set('Europe/Paris');
			return $time;
		}
	}

	/**
	 * Converti une valeur en octets en taille lisible (Mo, Go, etc.)
	 *
	 * @param int $size Taille en octets
	 * @param int $NbDecimals Nombre de décimales après la virgule
	 *
	 * @return string
	 */
	public static function readableFileSize($size, $NbDecimals = 2){
		if ($size == 0) return 'Vide';
		$siPrefix = array( 'o', 'Ko', 'Mo', 'Go', 'To', 'Eo', 'Zo', 'Yo' );
		$base = log(floatval($size)) / log(1024);
		return round(pow(1024, $base - floor($base)), $NbDecimals) . $siPrefix[floor($base)];
	}

	/**
	 * Transforme un type PHP en type SQL
	 * @param string $type Type à transformer
	 *
	 * @see <http://dev.mysql.com/doc/refman/5.0/fr/numeric-type-overview.html>
	 *
	 * @return string
	 */
	public static function PHPToSQLType($type){
		switch ($type){
			case 'string' : return 'varchar';
			default: return $type;
		}
	}

	/**
	 * Transforme une chaîne en mettant en majuscule les premières lettre de chaque mot.
	 *
	 * Utile pour mettre en forme les prénoms
	 *
	 * @param string $string Chaîne à mettre en forme
	 *
	 * @return string
	 */
	public static function ucname($string) {

		$string =ucwords(strtolower($string));
		foreach (array('-', '\'') as $delimiter) {
			if (strpos($string, $delimiter)!==false) {
				$string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
			}
		}
		return $string;
	}

}