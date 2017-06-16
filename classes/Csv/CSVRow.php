<?php
/**
 * Creator: Dric
 * Date: 16/06/2017
 * Time: 09:53
 */

namespace Csv;


use Logs\Alert;

class CSVRow {

	public function __construct($columns, $line, $delimiter = ';') {
		if (is_array($line)){
			$tab = $line;
		} else {
			$tab = explode($delimiter, $line);
		}
		foreach ($columns as $i => $column){
			$value = $tab[$i];
			if (in_array($value, array('true', 'false'))){
				// Booléen
				$value = ($value == 'true') ? true : false;
			}elseif(is_numeric($value)){
				// numérique
				$value =  $value + 0;
			}
			$this->$column = $value;
		}
	}

	/**
	 * Retourne un tableau avec les noms de colonnes en index associés à leurs valeurs
	 *
	 * @param string[] $columns
	 *
	 * @return array|bool
	 */
	public function getColumns($columns){
		$ret = array();
		foreach ($columns as $column){
			if (!isset($this->$column)){
				new Alert('danger', 'Erreur : la colonne <code>'.$column.'</code> n\'existe pas !');
				return false;
			}
			$ret[$column] = $this->$column;
		}
		return $ret;
	}

}