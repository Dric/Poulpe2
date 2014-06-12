<?php
/**
 * Classe de mise en forme d'argument (pour requête sql, pour filtre, etc.)
 *
 * User: cedric.gallard
 * Date: 19/03/14
 * Time: 14:10
 *
 * @package Sanitize
 */

/**
 * Class Arg
 *
 * @package Sanitize
 */
class Arg {
	protected $key;
	protected $operator;
	protected $value;
	protected $acceptedOperators = array('=', '>', '<', '>=', '<=', '!=', 'in', '!in');

	public function __construct($key, $operator, $value){
		$this->key = htmlspecialchars($key);
		if (!in_array($operator, $this->acceptedOperators))	return false;
		$this->$operator = $operator;
		if ($operator == 'in' or $operator == '!in' and !is_array($value)) return false;
		if ($operator != 'in' and $operator != '!in' and is_array($value)) return false;
		$this->value = $value;
	}

	public function __get($var){
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

	public function mysql(){
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
} 