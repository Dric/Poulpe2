<?php

namespace Modules\Transmission;
use Logs\Alert;

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
	 * Limite de ratio activée
	 * @var bool
	 */
	protected $isRatioLimited = true;

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
	* Mode tortue actif
	*
	* A ne pas confondre avec `$altModeEnabled` qui lui gère la planification
	*
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
	protected $altDaysSchedule = 127;

	/**
	 * Activation de la planification de mode tortue
	 * @var bool
	 */
	protected $altModeEnabled = true;


	/**
	 * @param string $transmissionURL URL de transmissionRPC
	 */
	public function __construct($transmissionURL){
		parent::__construct($transmissionURL);
		$settings = $this->request("session-get", array())->arguments;
		$this->ratioLimit			  = round($settings->seedRatioLimit, 1);
		$this->isRatioLimited   = (bool)$settings->seedRatioLimited;
		$this->dlSpeed					= (int)$settings->speed_limit_down;
		$this->upSpeed					= (int)$settings->speed_limit_up;
		$this->altDlSpeed			  = (int)$settings->alt_speed_down;
		$this->altUpSpeed			  = (int)$settings->alt_speed_up;
		$this->altSpeedEnabled	= (bool)$settings->alt_speed_enabled;
		$this->altBegin				  = (int)($settings->alt_speed_time_begin*60);
		$this->altEnd					  = (int)($settings->alt_speed_time_end*60);
		$this->altDaysSchedule	= (int)$settings->alt_speed_time_day;
		$this->altModeEnabled	  = (bool)$settings->alt_speed_time_enabled;
	}

	/**
	 * Sauvegarde les paramètres du serveur transmission
	 */
	public function saveSession(){
		$arguments = array(
			'seedRatioLimit'        => $this->ratioLimit,
			'seedRatioLimited'      => $this->isRatioLimited,
		  'speed-limit-down'      => $this->dlSpeed,
		  'speed-limit-up'        => $this->upSpeed,
		  'alt-speed-down'        => $this->altDlSpeed,
		  'alt-speed-up'          => $this->altUpSpeed,
		  'alt-speed-enabled'     => $this->altSpeedEnabled,
		  'alt-speed-time-begin'  => floor($this->altBegin/60),
		  'alt-speed-time-end'    => floor($this->altEnd/60),
		  'alt-speed-time-day'    => $this->altDaysSchedule,
		  'alt-speed-time-enabled'=> $this->altModeEnabled
		);
		$ret = $this->sset($arguments);
		if ($ret->result == 'success'){
			new Alert('success', 'Les paramètres du serveur ont été sauvegardés !');
			return true;
		}else{
			new Alert('error', 'Les paramètres du serveur n\'ont pas pu être sauvegardés !');
			return false;
		}
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
	 * @return boolean
	 */
	public function getIsRatioLimited() {
		return $this->isRatioLimited;
	}

	/**
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 *
	 * @return int|string
	 */
	public function getDlSpeed($realValue = false) {
		if ($realValue) {
			return $this->dlSpeed;
		}else{
			return \Sanitize::readableFileSize($this->dlSpeed*1024, 0);
		}
	}

	/**
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 *
	 * @return int|string
	 */
	public function getUpSpeed($realValue = false) {
		if ($realValue) {
			return $this->upSpeed;
		}else{
			return \Sanitize::readableFileSize($this->upSpeed*1024, 0);
		}
	}

	/**
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 *
	 * @return int|string
	 */
	public function getAltDlSpeed($realValue = false) {
		if ($realValue) {
			return $this->altDlSpeed;
		}else{
			return \Sanitize::readableFileSize($this->altDlSpeed*1024, 0);
		}
	}

	/**
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 *
	 * @return int|string
	 */
	public function getAltUpSpeed($realValue = false) {
		if ($realValue) {
			return $this->altUpSpeed;
		}else{
			return \Sanitize::readableFileSize($this->altUpSpeed*1024, 0);
		}
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
	 * @return boolean
	 */
	public function getAltModeEnabled() {
		return $this->altModeEnabled;
	}

	/**
	 * Retourne les jours où le mode tortue est activé
	 *
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 * @param bool $textFormat Retourner sous format texte (numérique entier sinon)
	 *
	 * @return int|string
	 */
	public function getAltDaysSchedule($realValue = false, $textFormat = true) {
		if ($realValue) {
			return $this->altDaysSchedule;
		}else{
			$days = $this->altDaysSchedule;
			if ($days == 127) return ($textFormat) ? 'Tous les jours' : $days;
			if ($days == 65)	return ($textFormat) ? 'Le weekend' : $days;
			if ($days == 62)	return ($textFormat) ? 'Du lundi au vendredi' : $days;
			if ($days === 0)	return ($textFormat) ? 'Jamais' : $days;
			$daysArr = array();
			if ($this->nbit($days, 1) === 1) $daysArr[1] = ($textFormat) ? 'Dimanche'  : 1;
			if ($this->nbit($days, 2) === 1) $daysArr[2] = ($textFormat) ? 'Lundi'     : 2;
			if ($this->nbit($days, 3) === 1) $daysArr[4] = ($textFormat) ? 'Mardi'     : 4;
			if ($this->nbit($days, 4) === 1) $daysArr[8] = ($textFormat) ? 'Mercredi'  : 8;
			if ($this->nbit($days, 5) === 1) $daysArr[16] = ($textFormat) ? 'Jeudi'     : 16;
			if ($this->nbit($days, 6) === 1) $daysArr[32] = ($textFormat) ? 'Vendredi'  : 32;
			if ($this->nbit($days, 7) === 1) $daysArr[64] = ($textFormat) ? 'Samedi'    : 64;
			if (isset($daysArr[64]) and isset($daysArr[1])){
				$daysArr[65] = ($textFormat) ? 'Le weekend' : 65;
				unset($daysArr[64]);
				unset($daysArr[1]);
			}
			if (isset($daysArr[2]) and isset($daysArr[4]) and isset($daysArr[8]) and isset($daysArr[16]) and isset($daysArr[32])){
				$daysArr[62] = ($textFormat) ? 'Du lundi au vendredi' : 62;
				unset($daysArr[2]);
				unset($daysArr[4]);
				unset($daysArr[8]);
				unset($daysArr[16]);
				unset($daysArr[32]);
			}
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
	public function setAltSpeedEnabled($altSpeedEnabled = true) {
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
	public function setAltDaysSchedule($altDaysEnabled) {
		$this->altDaysSchedule = (int)$altDaysEnabled;
	}

	/**
	 * @param boolean $isRatioLimited
	 */
	public function setIsRatioLimited($isRatioLimited = true) {
		$this->isRatioLimited = (bool)$isRatioLimited;
	}

	/**
	 * @param boolean $altModeEnabled
	 */
	public function setAltModeEnabled($altModeEnabled = true) {
		$this->altModeEnabled = (bool)$altModeEnabled;
	}
}
?>