<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 13:36
 */

namespace Forms;

/**
 * Classe de paramètres de validation d'un champ
 *
 * @package Forms
 */
class Pattern {

	/**
	 * Champ obligatoire
	 * @var bool
	 */
	protected $required   = false;
	/**
	 * Nombre de caractères minimum
	 * @var int
	 */
	protected $minLength  = 0;
	/**
	 * Nombre de caractères maximum
	 * @var int
	 */
	protected $maxLength  = 0;
	/**
	 * Expression régulière
	 * Non supporté par IE < 10
	 * @var string
	 */
	protected $regExp     = null;
	/**
	 * Type de champ (Identique aux types de champs HTML 5)
	 * @link <http://www.w3schools.com/tags/att_input_type.asp>
	 * @var string
	 */
	protected $type       = 'text';
	/**
	 * Valeur minimum pour les champs de type number, range, date, datetime, datetime-local, month, time et week
	 * IE 10 ne supporte pas les champs de dates et heure
	 * @var int|string
	 */
	protected $minValue   = 0;
	/**
	 * Valeur maximum pour les champs de type number, range, date, datetime, datetime-local, month, time et week
	 * IE 10 ne supporte pas les champs de dates et heure
	 * @var int
	 */
	protected $maxValue   = 0;

	/**
	 * Type de champs autorisés
	 * @link <http://www.w3schools.com/tags/att_input_type.asp>
	 * @link <https://developer.mozilla.org/en/docs/Web/HTML/Element/Input#Browser_compatibility> pour les supports de types de champs par les navigateurs
	 * @var string[]
	 */
	protected $types = array(
		'button',         // Defines a clickable button (mostly used with a JavaScript to activate a script)
		'checkbox',       // Defines a checkbox
		'color',          // Defines a color picker
		'date',           // Defines a date control (year, month and day (no time))
		'datetime',       // Defines a date and time control (year, month, day, hour, minute, second, and fraction of a second, based on UTC time zone)
		'datetime-local', // Defines a date and time control (year, month, day, hour, minute, second, and fraction of a second (no time zone)
		'email',          // Defines a field for an e-mail address
		'file',           // Defines a file-select field and a "Browse..." button (for file uploads)
		'hidden',         // Defines a hidden input field
		'image',          // Defines an image as the submit button
		'month',          // Defines a month and year control (no time zone)
		'number',         // Defines a field for entering a number
		'password',       // Defines a password field (characters are masked)
		'radio',          // Defines a radio button
		'range',          // Defines a control for entering a number whose exact value is not important (like a slider control)
		'reset',          // Defines a reset button (resets all form values to default values)
		'search',         // Defines a text field for entering a search string
		'submit',         // Defines a submit button
		'tel',            // Defines a field for entering a telephone number
		'text ',	        // Default. Defines a single-line text field (default width is 20 characters)
		'time',           // Defines a control for entering a time (no time zone)
		'url',            // Defines a field for entering a URL
		'week',           // Defines a week and year control (no time zone)
	);

	/**
	 * Définit des paramètres de validation d'un champ de formulaire
	 *
	 * @param string      $type       Type de champ (voir le tableau $types)
	 * @param bool        $required   Champ obligatoire
	 * @param int         $minLength  Nombre de caractères minimum
	 * @param int         $maxLength  Nombre de caractères maximum
	 * @param int|string  $minValue   Valeur minimum pour les champs de type `int` et `date`
	 * @param int|string  $maxValue   Valeur maximum pour les champs de type `int` et `date`
	 * @param string      $regExp     Expression régulière pour la validation
	 *
	 * @link <http://www.w3schools.com/tags/att_input_type.asp> pour les types de champs acceptés
	 * @link <https://developer.mozilla.org/en/docs/Web/HTML/Element/Input#Browser_compatibility> pour les supports de types de champs par les navigateurs
	 */
	public function __construct($type, $required = false, $minLength = 0, $maxLength = 0, $minValue = 0, $maxValue = 0, $regExp = null){
		$this->type       = (in_array($type, $this->types)) ? $type : 'text';
		$this->required   = (bool)$required;
		$this->minLength  = (int) $minLength;
		$this->maxLength  = (int) $maxLength;
		$this->minValue   = $minValue;
		$this->maxValue   = $maxValue;
		$this->regExp     = $regExp;
	}

	/**
	 * Retourne le statut de saisie obligatoire du champ
	 * @return boolean
	 */
	public function getRequired() {
		return $this->required;
	}

	/**
	 * Retourne la longueur de saisie minimum du champ
	 * @return int
	 */
	public function getMinLength() {
		return $this->minLength;
	}

	/**
	 * Retourne la longueur de saisie maximale du champ
	 * @return int
	 */
	public function getMaxLength() {
		return $this->maxLength;
	}

	/**
	 * Retourne l'expression régulière utilisée pour valider le champ
	 * @return string
	 */
	public function getRegExp() {
		return $this->regExp;
	}

	/**
	 * Retourne le type html de champ
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Retourne la valeur de saisie minimale (dans le cas d'un champ `number` ou `date`)
	 * @return int|string
	 */
	public function getMinValue() {
		return $this->minValue;
	}

	/**
	 * Retourne la valeur de saisie maximale (dans le cas d'un champ `number` ou `date`)
	 * @return int
	 */
	public function getMaxValue() {
		return $this->maxValue;
	}

} 