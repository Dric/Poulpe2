<?php
/**
 * Creator: Dric
 * Date: 16/06/2017
 * Time: 09:12
 */

namespace Csv;

use FileSystem\Fs;
use Forms\PostedData;
use Logs\Alert;
use Sanitize;

/**
 * Classe de gestion d'un fichier Csv
 *
 * @package Csv
 *
 * @property-read bool      isValid   Retourne true si le fichier est correctement chargé et exploitable, false sinon.
 * @property-read string[]  columns   Liste des colonnes du CSV.
 * @property-read CSVRow[]  rows      Lignes du CSV.
 * @property-read string    fileName  Nom du fichier CSV avec son chemin.
 * @property-read string    formName  Nom du fichier CSV sans son chemin.
 * @property-read string    delimiter Délimiteur du fichier CSV.
 * @property-read array     titles    Titres à afficher pour les colonnes du fichier CSV.
 */
class Csv {

	/** @var string[] Liste des colonnes */
	protected $columns = array();

	/** @var string Chemin et nom du fichier CSV */
	protected $fileName = null;

	/** @var string Chemin du fichier */
	protected $path = null;

	/** @var string Nom du fichier, sans son chemin */
	protected $file = null;

	/** @var CSVRow[] Lignes du CSV */
	protected $rows = array();

	/** @var string Délimiteur du CSV */
	protected $delimiter = ';';

	/** @var bool CSV correctement chargé */
	protected $isValid = false;

	/** @var string Colonne des ID */
	protected $colID = null;

	/** @var array Entêtes de colonnes (pour affichage/édition)  */
	protected $titles = array();

	/**
	 * Classe de fichier CSV.
	 *
	 * @param string  $file       Nom du fichier CSV
	 * @param string  $delimiter  Délimiteur dans le fichier CSV
	 * @param null    $colID      Colonne des ID si existante. Si une colonne représente un identifiant unique pour le tableau CSV, le fait de l'indiquer ici permettra de retrouver plus facilement une ligne grâce à son identifiant.
	 * @param array   $titles     Entêtes de colonnes sous forme tableau associatif position => titre, ou bien <entête dans le fichier> => titre
	 */
	public function __construct($file, $delimiter = ';', $colID = null, Array $titles = null) {
		$this->fileName   = $file;
		$this->delimiter  = $delimiter;
		$this->colID      = $colID;
		preg_match('/(.*)\\\(.*\.csv)/i', $file, $matches);
		list(, $this->path, $this->file) = $matches;
		$fs = New Fs($this->path);
		if ($fs) {
			$file = $fs->readFile($this->file);
			if ($file) {
				foreach ($file as $i => $line) {
					if ($i == 0) {
						// On traite les entêtes
						$this->columns = explode($this->delimiter, $line);
					} elseif (!is_null($this->colID)) {
						$row = explode($delimiter, $line);
						$colIndex = array_search($this->colID, $this->columns);
						$this->rows[$row[$colIndex]] = new CSVRow($this->columns, $line, $this->delimiter, $row[$colIndex]);
					} else {
						$this->rows[] = new CSVRow($this->columns, $line, $this->delimiter);
					}
				}
				if (!empty($titles)){
					if (is_numeric(key($titles)) and key($titles) == 0){
						if (count($titles) == count($this->columns)){
							$this->titles = array_combine($this->columns, $titles);
						} else {
							new Alert('error', 'Erreur : les titres de colonnes ne correspondent pas au nombre de colonnes du tableau ! Les titres seront ignorés.');
							$this->titles = array_combine($this->columns, $this->columns);
						}
					} else {
						$titlesInError = false;
						foreach ($titles as $col => $title) {
							if (!in_array($col, $this->columns)) {
								new Alert('error', 'Erreur : la colonne <code>' . $col . '</code> n\'existe pas dans le tableau ! Les titres seront ignorés.');
								$titlesInError = true;
								break;
							}
							$this->titles[$col] = $title;
						}
						if ($titlesInError) {
							$this->titles = array_combine($this->columns, $this->columns);
						}
					}
				} else {
					$this->titles = array_combine($this->columns, $this->columns);
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
				case 'fileName':
				case 'isValid':
				case 'delimiter':
				case 'titles':
					return $this->$prop;
				case 'formName':
					return str_replace('.', '', Sanitize::SanitizeFileName($this->file));
				default:
					if (in_array($prop, $this->columns)){
						return $this->getColumns($prop);
					}
			}
		}
		return false;
	}

	/**
	 * Ajoute une ligne au tableau (sans faire de tri après)
	 *
	 * @param array $values Tableau de liste des valeurs classées par ordre de colonne
	 * @param null  $ID ID de la ligne
	 *
	 * @return bool
	 *
	 */
	public function addRow(Array $values, $ID = null){
		if (!$this->isValid) return false;
		if (count($values) != count($this->columns)){
			new Alert('error', 'Erreur : le nombre de colonnes de la ligne à ajouter n\'est pas identique au nombre de colonnes dans le tableau !');
			return false;
		}
		$rowAdded = new CSVRow($this->columns, $values, $this->delimiter, $ID);
		if (!is_null($ID)){
			if (isset($this->rows[$ID])){
				new Alert('error', 'Erreur : Impossible d\'ajouter la ligne ayant l\'ID <code>'.$ID.'</code> car cette ID existe déjà dans le tableau !');
				return false;
			}
			$this->rows[$ID] = $rowAdded;
		} else {
			$this->rows[] = $rowAdded;
		}
		return true;
	}

	/**
	 * Supprime une ligne via son ID
	 *
	 * @param $ID
	 *
	 * @return bool
	 */
	public function deleteRow($ID){
		if (!$this->isValid) return false;
		if (!array_key_exists($ID, $this->rows)){
			new Alert('error', 'Erreur : l\'ID <code>'.$ID.'</code> n\'existe pas !');
			return false;
		}
		if (!isset($this->rows[$ID])){
			new Alert('error', 'Erreur : cette ligne n\'existe pas !');
			return false;
		}
		unset($this->rows[$ID]);
		return true;
	}

	/**
	 * Supprime des lignes dans le tableau suivant certains critères
	 *
	 * @param array|null $where
	 *
	 * $where est composé de la sorte :
	 *  'col' : colonne
	 *  'op'  : opérande (>, <, =, ==, >=, <=, !=, ~ (like))
	 *  'val' : valeur (null pour une valeur vide)
	 *  'log' : opérateur logique (and ou or)
	 *
	 * Ex : array(
	 *        array(
	 *          'col' => 'Nom',
	 *          'op'  => '=',
	 *          'val' => 'John',
	 *          'log' => 'and'
	 *         ),
	 *        array(
	 *          'col' => 'age',
	 *          'op'  => '>',
	 *          'val' => '13',
	 *          'log' => 'and'
	 *         )
	 *       )
	 *
	 * @return int Nombre de lignes affectées
	 */
	public function deleteRows(Array $where){
		if (!$this->isValid) return 0;
		$nb = 0;
		$matchingRows = $this->matchingRows($where);
		foreach ($matchingRows as $id){
			$this->deleteRow($id);
			$nb++;
		}
		return $nb;
	}

	/**
	 * Met à jour des lignes dans le tableau suivant certains critères
	 *
	 * @param array      $values
	 * @param array|null $where
	 *
	 * $where est composé de la sorte :
	 *  'col' : colonne
	 *  'op'  : opérande (>, <, =, ==, >=, <=, !=, ~ (like))
	 *  'val' : valeur (null pour une valeur vide)
	 *  'log' : opérateur logique (and ou or)
	 *
	 * Ex : array(
	 *        array(
	 *          'col' => 'Nom',
	 *          'op'  => '=',
	 *          'val' => 'John',
	 *          'log' => 'and'
	 *         ),
	 *        array(
	 *          'col' => 'age',
	 *          'op'  => '>',
	 *          'val' => '13',
	 *          'log' => 'and'
	 *         )
	 *       )
	 *
	 * @return int Nombre de lignes affectées
	 */
	public function updateRows(Array $values, Array $where = null){
		if (!$this->isValid) return 0;
		$nb = 0;
		foreach ($values as $col => $val){
			if (!in_array($col, $this->columns)){
				new Alert('error', 'Erreur : la colonne <code>'.$col.'</code> n\'existe pas dans le tableau !');
				return 0;
			}
		}
		$matchingRows = $this->matchingRows($where);
		foreach ($matchingRows as $id){
			$this->updateRow($id, $values);
			$nb++;
		}
		return $nb;
	}

	/**
	 * Met à jour une ligne dans le tableau
	 *
	 * @param       $ID
	 * @param array $values
	 *
	 * @return bool
	 */
	public function updateRow($ID, Array $values){
		if (!$this->isValid) return false;
		if (!array_key_exists($ID, $this->rows)){
			new Alert('error', 'Erreur : l\'ID <code>'.$ID.'</code> n\'existe pas !');
			return false;
		}
		foreach ($values as $col => $val){
			if (!in_array($col, $this->columns)){
				new Alert('error', 'Erreur : la colonne <code>'.$col.'</code> n\'existe pas dans le tableau !');
				return false;
			}
			$this->rows[$ID]->$col = $val;
		}
		return true;
	}

	/**
	 * Retourne les ids des lignes répondant aux critères
	 *
	 * @param array $crits
	 *
	 * @return array
	 */
	protected function matchingRows(Array $crits){
		if (!$this->isValid) return null;
		$ret = array();
		if (empty($crits)){
			return $this->rows;
		}
		foreach ($this->rows as $i => $row){
			$isValid = false;
			foreach ($crits as $col => $tab){
				if ($this->matchCriteria($row, $tab)){
					$isValid = true;
				} elseif (strtolower($tab['log']) == 'and') {
					$isValid = false;
					break;
				}
			}
			if ($isValid){
				$ret[] = $i;
			}
		}
		return $ret;
	}

	/**
	 * Vérifie qu'une ligne répond aux critères demandés
	 *
	 * @param CSVRow $row
	 * @param array  $crit
	 *
	 * $crit est composé de la sorte :
	 *  'col' : colonne
	 *  'op'  : opérande (>, <, =, ==, >=, <=, !=, ~ (like))
	 *  'val' : valeur (null pour une valeur vide)
	 *  'log' : opérateur logique (and ou or)
	 *
	 * Ex : array(
	 *        array(
	 *          'col' => 'Nom',
	 *          'op'  => '=',
	 *          'val' => 'John',
	 *          'log' => 'and'
	 *         ),
	 *        array(
	 *          'col' => 'age',
	 *          'op'  => '>',
	 *          'val' => '13',
	 *          'log' => 'and'
	 *         )
	 *       )
	 *
	 * @return bool
	 */
	protected function matchCriteria(CSVRow $row, Array $crit){
		if (!$this->isValid) return false;
		if (!in_array($crit['col'], $this->columns)){
			new Alert('error', 'Erreur : le critère sur la colonne <code>'.$crit['col'].'</code> est impossible car celle-ci n\'existe pas dans le tableau !');
			return false;
		}
		if (!in_array(strtolower($crit['log']), array('and', 'or'))){
			new Alert('error', 'Erreur : le critère sur la colonne <code>'.$crit['col'].'</code> est impossible car l\'opérateur logique <code>'.$crit['log'].'</code> n\'existe pas !');
			return false;
		}
		if (\Check::compare($row->${$crit['col']}, $crit['op'], $crit['val'])){
			return true;
		}
		return false;
	}

	/**
	 * Vérifie que deux tableaux ont des valeurs identiques
	 * @param $arrayA
	 * @param $arrayB
	 *
	 * @return bool
	 */
	protected function identicalValues( $arrayA , $arrayB ) {

		sort( $arrayA );
		sort( $arrayB );

		return $arrayA == $arrayB;
	}

	/**
	 * Trie le tableau CSV.
	 *
	 * Attention :
	 *  Contrairement à search(), sort() change l'ordre des lignes du tableau et ne renvoie rien.
	 *  Le tri va détruire les index du tableau. Si vous souhaitez ordonner le tableau sans toucher aux index, utilisez plutôt search('*', null, $sort).
	 *
	 *  sort() doit être utilisé dans l'optique de sauvegarder les résultats dans le fichier CSV.
	 *
	 * @param array $sort  Colonnes sur lesquelles trier le tableau sous forme de tableau associatif nom de colonne => sens du tri (asc ou desc)
	 *
	 * @return bool
	 */
	public function sort(Array $sort){
		if (!$this->isValid) return false;
		foreach ($sort as $col => $order){
			if (!in_array($col, $this->columns)){
				new Alert('error', 'Erreur : la colonne <code>'.$col.'</code> sur laquelle faire le tri n\'existe pas dans le tableau !');
				return false;
			}
		}

		$colSort  = array_keys($sort);
		$colOrder = array_values($sort);

		$ret = \Sanitize::sortObjectList($this->rows, $colSort, $colOrder);

		if (empty($ret)){
			return false;
		}
		$this->rows = $ret;
		return true;
	}

	/**
	 * Retourne les valeurs de la ou des colonnes demandées
	 *
	 * @param array|string $columns
	 *
	 * @param array $sort Colonnes sur lesquelles trier le tableau sous forme de tableau associatif nom de colonne => sens du tri (asc ou desc)
	 *
	 * @return CSVRow[]
	 */
	public function getColumns($columns, Array $sort = null){
		return ($this->isValid) ? $this->search($columns, null, $sort) : null;
	}

	/**
	 * Retourne la ligne ayant pour identifiant $ID
	 *
	 * @param mixed $ID ID de la ligne
	 *
	 * @return CSVRow
	 */
	public function getRow($ID){
		if (!array_key_exists($ID, $this->rows)){
			new Alert('error', 'Erreur : l\'ID <code>'.$ID.'</code> n\'existe pas !');
			return null;
		}
		return ($this->isValid) ? $this->rows[$ID] : null;
	}

	/**
	 * Retourne les lignes qui correspondent au tableau d'IDs
	 *
	 * @param array $IDs Liste des IDs
	 *
	 * @param array $sort Tableau associatif de tri (colonne en index, sens - asc ou desc - en valeur)
	 *
	 * @return array
	 */
	public function getRows(Array $IDs, Array $sort = null){
		if (!$this->isValid) return null;
		$ret = array();
		foreach ($IDs as $ID){
			$ret[$ID] = $this->rows[$ID];
		}
		if (!is_null($sort)){
			foreach ($sort as $col => $order){
				if (!in_array($col, $this->columns)){
					new Alert('error', 'Erreur : la colonne <code>'.$col.'</code> sur laquelle faire le tri n\'existe pas dans le tableau !');
					return array();
				}
			}
			$colOrder = array_keys($sort);
			$colSort  = array_values($sort);
			$ret = \Sanitize::sortObjectList($ret, $colSort, $colOrder);
		}
		return $ret;
	}

	/**
	 * Effectue une recherche dans le tableau et retourne les lignes trouvées, avec possibilité de tri
	 *
	 * Le tri supprime l'index du tableau des résultats, mais on peut toujours accéder à l'ID de la ligne avec $row->getID()
	 *
	 * @param string  $select Tableau des colonnes à sélectionner
	 * @param array   $where  Critères de sélection des lignes
	 * @param array   $sort   Tableau associatif de tri des colonnes array(<colonne> => <sens du tri (asc, desc)>)
	 *
	 * @return array
	 *
	 * $where est composé de la sorte :
	 *  'col' : colonne
	 *  'op'  : opérande (>, <, =, ==, >=, <=, !=, ~ (like))
	 *  'val' : valeur (null pour une valeur vide)
	 *  'log' : opérateur logique (and ou or)
	 *
	 * Ex : array(
	 *        array(
	 *          'col' => 'Nom',
	 *          'op'  => '=',
	 *          'val' => 'John',
	 *          'log' => 'and'
	 *         ),
	 *        array(
	 *          'col' => 'age',
	 *          'op'  => '>',
	 *          'val' => '13',
	 *          'log' => 'and'
	 *         )
	 *       )
	 *
	 */
	public function search($select = '*', Array $where = null, Array $sort = null){
		if (!$this->isValid) return null;

		$matchingRows = (!empty($where)) ? $this->matchingRows($where) : array_keys($this->rows);

		if ($select == '*'){
			return (!empty($where)) ? $this->getRows($matchingRows, $sort) : $this->rows;
		}
		if (!is_array($select)){
			$select = array($select);
		}
		foreach ($select as $colReq){
			if (!in_array($colReq, $this->columns)){
				new Alert('error', 'Erreur : la colonne <code>'.$colReq.'</code> n\'existe pas dans le tableau !');
				return array();
			}
		}
		$rows = (!empty($where)) ? $this->getRows($matchingRows) : $this->rows;
		$ret = array();
		foreach ($rows as $row){
			$colVal = array();
			foreach ($select as $col){
				$colVal[] = $row->$col;
			}
			$ret[$row->getID()] = new CSVRow($select, $colVal, $this->delimiter, $row->getID());
		}
		if (!is_null($sort)){
			foreach ($sort as $col => $order){
				if (!in_array($col, $this->columns)){
					new Alert('error', 'Erreur : la colonne <code>'.$col.'</code> sur laquelle faire le tri n\'existe pas dans le tableau !');
					return array();
				}
			}
			$colOrder = array_keys($sort);
			$colSort  = array_values($sort);
			$ret = \Sanitize::sortObjectList($ret, $colSort, $colOrder);
		}
		return $ret;
	}

	/**
	 * Sauvegarde un fichier CSV
	 *
	 * @return bool
	 */
	public function saveToFile(){
		if (!$this->isValid) return false;
		$toSave = array();
		$toSave[] = implode($this->delimiter, $this->columns);
		foreach ($this->rows as $row){
			$toSave[] = implode($this->delimiter, $row->filter($this->columns));
		}
		$fs = New Fs($this->path);
		if ($fs) {
			return $fs->writeFile($this->file, $toSave);
		}else{
			New Alert('error', 'Erreur : Impossible de récupérer le répertoire de sauvegarde du fichier !');
			return false;
		}
	}

	/**
	 * Affiche le fichier CSV
	 *
	 * @param bool    $edit
	 * @param string  $action Valeur du bouton action (savePostes par ex)
	 *
	 * On ne s'occupe pas du traitement du formulaire dans cette classe car on ne sait pas quelles validations faire sur les champs.
	 * Il faut donc créer une méthode dans la classe appelante qui va s'occuper de valider les valeurs saisies puis de renvoyer le tout à cette classe pour sauvegarde.
	 */
	public function display($edit = false, $action) {
		$formName = str_replace('.', '', Sanitize::SanitizeFileName($this->file));
		?>
		<div class="row">
			<div class="col-md-6">
				<form method="post">
					<table class="table table-bordered table-striped">
						<thead>
							<tr class="<?php if ($edit){ echo 'tr_dbTable_header'; }?>">
								<?php
								foreach ($this->titles as $title){
									?><th><?php echo $title; ?></th><?php
								}
								?>
							</tr>
						</thead>
						<tbody>
						<?php
						$i = 0;
						if ($edit){
							foreach ($this->rows as $id => $row){
								?>
								<tr class="tr_dbTable" id="tr_<?php echo $formName; ?>_<?php echo $id; ?>">
									<?php
									foreach ($this->columns as $column){
										?><td><input type="text" class="form-control" name="dbTable_<?php echo $formName; ?>_string_<?php echo $column; ?>_<?php echo $id; ?>" value="<?php echo $row->$column; ?>"></td><?php
									}
									?>
								</tr>
								<?php
								$i++;
							}
							?>
							<tr id="tr_new">
								<?php
								foreach ($this->columns as $column){
									?><td><input type="text" class="form-control" name="dbTable_<?php echo $formName; ?>_string_<?php echo $column; ?>_new"></td><?php
								}
								?>
								<td></td>
							</tr>
						<?php }else{
							foreach ($this->rows as $id => $row){
								?><tr><?php
								foreach ($this->columns as $column) {
									?>
									<td><?php echo $row->$column; ?></td>
									<?php
								}
									?></tr><?php
							}
						}
						?>
						</tbody>
					</table>
					<noscript><span class="help-block">Pour supprimer une ligne, il vous suffit d'effacer toutes les valeurs contenues dans celle-ci.</span></noscript>
					<?php if ($edit) { ?><button name="action" value="<?php echo $action; ?>" class="btn btn-primary">Sauvegarder</button><?php }else{ ?>
						<div class="alert alert-warning">Vous pouvez visualiser les valeurs mais pas les modifier car vous n'avez pas les droits pour le faire.</div>
					<?php } ?>
					<?php
					$token = PostedData::setToken($formName);
					?>
					<input id="field_hidden_token" name="field_hidden_token" value="<?php echo $token; ?>" type="hidden">
					<input id="field_hidden_formName" name="field_hidden_formName" value="<?php echo $formName; ?>" type="hidden">
				</form>
			</div>
		</div>
		<div class="row"></div>
		<?php
	}
}