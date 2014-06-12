<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/06/14
 * Time: 09:18
 */

namespace Admin;


class serverUsage {
	protected $percent = 0;
	protected $total = 0;
	protected $free = 0;
	protected $useOctal = false;

	public function __construct($free, $total, $percent, $useOctal){
		$this->free = (float)$free;
		$this->total = (float)$total;
		$this->percent = (float)$percent;
		$this->useOctal = (bool)$useOctal;
	}

	/**
	 * Retourne le pourcentage d'occupation de la ressource
	 * @param bool $realValue Retourne la vraie valeur ou la valeur mise en forme
	 *
	 * @return float|string
	 */
	public function getPercent($realValue = false) {
		if ($realValue) return $this->percent;
		if ($this->percent > 1){
			return $this->percent.'%';
		}
		return ($this->percent*100).'%';
	}

	/**
	 * Retourne le total d'occupation de la ressource
	 * @param bool $realValue Retourne la vraie valeur ou la valeur mise en forme
	 *
	 * @return float|string
	 */
	public function getTotal($realValue = false) {
		return $this->total;
	}

	/**
	 * Retourne la quantité libre restante de la ressource
	 * @param bool $realValue Retourne la vraie valeur ou la valeur mise en forme
	 *
	 * @return float|string
	 */
	public function getFree($realValue = false) {
		return $this->free;
	}

	public function __get($prop){
		if (isset($this->$prop)){
			if ($prop == 'percent'){
				if ($this->percent < 1){
					return ($this->percent*100);
				}
				return $this->percent;
			}else{
				if ($this->useOctal){
					return \Sanitize::readableFileSize($this->$prop);
				}
				return $this->$prop;
			}
		}
		return false;
	}

} 