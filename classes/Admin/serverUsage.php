<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/06/14
 * Time: 09:18
 */

namespace Admin;

/**
 * Classe de calcul et de gestion de ressource de serveur
 *
 * @package Admin
 */
class serverUsage {

	/**
	 * Pourcentage d'occupation de la ressource
	 * @var float
	 */
	protected $percent = 0;
	/**
	 * Total d'occupation de la ressource
	 * @var float
	 */
	protected $total = 0;
	/**
	 * Quantité libre de ressource
	 * @var int
	 */
	protected $free = 0;
	/**
	 * La ressource s'exprime en octets
	 * @var bool
	 */
	protected $useOctal = false;

	/**
	 * Construction de la gestion de ressource
	 *
	 * @param float $free     Quantité libre de ressource
	 * @param float $total    Total d'occupation de la ressource
	 * @param float $percent  La ressource s'exprime en octets
	 * @param bool  $useOctal La ressource s'exprime en octets
	 */
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

	/**
	 * Méthode magique pour récupérer les propriétés de la classe
	 * @param string $prop Propriété
	 *
	 * @return bool|float|string
	 */
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