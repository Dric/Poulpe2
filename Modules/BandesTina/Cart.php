<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 22/05/14
 * Time: 11:52
 */

namespace Modules\BandesTina;


/**
 * Classe cartouche
 *
 * @package Modules\gestionBandesTina
 */
Class Cart{

	/**
	 * Code-barre de la cartouche
	 * @var string
	 */
	protected $barcode = '';

	/**
	 * Identifiant (2 chiffres) permettant d'identifier rapidement la cartouche
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Type de sauvegarde inscrite sur la cartouche
	 * @var string
	 *
	 */
	protected $type = '';

	/**
	 * Objet cartouche
	 * @param string $barcode Code-Barre de la cartouche
	 *
	 */
	public function __construct($barcode){
		$this->setBarcode($barcode);
		$this->setId($barcode);
		$this->setType($barcode);
	}

	/**
	 * Définit le code-barre de l'objet
	 * @param string $barcode
	 *
	 * @return bool
	 */
	public function setBarcode($barcode){
		if (preg_match(BandesTina::$regex, $barcode, $output)){
			$this->barcode = $output[1];
			return true;
		}
		return false;
	}

	/**
	 * Définit l'identifiant de la cartouche
	 * @param string $barcode
	 *
	 * @return bool
	 */
	public function setId($barcode){
		if (preg_match(BandesTina::$regex, $barcode, $output)){
			$this->id = (int)$output[2];
			return true;
		}
		return false;
	}

	/**
	 * Définit le type de sauvegarde
	 * @param string $barcode
	 *
	 * @return bool
	 */
	public function setType($barcode){
		if (preg_match(BandesTina::$regex, $barcode, $output)){
			$this->type = $output[3];
			return true;
		}
		return false;
	}

	/**
	 * Retourne le code-barre de la cartouche
	 *
	 * @return string
	 */
	public function getBarcode(){
		return $this->barcode;
	}

	/**
	 * Retourne l'identifiant de la cartouche
	 *
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * Retourne le type de sauvegarde de la cartouche
	 *
	 * @return string
	 */
	public function getType(){
		return $this->type;
	}
}