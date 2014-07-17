<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 08:58
 */

namespace Forms\Fields;


use Components\Help;
use Db\DbTable;
use Forms\Field;
use Forms\JSSwitch;

class Table extends Field{

	protected $type = 'dbTable';
	/**
	 * @var DbTable
	 */
	protected $table = null;

	/**
	 * Déclaration d'une table
	 *
	 * @param DbTable   $table        Objet DbTable
	 * @param string    $category     Catégorie du champ (global ou user)
	 * @param string    $ACLLevel     Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param bool      $disabled     Champ désactivé (facultatif)
	 */
	public function __construct(DbTable $table, $category, $ACLLevel = 'admin', $disabled = false){
		$this->table = $table;
		$name = str_replace('_', '-', $table->getName());
		parent::__construct($name, $this->type, $category, $table->getName(), $table->getTitle(), null, null, $table->getHelp(), null, null, true, $ACLLevel, $table->getClass(), $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue
	 */
	public function display($enabled = true, $userValue = false){
		$this->table->display();
	}

} 