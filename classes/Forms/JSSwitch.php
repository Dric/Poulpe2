<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 16:22
 */

namespace Forms;

/**
 * Classe de paramètres de switch
 *
 * @package Forms
 */
class JSSwitch {

	/**
	 * Texte pour état activé
	 * @var string
	 */
	protected $onText = 'Oui';
	/**
	 * Texte pour état désactivé
	 * @var string
	 */
	protected $offText = 'Non';
	/**
	 * Couleur de l'état activé
	 * @var string
	 */
	protected $onColor = 'primary';
	/**
	 * Couleur de l'état désactivé
	 * @var string
	 */
	protected $offColor = 'default';
	/**
	 * Taille du switch
	 * @var string
	 */
	protected $size = 'normal';
	/**
	 * Position du switch (left, right)
	 * @var string
	 */
	protected $labelPosition = 'right';
	/**
	 * Couleurs autorisées
	 * @var array
	 */
	protected $colors = array(
		'primary',
		'info',
		'success',
		'warning',
		'danger',
		'default'
	);
	/**
	 * Tailles autorisées
	 * @var array
	 */
	protected $sizes  = array(
		'mini',
		'small',
		'normal',
		'large'
	);

	/**
	 * Paramètres de switch
	 *
	 * @param string $size            Taille du switch
	 * @param string $labelPosition   Position du label (left, right)
	 * @param string $onText          Texte de l'état activé
	 * @param string $offText         Texte de l'état désactivé
	 * @param string $onColor         Couleur de l'état activé
	 * @param string $offColor        Couleur de l'état désactivé
	 */
	public function __construct($size = null, $labelPosition = null, $onText = null, $offText = null, $onColor = null, $offColor = null){
		if (!is_null($onText))    $this->onText = $onText;
		if (!is_null($offText))   $this->offText = $offText;
		if (!is_null($onColor) and in_array($onColor, $this->colors))   $this->onColor = $onColor;
		if (!is_null($offColor) and in_array($offColor, $this->colors)) $this->offColor = $offColor;
		if (!is_null($size) and in_array($size, $this->sizes))          $this->size = $size;
		if (!is_null($labelPosition) and in_array($labelPosition, array('left', 'right'))) $this->labelPosition = $labelPosition;
	}

	/**
	 * @return string
	 */
	public function getOnText() {
		return $this->onText;
	}

	/**
	 * @return string
	 */
	public function getOffText() {
		return $this->offText;
	}

	/**
	 * @return string
	 */
	public function getOnColor() {
		return $this->onColor;
	}

	/**
	 * @return string
	 */
	public function getOffColor() {
		return $this->offColor;
	}

	/**
	 * @return string
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getLabelPosition() {
		return $this->labelPosition;
	}
} 