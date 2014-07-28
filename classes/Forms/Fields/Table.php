<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 08:58
 */

namespace Forms\Fields;


use Db\DbTable;
use Forms\Field;

/**
 * Champ spécial permettant de gérer les tables de bases de données
 *
 * @package Forms\Fields
 */
class Table extends Field{

	/**
	 * Type de champ
	 * @var string
	 */
	protected $type = 'dbTable';
	/**
	 * Objet table lié au champ
	 * @var DbTable
	 */
	protected $table = null;

	/**
	 * Déclaration d'une table
	 *
	 * @param DbTable   $table        Objet DbTable
	 * @param string    $ACLLevel     Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param bool      $disabled     Champ désactivé (facultatif)
	 */
	public function __construct(DbTable $table, $ACLLevel = 'admin', $disabled = false){
		$this->table = $table;
		$name = str_replace('_', '-', $table->getName());
		parent::__construct($name, $this->type, $table->getName(), $table->getTitle(), null, $table->getHelp(), null, true, $ACLLevel, $table->getClass(), $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * On fait appel à la méthode d'affichage de la table
	 *
	 * @see \Db\DbTable::display()
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue Afficher la valeur utilisateur au lieu de la valeur globale
	 */
	public function display($enabled = true, $userValue = false){
		$this->table->display();
	}

} 