<?php

namespace Components;
/**
 * Classe de mise en forme de filtre (pour requête sql, pour filtrage de données, etc.)
 *
 * User: cedric.gallard
 * Date: 19/03/14
 * Time: 14:10
 *
 * @package Components
 */
use Logs\Alert;

/**
 * Class Filter
 *
 * @package Components
 */
class Filter {
	protected $key;
	protected $operator;
	protected $value;
	protected $acceptedOperators = array('=', '>', '<', '>=', '<=', '!=', 'in', '!in');
	protected $sortOrder = 'ASC';
	protected $limit = 0;
	protected $fail = false;

	/**
	 * @param string $key Clé à filtrer
	 * @param string $operator Opérateur de comparaison
	 * @param mixed  $value Valeur de comparaison
	 * @param int    $limit Limite max d'items à retourner (0 = pas de limite)
	 * @param string $sortOrder Ordre de tri (ASC ou DESC)
	 */
	public function __construct($key, $operator, $value, $limit = 0, $sortOrder = null){
		$this->key = htmlspecialchars($key);
		if (!in_array($operator, $this->acceptedOperators)){
			new Alert('debug', '<code>Filter Constructor</code> : L\'opérateur <code>'.$operator.'</code> n\'est pas un opérateur accepté');
			$this->fail = true;
		}
		$this->$operator = $operator;
		if ($operator == 'in' or $operator == '!in' and !is_array($value)){
			new Alert('debug', '<code>Filter Constructor</code> : L\'opérateur <code>'.$operator.'</code> demande un tableau comme entrée de valeur !');
			$this->fail = true;
		}
		if ($operator != 'in' and $operator != '!in' and is_array($value)){
			new Alert('debug', '<code>Filter Constructor</code> : L\'opérateur <code>'.$operator.'</code> ne demande pas de tableau comme entrée de valeur !');
			$this->fail = true;
		}
		$this->value = $value;
		$this->sortOrder = (in_array($sortOrder, array('ASC', 'DESC'))) ? $sortOrder : 'ASC';
	}

	public function __get($var){
		if ($this->fail){
			return null;
		}
		switch ($var){
			case 'key':
				return $this->key;
			case 'operator':
			case 'op':
				return $this->operator;
			case 'value':
				return $this->value;
			default:
				return null;
		}
	}

	/**
	 * Crée un argument pour MySQL - Ne prend pas en compte les arguments $sortOrder et $limit
	 * @return string|bool
	 */
	public function mysql(){
		if ($this->fail) return false;
		$ret = $this->key;
		if ($this->operator == '!in'){
			$ret .= ' NOT IN('.implode(', ', $this->value).')';
		}elseif ($this->operator == 'in'){
			$ret .= ' IN('.implode(', ', $this->value).')';
		}else{
			$ret .= ' '.$this->operator.' '.$this->value;
		}
		return $ret;
	}

	/**
	 * Vérifie qu'une valeur est acceptée
	 * @param mixed $value Valeur à tester
	 *
	 * @return bool
	 */
	public function accept($value){
		if ($this->fail) return false;
		switch ($this->operator){
			case '=':
				return ($value != $this->value) ? false : true;
			case '>':
				return ($value <= $this->value) ? false : true;
			case '<':
				return ($value >= $this->value) ? false : true;
			case '>=':
				return ($value < $this->value) ? false : true;
			case '<=':
				return ($value > $this->value) ? false : true;
			case '!=':
				return ($value == $this->value) ? false : true;
			case 'in':
				return (!in_array($value, $this->value)) ? false : true;
			case '!in':
				return (in_array($value, $this->value)) ? false : true;
		}
		return false;
	}

	/**
	 * Filtre un tableau d'objets, le classe selon l'ordre de tri et retourne le nombre d'éléments demandés
	 *
	 * @warning Si le tableau d'objets en entrée est associatif, les clés seront perdues dans l'opération
	 *
	 * @param array $objects tableau d'objets contenant une propriété ou une méthode en rapport avec l'argument $key utilisé pour instancier la classe Filter
	 *
	 * @return array
	 */
	public function Objects($objects){
		if ($this->fail) return null;
		$retObjects = array();
		$key = $this->key;
		foreach ($objects as $object){
			if (!isset($object->$key)){
				if ($this->accept($object->{'get'.ucfirst($key)})){
					$retObjects[] = $object;
				}
			}else{
				if ($this->accept($object->$key)){
					$retObjects[] = $object;
				}
			}
		}
		if ($this->limit > 0){
			$retObjects = Sanitize::sortObjectList($retObjects, $key, $this->sortOrder);
			return array_slice($retObjects, 0, $this->limit);
		}else{
			return Sanitize::sortObjectList($retObjects, $key, $this->sortOrder);
		}
	}
} 