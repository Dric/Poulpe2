<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 10/07/14
 * Time: 11:07
 */

namespace Db;


use Forms\Pattern;

/**
 * Classe de définition des paramètres de champs dans une table de bdd
 *
 * @package Db
 */
class DbFieldSettings extends Pattern{

	/**
	 * Champ auto-incrémenté
	 * @var bool
	 */
	protected $autoIncrement = false;
	/**
	 * Valeur de l'auto-incrémentation
	 * @var int
	 */
	protected $autoIncrementValue = 0;
	/**
	 * Index et type d'index
	 * @var bool|string
	 */
	protected $index = false;
	/**
	 * Types d'index possibles
	 *
	 * Il ne doit y avoir qu'un seul index primaire défini sur la table, sans quoi le dernier défini est le gagnant !
	 *
	 * @var array
	 */
	protected $indexTypes = array(
		'index',
	  'primary',
	  'unique'
	);
	/**
	 * Définit si le champ fait partie d'un index multiple au niveau de la table. On ne peut définir qu'un seul index multiple pour une table.
	 * @var bool
	 */
	protected $inMultipleIndex = false;
	/**
	 * Objet de contrainte de clé étrangère
	 * @var ForeignKey
	 */
	protected $foreignKey = null;
	/**
	 * Afficher le champ
	 * @var bool
	 */
	protected $show = true;
	/**
	 * Ce champ doit être mis à jour en cas d'insertion sur un index déjà existant
	 * @var bool
	 */
	protected $onDuplicateKeyUpdate = false;

	/**
	 * Définit des paramètres de validation d'un champ de formulaire
	 *
	 * @param string      $type           Type de champ (voir le tableau $types)
	 * @param bool        $required       Champ obligatoire
	 * @param int         $length      Nombre de caractères maximum
	 * @param bool|string $index          Type d'index du champ (false si pas d'index, sinon se référer à $indexTypes)
	 * @param bool        $inMultipleIndex Ce champ fait partie d'un index multiple
	 * @param bool        $autoIncrement  Champ auto-incrémenté en bdd
	 * @param int         $autoIncrementValue  Valeur d'auto-incrémentation (facultatif)
	 * @param ForeignKey  $foreignKey     Contrainte de clé étrangère sur le champ
	 * @param bool        $onDuplicateKeyUpdate Ce champ doit être mis à jour en cas d'insertion sur un index déjà existant
	 * @param bool        $show           Afficher le champ
	 *
	 * @link <http://www.w3schools.com/tags/att_input_type.asp> pour les types de champs acceptés
	 * @link <https://developer.mozilla.org/en/docs/Web/HTML/Element/Input#Browser_compatibility> pour les supports de types de champs par les navigateurs
	 */
	public function __construct($type, $required = false, $length = 0, $index = false, $inMultipleIndex = false, $autoIncrement = false, $autoIncrementValue = 0, ForeignKey $foreignKey = null, $onDuplicateKeyUpdate = false, $show = true){
		if ($length > 255) $length = 255;
		$this->autoIncrement = (bool)$autoIncrement;
		$this->autoIncrementValue = (int)$autoIncrementValue;
		$this->foreignKey = $foreignKey;
		$this->show = (bool)$show;
		$this->onDuplicateKeyUpdate = (bool)$onDuplicateKeyUpdate;
		if ($index !== false and in_array($index, $this->indexTypes)) $this->index = $index;
		if ($this->index !== false) $this->inMultipleIndex = (bool)$inMultipleIndex;
		parent::__construct($type, $required, 0, $length);
	}

	/**
	 * @return boolean
	 */
	public function getAutoIncrement() {
		return $this->autoIncrement;
	}

	/**
	 * @return bool|string
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * @return \Db\ForeignKey|null
	 */
	public function getForeignKey() {
		return $this->foreignKey;
	}

	/**
	 * @return int
	 */
	public function getAutoIncrementValue() {
		return $this->autoIncrementValue;
	}

	/**
	 * @return int
	 */
	public function getLength() {
		return $this->maxLength;
	}

	/**
	 * @return boolean
	 */
	public function getShow() {
		return $this->show;
	}

	/**
	 * @return boolean
	 */
	public function getOnDuplicateKeyUpdate() {
		return $this->onDuplicateKeyUpdate;
	}

	/**
	 * @return boolean
	 */
	public function getInMultipleIndex() {
		return $this->inMultipleIndex;
	}
} 