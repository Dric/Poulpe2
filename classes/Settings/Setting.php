<?php
/**
 * Classe de gestion des paramètres
 *
 * User: cedric.gallard
 * Date: 17/03/14
 * Time: 12:33
 *
 * @package Settings
 */

namespace Settings;
use Sanitize;

/**
 * Class Setting
 *
 * @package Settings
 */
class Setting {

	/**
	 * Type de paramètre
	 * - string
	 * - int
	 * - float
	 * - bool
	 * - list
	 * - text
	 * - date
	 * - button
	 * @var string
	 */
	protected $type = 'string';

	/**
	 * Id de la bdd du paramètre
	 * @var int
	 */
	protected $id = 0;
	/**
	 * Nom du paramètre
	 * @var string
	 */
	protected $name = '';

	/**
	 * Valeur définie par l'utilisateur
	 * @var mixed
	 */
	protected $userValue = null;

	/**
	 * Valeur du paramètre
	 * @var mixed
	 */
	protected $value = null;
	/**
	 * Catégorie du paramètre
	 * - global
	 * - user
	 * @var string
	 */
	protected $category = 'global';

	/**
	 * Paramètre important
	 *
	 * Les paramètres importants ne doivent pas être changés à la légère.
	 * @var bool
	 */
	protected $important = false;

	/**
	 * Types autorisés de type de paramètre
	 * Le tableau est de la forme 'type' => typage php des valeurs
	 * @var array
	 */
	static protected $types = array(
		'string'  => 'string',
		'int'     => 'int',
		'float'   => 'float',
		'bool'    => 'bool',
		'list'    => 'array',
		'text'    => 'string',
		'date'    => 'int',
		'button'  => 'string',
		'hidden'  => 'string',
	  'data'    => 'string',
	  'dbTable' => 'string'
	);

	/**
	 * Types autorisés de catégorie de paramètre
	 * @var array
	 */
	static protected $categories = array('global', 'user');

	/**
	 * Construction du paramètre
	 *
	 * @param string $name Nom du paramètre
	 * @param string $type Type de paramètre (voir la définition de la propriété Setting->type)
	 * @param string $category Catégorie du paramètre (global ou user)
	 * @param mixed  $value Valeur du paramètre
	 * @param mixed  $userValue Valeur définie par l'utilisateur pour ce paramètre
	 * @param bool   $important Détermine si ce paramètre est important ou non (signalé à l'utilisateur lors de l'affichage des paramètres)
	 * @param int    $id Id du paramètre en bdd (facultatif)
	 */
	public function __construct($name, $type, $category, $value, $userValue = null, $important = false, $id = null){
		foreach(get_object_vars($this) as $prop => $val){
			if (isset(${$prop})) {
				if (($prop == 'category' and in_array(${$prop}, self::$categories)) or ($prop == 'type' and in_array(${$prop}, array_keys(self::$types))) or !in_array($prop, array('category', 'type'))){
					$this->$prop = ${$prop};
				}
			}
		}
		// On remplace les underscores car ils mettraient le bazar dans la récupération des formulaires
		$this->name = str_replace('_', '-', $this->name);
	}


	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Retourne la valeur du paramètre
	 *
	 * @param bool $getUserValueIfExists Retourne la valeur utilisateur si elle existe et si ce paramètre est à true
	 *
	 * @return mixed
	 */
	public function getValue($getUserValueIfExists = true) {
		return (($getUserValueIfExists and !is_null($this->userValue)) ? $this->userValue : $this->value);
	}

	/**
	 * @return string
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @return boolean
	 */
	public function getImportant() {
		return $this->important;
	}

	/**
	 * @return mixed
	 */
	public function getUserValue() {
		return $this->userValue;
	}

	/**
	 * Valeur du paramètre définie par l'utilisateur
	 * @param mixed $userValue
	 */
	public function setUserValue($userValue) {
		if ($this->type == 'date') $userValue = Sanitize::date($userValue, 'timestamp');
		settype($userValue, self::$types[$this->type]);
		$this->userValue = $userValue;
	}

	/**
	 * Valeur du paramètre définie par l'administrateur
	 * @param mixed $value
	 */
	public function setValue($value) {
		if ($this->type == 'date') $value = Sanitize::date($value, 'timestamp');
		settype($value, self::$types[$this->type]);
		$this->value = $value;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
}

?>