<?php
/**
 * Created by PhpStorm.
 * User: Dric
 * Date: 12/08/14
 * Time: 18:50
 */

namespace TMDB\structures;


class Tv extends Asset{
	public static $type = 'tv';

	/**
	 * @link http://help.themoviedb.org/kb/api/movie-casts
	 */
	public function casts() {
		$casts = array();
		$db = \TMDB\Client::getInstance();
		$info = $db->info(self::$type, $this->id, 'casts');
		foreach ($info as $group => $persons) {
			if (!is_array($persons)) continue;
			foreach ($persons as $index => $person) {
				if (!isset($casts[$group][$person->id])){
					$casts[$group][$person->id] = new Person($person);
				}else{
					$casts[$group][$person->id.'_2'] = new Person($person);
				}
			}
		}
		return $casts;
	}
	/**
	 * Get the posters
	 */
	public function poster($size = false, $random = false, $language = null) {
		return $this->image('poster', $size, $random, $language);
	}
	public function posters($size, $language = null) {
		$images = $this->images($language, $size);
		return $images->posters;
	}

	/**
	 * Récupère un épisode d'une saison
	 */
	public function episode($season, $episode) {
		$db = \TMDB\Client::getInstance();
		$info = $db->info(self::$type, $this->id, 'season/'.$season.'/episode/'.$episode);
		return $info;
	}
} 