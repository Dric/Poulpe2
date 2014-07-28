<?php

namespace Modules\Transmission;

/**
* Classe de session Transmission
* 
* @see <https://trac.transmissionbt.com/browser/trunk/extras/rpc-spec.txt>
* @package Modules\Transmission
*/
class TransSession extends TransmissionRPC{

	/**
	* Limite max de ratio partage/téléchargement
	* @var float
	*/
	protected $ratioLimit = 1;

	/**
	* Débit descendant (en ko/s)
	* @var int
	*/
	protected $dlSpeed = 350;

	/**
	* Débit montant (en ko/s)
	* @var int
	*
	*/
	protected $upSpeed = 90;

	/**
	* Débit descendant alternatif (en ko/s)
	* @var int
	*/
	protected $altDlSpeed = 80;

	/**
	* Débit montant alternatif (en ko/s)
	* @var int
	*
	*/
	protected $altUpSpeed = 30;

	/**
	* Vitesses de transfert alternatives actives
	* @var bool
	*/
	protected $altSpeedEnabled = false;

	/**
	* Heure quotidienne du basculement sur les vitesses alternatives (exprimé en secondes depuis 0h00)
	*
	* 27000 secondes = 7h30
	* @var int
	*/
	protected $altBegin = 27000;

	/**
	* Heure quotidienne de la fin d'utilisation des vitesses alternatives (exprimé en secondes depuis 0h00)
	*
	* 84600 secondes = 23h30
	* @var int
	*/
	protected $altEnd = 84600;

	/**
	* Jours d'activation des vitesses alternatives
	*
	* Dimanche					= 1			(binary: 0000001)
  * Lundi							= 2			(binary: 0000010)
  * Mardi							= 4			(binary: 0000100)
  * Mercredi					= 8			(binary: 0001000)
  * Jeudi							= 16		(binary: 0010000)
  * Vendredi					= 32		(binary: 0100000)
  * Samedi						= 64		(binary: 1000000)
  * Jours ouvrés			= 62		(binary: 0111110)
  * Weekend						= 65		(binary: 1000001)
  * Toute la semaine	= 127		(binary: 1111111)
  * Aucun							= 0			(binary: 0000000)
  *
  * Il suffit d'additionner les jours pour en cumuler plusieurs. Ex : lundi, mardi et mercredi : 14
	* @var int
	*
	*/
	protected $altDaysEnabled = 127;

	/**
	 * @param string $transmissionURL URL de transmissionRPC
	 */
	public function __construct($transmissionURL){
		parent::__construct($transmissionURL);
		$settings = $this->request("session-get", array())->arguments;
		$this->ratioLimit			= (float)$settings->seedRatioLimit;
		$this->dlSpeed					= (int)$settings->speed_limit_down;
		$this->upSpeed					= (int)$settings->speed_limit_up;
		$this->altDlSpeed			= (int)$settings->alt_speed_down;
		$this->altUpSpeed			= (int)$settings->alt_speed_up;
		$this->altSpeedEnabled	= (int)$settings->alt_speed_enabled;
		$this->altBegin				= (int)($settings->alt_speed_time_begin*60);
		$this->altEnd					= (int)($settings->alt_speed_time_end*60);
		$this->altDaysEnabled	= (int)$settings->alt_speed_time_day;
	}

	/**
	 * Retourne le bit à la position $n dans un nombre
	 *
	 * @param int $number Nombre
	 * @param int $n Position (commence à 1)
	 *
	 * @return int
	 */
	protected function nbit($number, $n) {
		return ($number >> $n-1) & 1;
	}

	/**
	 * @return float
	 */
	public function getRatioLimit() {
		return $this->ratioLimit;
	}

	/**
	 * @return int
	 */
	public function getDlSpeed() {
		return $this->dlSpeed;
	}

	/**
	 * @return int
	 */
	public function getUpSpeed() {
		return $this->upSpeed;
	}

	/**
	 * @return int
	 */
	public function getAltDlSpeed() {
		return $this->altDlSpeed;
	}

	/**
	 * @return int
	 */
	public function getAltUpSpeed() {
		return $this->altUpSpeed;
	}

	/**
	 * @return boolean
	 */
	public function getAltSpeedEnabled() {
		return $this->altSpeedEnabled;
	}

	/**
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 *
	 * @return int|string
	 */
	public function getAltBegin($realValue = false) {
		if ($realValue) {
			return $this->altBegin;
		}else{
			return \Sanitize::time($this->altBegin, 'time');
		}
	}

	/**
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 *
	 * @return int|string
	 */
	public function getAltEnd($realValue = false) {
		if ($realValue) {
			return $this->altEnd;
		}else{
			return gmdate('H:i', floor($this->altEnd * 60));
		}
	}

	/**
	 *
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 *
	 * @return int|string
	 */
	public function getAltDaysEnabled($realValue = false) {
		if ($realValue) {
			return $this->altDaysEnabled;
		}else{
			$days = $this->altDaysEnabled;
			if ($days == 127) return 'Tous les jours';
			if ($days == 65)	return 'Le weekend';
			if ($days == 62)	return 'Du lundi au vendredi';
			if ($days === 0)	return 'Jamais';
			$daysArr = array();
			if ($this->nbit($days, 1) === 1) $daysArr[] = 'Dimanche';
			if ($this->nbit($days, 2) === 1) $daysArr[] = 'Lundi';
			if ($this->nbit($days, 3) === 1) $daysArr[] = 'Mardi';
			if ($this->nbit($days, 4) === 1) $daysArr[] = 'Mercredi';
			if ($this->nbit($days, 5) === 1) $daysArr[] = 'Jeudi';
			if ($this->nbit($days, 6) === 1) $daysArr[] = 'Vendredi';
			if ($this->nbit($days, 7) === 1) $daysArr[] = 'Samedi';
			return implode(', ', $daysArr);
		}
	}

	/**
	 * @param float $ratioLimit
	 */
	public function setRatioLimit($ratioLimit) {
		$this->ratioLimit = $ratioLimit;
	}

	/**
	 * @param int $dlSpeed
	 */
	public function setDlSpeed($dlSpeed) {
		$this->dlSpeed = $dlSpeed;
	}

	/**
	 * @param int $upSpeed
	 */
	public function setUpSpeed($upSpeed) {
		$this->upSpeed = $upSpeed;
	}

	/**
	 * @param int $altDlSpeed
	 */
	public function setAltDlSpeed($altDlSpeed) {
		$this->altDlSpeed = $altDlSpeed;
	}

	/**
	 * @param int $altUpSpeed
	 */
	public function setAltUpSpeed($altUpSpeed) {
		$this->altUpSpeed = $altUpSpeed;
	}

	/**
	 * @param boolean $altSpeedEnabled
	 */
	public function setAltSpeedEnabled($altSpeedEnabled) {
		$this->altSpeedEnabled = $altSpeedEnabled;
	}

	/**
	 * @param int $altBegin
	 */
	public function setAltBegin($altBegin) {
		$this->altBegin = $altBegin;
	}

	/**
	 * @param int $altEnd
	 */
	public function setAltEnd($altEnd) {
		$this->altEnd = $altEnd;
	}

	/**
	 * @param int $altDaysEnabled
	 */
	public function setAltDaysEnabled($altDaysEnabled) {
		$this->altDaysEnabled = $altDaysEnabled;
	}
}
?>