<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/04/14
 * Time: 09:38
 */

/**
 * Class Get
 * Contient les fonctions d'obtention de données
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
			$params[$item[0]] = $item[1];
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
	 */
	public static function stupidRule(){
		$rules = array(
			'Le riz basmati se cuit avec un volume de riz pour 5 volumes d\'eau pendant 10 minutes dans une eau légèrement bouillante. Pensez à enlever l\'amidon qui flotte sur l\'eau après la cuisson',
		  'Ce qui est écrit ici doit rester secret, sauf pour la NSA qui a déjà lu ceci avant que ce ne soit écrit',
		  'Ces instructions sont récapitulées dans le manuel, qui ne verra probablement jamais le jour',
		  'Lire des instructions est rarement une perte de temps'
		);
		$i = rand(0, count($rules)-1);
		return $rules[$i];
	}
}