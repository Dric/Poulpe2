<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 14/04/14
 * Time: 09:12
 */

namespace Settings;
use Sanitize;

/**
 * Champ de formulaire (extension de Setting)
 *
 * @package Settings
 */
class Field extends Setting{

	/**
	 * Libellé affiché dans une balise <label>
	 * @var string
	 */
	protected $label = '';

	/**
	 * Libellé affiché dans la propriété placeholder du champ
	 * @var string
	 */
	protected $placeholder = '';

	/**
	 * Expression régulière délimitant la saisie du champ (pour validation par js et php)
	 * @var string
	 */
	protected $pattern = null;

	protected $type = '';

	protected $help = '';

	protected $class = '';

	/**
	 * Types possibles pour un champ - élargi par rapport aux types de Setting
	 * @var array
	 */
	static protected $types = array();

	/**
	 * Data regroupe toutes les informations nécessaires à la construction du champ.
	 * array(
	 *  'choices' => array($value => $label, $value2 => $label2, ...) Pour un élément à choix multiples comme un élément radio
	 * );
	 * @var array
	 */
	protected $data = array();

	protected $ACLLevel = null;

	protected $disabled = false;

	/**
	 * Construction du champ
	 *
	 * @param string $name Nom du champ - repris dans la propriété name et id
	 * @param string $type Type du champ - type élargi par rapport au type de Setting
	 * @param string $category catégorie 'global' ou 'user'
	 * @param mixed  $value Valeur à mettre dans la propriété value
	 * @param string $label Libellé du champ - repris dans l'élément <label>
	 * @param string $placeholder Placeholder - repris dans la propriété du même nom
	 * @param array  $data Informations complémentaires requises pour la construction du champ
	 * - array 'choices' Choix possibles pour les boutons radio
	 * - array (
	 *    'showOtherField' => array(
	 *        'fieldId1' => array(
	 *            'on' =>
	 * @param string $help Message d'aide du champ - facultatif
	 * @param null   $pattern
	 * @param null   $userValue
	 * @param bool   $important Paramètre à signaler comme étant important
	 * @param int    $id Id du paramètre en bdd
	 * @param string $ACLLevel Niveau minimum d'autorisation ('access', 'modify' ou 'admin')
	 * @param string $class Classe optionnelle à ajouter au champ
	 * @param bool   $disabled Champ désactivé si à true
	 */
	public function __construct($name, $type, $category, $value, $label = null, $placeholder = null, $data = array(), $help = null, $pattern = null, $userValue = null, $important = false, $id = null, $ACLLevel = 'admin', $class = '', $disabled = false){
		self::$types = Setting::$types;
		self::$types += array(
			'email'   => 'string',
		  'radio'   => 'string',
		  'file'    => 'string',
		  'linkButton'  => 'string',
		  'select'  => 'string',
		  'checkboxList'  => 'string'
		);
		switch ($type){
			case 'email':
			case 'radio':
			case 'select':
				$typeSetting = 'string';
				break;
			default:
				$typeSetting = $type;
		}
		parent::__construct($name, $typeSetting, $category, $value, $userValue = null, $important = false);
		$this->type = $type;
		if (!empty($label)) $this->label = $label;
		if (!empty($placeholder)) $this->placeholder = $placeholder;
		$this->data = $data;
		if (!empty($help)) $this->help = $help;
		if (!empty($pattern)) $this->pattern = $pattern;
		$this->ACLLevel = (!empty($ACLLevel)) ? $ACLLevel : 'admin';
		if (!empty($class)) $this->class = $class;
		$this->disabled = $disabled;
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getPattern() {
		return $this->pattern;
	}

	/**
	 * @return string
	 */
	public function getPlaceholder() {
		return $this->placeholder;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function getHelp() {
		return $this->help;
	}

	/**
	 * @return null|string
	 */
	public function getACLLevel() {
		return $this->ACLLevel;
	}

	/**
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * @return boolean
	 */
	public function getDisabled() {
		return $this->disabled;
	}

	/**
	 * Valeur du paramètre définie par l'utilisateur
	 * @param mixed $userValue
	 */
	public function setUserValue($userValue) {

		if ($this->type == 'date') $userValue = Sanitize::date($userValue, 'timestamp');
		if ($this->type != 'select') settype($userValue, self::$types[$this->type]);
		$this->userValue = $userValue;
	}

	/**
	 * Valeur du paramètre définie par l'administrateur
	 * @param mixed $value
	 */
	public function setValue($value) {
		if ($this->type == 'date') $value = Sanitize::date($value, 'timestamp');
		if ($this->type != 'select') settype($value, self::$types[$this->type]);
		$this->value = $value;
	}

}