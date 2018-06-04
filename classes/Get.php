<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/04/14
 * Time: 09:38
 */

/**
 * Classe de fonctions d'obtention de données
 *
 * @package Get
 */
class Get {

	/**
	 * Retourne les paramètres d'une url sous forme de tableau associatif (param => value)
	 *
	 * @from <http://www.php.net/manual/fr/function.parse-url.php#104527>
	 * @param  string $url URL à traiter
	 * @return array
	 */
	public static function urlParamsToArray($url) {
		$query = parse_url($url, PHP_URL_QUERY);
		$queryParts = explode('&', $query);
		$params = array();
		foreach ($queryParts as $param) {
			$item = explode('=', $param);
			if (isset($item[1])) $params[$item[0]] = $item[1];
		}
		return $params;
	}

	/**
	 * Retourne un tableau avec les objets trouvés dans une liste d'objets
	 *
	 * @from <http://stackoverflow.com/a/11648522/1749967>
	 * @param array $objects Liste des objets
	 * @param string $key Propriété sur laquelle effectuer la recherche (les propriétés peuvent être accessibles via un getter - ex : pour 'id' -> $object->getId())
	 * @param mixed $value Valeur à chercher
	 *
	 * @return array
	 */
	public static function getObjectsInList($objects, $key, $value) {
		$return = array();
		foreach ($objects as $object) {
			if ((isset($object->$key) and $object->$key == $value) or (method_exists($object, 'get'.ucfirst($key)) and $object->{'get'.ucfirst($key)}() == $value)){
				$return[] = $object;
			}
		}
		return $return;
	}

	/**
	 * Retourne l'équivalent d'un var_dump, mais dans le flux normal de sortie
	 *
	 * @see <http://www.php.net/manual/fr/function.var-export.php>
	 *
   * @param mixed $var
	 *
	 * @return string
	 */
	public static function varDump($var) {
		return '<pre>' . gettype($var).' '.var_export($var, true) . '</pre>' . PHP_EOL;
	}

	/**
	 * Retourne aléatoirement une règle stupide
	 *
	 * @return string
	 */
	public static function stupidRule(){
		$rules = array(
			'Le riz basmati se cuit avec un volume de riz pour 5 volumes d\'eau pendant 10-11 minutes dans une eau légèrement bouillante. Pensez à enlever l\'amidon qui flotte sur l\'eau après la cuisson.',
		  'Ce qui est écrit ici ne contient à priori pas de propagande.',
		  'Ces instructions sont récapitulées dans le manuel, qui ne verra probablement jamais le jour.',
		  'Lire des instructions est rarement une perte de temps.',
		  'Pour votre santé, mangez au moins 5 fruits et légumes par jour.',
		  'Ne faites pas ça chez vous.',
		  'Ces instructions sont basées sur des manuels de montage de meuble.',
			'Pour votre santé ne mangez pas trop gras, trop salé, trop sucré.',
			'Pensez à vous hydrater régulièrement, surtout en cas de fortes chaleurs.',
			'Ce message s\'autodétruira dans quelques minutes dès que le module d\'auto-destruction aura été activé.',
			'Ces instructions seront périmées dans 3 jours et 22 minutes.',
			'Lavez-vous les mains après avoir utilisé votre clavier, il est plein de germes.',
			'Toutes les 20 minutes, regardez quelque chose à 6m de vous pendant 20 secondes. Ça permet à vos yeux de se reposer et de ne pas trop s\'assécher.'
		);
		$i = rand(0, count($rules)-1);
		return $rules[$i];
	}

	/**
	 * Arrondit un nombre à la valeur d'incrémentation
	 *
	 * @example roundUpTo(18.4, 5) = 20
	 * @example roundUpTo(3, 5) = 5
	 * @example roundUpTo(84, 10) = 80
	 *
	 * @from <http://php.net/manual/fr/function.round.php#100322>
	 *
	 * @param float $number Nombre à arrondir
	 * @param int   $increments Valeur d'incrémentation
	 *
	 * @return int
	 */
	public static function roundTo($number, $increments) {
		$increments = 1 / $increments;
		return (round($number * $increments) / $increments);
	}

	/**
	 * retourne une chaîne tronquée à x caractères
	 *
	 * @from http://stackoverflow.com/a/79986/1749967
	 * @param string $text       Texte à tronquer
	 * @param int    $charNumber Nombre de caractères à renvoyer
	 * @param bool   $isAbbr     Si true, renvoie la chaîne tronquée dans une balise abbr contenant la totalité de la chaîne (si celle-ci est tronquée)
	 * @param bool   $noDots     N'ajoute pas `[...]` à la fin de la chaîne tronquée
	 *
	 * @return string
	 */
	public static function excerpt($text, $charNumber, $isAbbr = false, $noDots = false){
		$parts = preg_split('/([\s\n\r]+)/', $text, null, PREG_SPLIT_DELIM_CAPTURE);
		$partsCount = count($parts);
		$addFinal = false;
		if ($partsCount < 2) {
			if (strlen($text) > $charNumber and !$noDots){
				$addFinal = true;
			}
			$ret =  ($addFinal) ? substr($text, 0, $charNumber - 6) : substr($text, 0, $charNumber);
		} else {
			$length = 0;
			$lastPart = 0;
			for (; $lastPart < $partsCount; ++$lastPart) {
				$length += strlen($parts[$lastPart]);
				if ($length > ($charNumber - 6)) {
					$addFinal = true;
					break;
				}
			}
			$ret = implode(array_slice($parts, 0, $lastPart));
		}
		if ($addFinal and !$noDots){
			$ret .= ' [...]';
		}
		if ($isAbbr and $addFinal){
			$ret = '<abbr class="tooltip-bottom" title="'.$text.'">'.$ret.'</abbr>';
		}
		return $ret;
	}
}