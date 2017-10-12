<?php
/**
 * Creator: Dric
 * Date: 16/06/2017
 * Time: 09:53
 */

namespace Csv;


use Logs\Alert;

/**
 * Ligne de fichier CSV
 *
 * @package Csv
 */
class CSVRow {

	/** @var null Etat de la ligne (success, warning, danger, info - correspond en fait aux couleurs de Bootstrap) */
	protected $CSVRowState = null;
	/** @var string Id de la ligne */
	protected $CSVRowID = null;
	/** @var array Colonnes de la ligne */
	protected $columns = array();

	/**
	 * Ligne de fichier CSV.
	 *
	 * @param array           $columns    Noms des colonnes
	 * @param string[]|string $line       Valeurs sous forme de chaîne ou tableau
	 * @param string          $delimiter  Séparateur des valeurs
	 * @param string          $ID         ID de la ligne
	 */
	public function __construct($columns, $line, $delimiter = ';', $ID = null) {
		$this->CSVRowID = $ID;
		$this->columns = $columns;
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
	public function filter($columns){
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

	/**
	 * Définit l'état de la ligne (success, warning, danger, info - correspond en fait aux couleurs de Bootstrap)
	 * @param string $state
	 */
	public function setState($state){
		$this->CSVRowState = $state;
	}

	/**
	 * Retourne l'état de la ligne (success, warning, danger, info - correspond en fait aux couleurs de Bootstrap)
	 * @return string
	 */
	public function getState() {
		return $this->CSVRowState;
	}

	/**
	 * Retourne l'ID de la ligne
	 * @return string
	 */
	public function getID() {
		return $this->CSVRowID;
	}

	/**
	 * Retourne les colonnes définies dans la ligne
	 * @return array
	 */
	public function getColumns() {
		return $this->columns;
	}

}