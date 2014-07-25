<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 10/07/14
 * Time: 11:41
 */

namespace Db;

/**
 * Class De définition d'une clé étrangère
 *
 * @package Db
 */
class ForeignKey {

	/**
	 * Nom de la table contenant la référence de clé étrangère
	 * @var string
	 */
	protected $table = null;
	/**
	 * Nom de la clé étrangère
	 * @var string
	 */
	protected $key = null;
	/**
	 * Action en cas de mise à jour sur le champ de référence
	 * @var string
	 */
	protected $onUpdate = 'NO ACTION';
	/**
	 * Action en cas de suppression du champ de référence
	 * @var string
	 */
	protected $onDelete = 'NO ACTION';
	/**
	 * Tableau des actions autorisées
	 * @var string[]
	 */
	protected $actions = array(
		'RESTRICT',
		'CASCADE',
		'SET NULL',
		'NO ACTION'
	);

	/**
	 * Construction de la clé étrangère
	 *
	 * @param string $table     Nom de la table contenant la référence de clé étrangère
	 * @param string $key       Nom de la clé étrangère
	 * @param string $onUpdate  Action en cas de mise à jour sur le champ de référence (facultatif)
	 * @param string $onDelete  Action en cas de suppression du champ de référence (facultatif)
	 */
	public function __construct($table, $key, $onUpdate = 'NO ACTION', $onDelete = 'NO ACTION'){
		$this->table = $table;
		$this->key = $key;
		if (in_array($onUpdate, $this->actions)) $this->onUpdate = $onUpdate;
		if (in_array($onDelete, $this->actions)) $this->onDelete = $onDelete;
	}

	/**
	 * Retourne la table contenant la clé étrangère
	 * @return null
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * retourne la clé étrangère
	 * @return null
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Retourne le comportement sur mise à jour d'un item maître
	 * @return string
	 */
	public function getOnUpdate() {
		return $this->onUpdate;
	}

	/**
	 * Retourne le comportement sur suppression d'un item maître
	 * @return string
	 */
	public function getOnDelete() {
		return $this->onDelete;
	}

} 