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
	 * @var string[]
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
	 * @param bool        $required       Spécifie un champ obligatoire (facultatif)
	 * @param int         $length      Nombre de caractères maximum (facultatif)
	 * @param bool|string $index          Type d'index du champ (false si pas d'index, sinon se référer à $indexTypes) (facultatif)
	 * @param bool        $inMultipleIndex Ce champ fait partie d'un index multiple (facultatif)
	 * @param bool        $autoIncrement  Champ auto-incrémenté en bdd (facultatif)
	 * @param int         $autoIncrementValue  Valeur d'auto-incrémentation (facultatif)
	 * @param ForeignKey  $foreignKey     Contrainte de clé étrangère sur le champ (facultatif)
	 * @param bool        $onDuplicateKeyUpdate Ce champ doit être mis à jour en cas d'insertion sur un index déjà existant (facultatif)
	 * @param bool        $show           Afficher le champ (facultatif)
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
		// On ne peut mettre un champ dans un index multiple que si celui-ci est déjà indexé
		if ($this->index !== false) $this->inMultipleIndex = (bool)$inMultipleIndex;
		parent::__construct($type, $required, 0, $length);
	}

	/**
	 * Retourne le statut d'auto-incrémentation
	 * @return boolean
	 */
	public function getAutoIncrement() {
		return $this->autoIncrement;
	}

	/**
	 * Retourne le statut d'index
	 * @return bool|string
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Retourne la clé étrangère liée au champ
	 * @return \Db\ForeignKey|null
	 */
	public function getForeignKey() {
		return $this->foreignKey;
	}

	/**
	 * Retourne la valeur de l'auto-incrémentation
	 * @return int
	 */
	public function getAutoIncrementValue() {
		return $this->autoIncrementValue;
	}

	/**
	 * Retourne la longueur du champ
	 * @return int
	 */
	public function getLength() {
		return $this->maxLength;
	}

	/**
	 * Retourne le statut d'affichage du champ lors de l'affichage de la table
	 * @return boolean
	 */
	public function getShow() {
		return $this->show;
	}

	/**
	 * Retourne le statut de mise en jour en cas d'index existant
	 * @return boolean
	 */
	public function getOnDuplicateKeyUpdate() {
		return $this->onDuplicateKeyUpdate;
	}

	/**
	 * Retourne le statut d'appartenance à un index multiple
	 * @return boolean
	 */
	public function getInMultipleIndex() {
		return $this->inMultipleIndex;
	}
} 