<?php
/**
 * Creator: Dric
 * Date: 16/06/2017
 * Time: 09:12
 */

namespace Csv;

use FileSystem\Fs;
use Logs\Alert;

/**
 * Classe de gestion d'un fichier Csv
 *
 * @package Csv
 *
 * @property-read bool      isValid Retourne true si le fichier est correctement chargé et exploitable, false sinon.
 * @property-read string[]  columns Liste des colonnes du CSV.
 * @property-read CSVRow[]  rows    Lignes du CSV.
 */
class Csv {

	/** @var string[] Liste des colonnes */
	protected $columns = array();

	/** @var string Chemin et nom du fichier CSV */
	protected $fileName = null;

	/** @var CSVRow[] Lignes du CSV */
	protected $rows = array();

	/** @var string Délimiteur du CSV */
	protected $delimiter = ';';

	/** @var bool CSV correctement chargé */
	protected $isValid = false;

	/**
	 * Classe de fichier CSV.
	 *
	 * @param string $file Nom du fichier CSV
	 * @param string $delimiter Délimiteur dans le fichier CSV
	 */
	public function __construct($file, $delimiter = ';') {
		$this->fileName = $file;
		$this->delimiter = $delimiter;
		preg_match('/(.*)\\\(.*\.csv)/i', $file, $matches);
		list(, $path, $fileName) = $matches;
		$fs = New Fs($path);
		if ($fs) {
			$file = $fs->readFile($fileName);
			if ($file) {
				foreach ($file as $i => $line) {
					if ($i == 0) {
						// On traite les entêtes
						$this->columns = explode($this->delimiter, $line);
					} else {
						$this->rows[] = new CSVRow($this->columns, $line, $this->delimiter);
					}
				}
				$this->isValid = true;
			}
		} else {
			New Alert('error', 'Erreur : impossible de charger le fichier CSV !');
		}
	}

	/**
	 * Retourne les propriétés ou les colonnes du fichier CSV
	 *
	 * Si on appelle une des colonnes, la méthode va retourner un tableau contenant toutes les valeurs de cette colonne (comme Powershell)
	 *
	 * @param string $prop Propriété ou colonne à retourner
	 *
	 * @return array|bool
	 */
	public function __get($prop){
		if ($this->isValid){
			switch ($prop){
				case 'rows':
				case 'columns':
				case 'name':
				case 'isValid':
					return $this->$prop;
				default:
					if (in_array($prop, $this->columns)){
						$ret = array();
						/** @var CSVRow $row */
						foreach ($this->rows as $row){
							$ret[] = $row->$prop;
						}
						return $ret;
					}
			}
		}
		return false;
	}

	public function sort($columns, $order  = 'ASC'){
		if (!is_array($columns)){
			$columns = array($columns);
		}
		$colOrder = (is_array($order)) ? $order : array();

		foreach ($columns as $i => $column){
			if (!isset($this->columns[$column])){
				New Alert('error', 'Erreur : la colonne <code>'.$column.'</code> n\'existe pas !');
				return false;
			}
			if (!is_array($order)){
				$colOrder[] = $order;
			}
		}

		$this->rows = \Sanitize::sortObjectList($this->rows, $columns, $colOrder);
		return true;
	}

	/**
	 * Retourne les valeurs de la ou des colonnes demandées
	 *
	 * @param $columns
	 *
	 * @return CSVRow[]
	 */
	public function getColumns($columns){
		return $this->filter($columns);
	}

	/**
	 * Effectue une filtrage sur les données du CSV
	 *
	 * @param string|string[]      $columns   Colonnes à récupérer (ALL_COLUMNS pour retourner rtoutes les colonnes)
	 * @param array|string         $request   Tableau de requête (colonne => valeur cherchée) ou bien valeur à chercher dans toutes les colonnes - La recherche peut s'effectuer sur des colonnes non retournées
	 * @param null|string|string[] $sort      Critères de tri (peut être sur une ou plusieurs colonnes, laisser à vide si pas de tri)
	 * @param null|string|string[] $sortOrder Ordre du tri (si valeur simple, ordre général du tri, sinon un tableau correspondant aux colonnes de tri)
	 *
	 * @return CSVRow[]
	 */
	public function filter($columns, $request = array(), $sort = null, $sortOrder = 'ASC'){
		if ($columns == 'ALL_COLUMNS'){
			$columns = $this->columns;
		}elseif (!is_array($columns)){
			$columns = array($columns);
		}

		/** @var CSVRow[] $filtered */
		$filtered = array();
		/** @var CSVRow $row */
		foreach ($this->rows as $row){
			$ignore = false;
			if (!empty($request)) {
				if (!is_array($request)) {
					$rowIgnore = true;
					foreach($this->columns as $column) {
						if (stristr($row->$column, $request) !== false) {
							$rowIgnore = false;
						}
					}
					$ignore = $rowIgnore;
				} else {
					foreach ($request as $searchCol => $searchValue) {
						// Cette fonction est l'équivalent d'un like en SQL ou powershell
						if (stristr($row->$searchCol, $searchValue) === false) {
							$ignore = true;
						}
					}
				}
			}
			if (!$ignore) $filtered[] = new CSVRow($columns, array_values($row->getColumns($columns)), $this->delimiter);
		}
		if (!is_null($sort) and !empty($filtered)){
			if (!is_array($sort)){
				$sort = array($sort);
			}
			$colOrder = (is_array($sortOrder)) ? $sortOrder : array();

			if (!is_array($sortOrder)){
				for ($i = 0;$i <= count($sort)-1;$i++){
					$colOrder[] = $sortOrder;
				}
			}
			$filtered = \Sanitize::sortObjectList($filtered, $sort, $colOrder);
		}

		return $filtered;
	}

	/**
	 * Sauvegarde un fichier CSV
	 *
	 * @return bool
	 */
	public function saveToFile(){
		$toSave = array();
		$toSave[] = implode($this->delimiter, $this->columns);
		foreach ($this->rows as $row){
			$toSave[] = implode($this->delimiter, $row->getColumns($this->columns));
		}
		$fs = New Fs(dirname($this->fileName));
		if ($fs) {
			return $fs->writeFile(basename($this->fileName), $toSave);
		}else{
			New Alert('error', 'Erreur : Impossible de récupérer le répertoire de sauvegarde du fichier !');
			return false;
		}
	}
}