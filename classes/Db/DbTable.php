<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 10/07/14
 * Time: 09:46
 */

namespace Db;


use Components\Help;
use Forms\Field;
use Logs\Alert;
use Sanitize;

/**
 * Objet de table de base de données
 *
 * Les champs de la table sont des objets de type Field, afin de pouvoir gérer leur affichage et leur typage.
 * Cela permet également pour la gestion automatique des enregistrements de table de générer un formulaire possédant automatiquement les restrictions de saisie requises
 *
 * @package Db
 */
class DbTable {

	/**
	 * Nom de la table en bdd
	 * @var string
	 */
	protected $name = null;
	/**
	 * Tableau d'objets Field
	 * @var Field[]
	 */
	protected $fields = array();
	/**
	 * Classe CSS optionnelle à appliquer à la table
	 * @var string
	 */
	protected $class = null;
	/**
	 * Titre à afficher au dessus de la table
	 * @var string
	 */
	protected $title = null;
	/**
	 * Aide optionnelle à afficher à côté du titre en infobulle
	 * @var string
	 */
	protected $help = null;

	/**
	 * Construction d'une table de bdd
	 *
	 * @param string  $name     Nom de la table
	 * @param string  $title    Titre lorsqu'on affiche la table
	 * @param string  $class    Classe CSS optionnelle à appliquer à la table à l'affichage (facultatif)
	 * @param string  $help     Aide optionnelle affichée en infobulle à côté du titre (facultatif)
	 */
	public function __construct($name, $title, $class = null, $help = null){
		$this->name   = $name;
		$this->title  = $title;
		$this->class  = $class;
		$this->help   = $help;
	}

	/**
	 * Affichage de la table dans un formulaire
	 */
	public function display(){
		// On va chercher les valeurs en bdd
		global $db;
		$dbData = $db->get($this->name);
		$safeName = str_replace('_', '-', $this->name);
		?>
		<h3><?php echo $this->title; ?>  <?php if($this->help != '') Help::iconHelp($this->help); ?></h3>
		<table class="table table-responsive<?php echo ' '.$this->class; ?>">
			<thead>
				<tr class="tr_dbTable_header">
					<?php
					/**
					 * @var Field $field
					 */
					foreach ($this->fields as $field){
						if ($field->getSettings() !== null and $field->getSettings()->getShow()){
							?><th><?php echo ($field->getLabel() != null) ? $field->getLabel() : $field->getName(); ?></th><?php
						}
					}
					?>
				</tr>
			</thead>
			<tbody>
			<?php
			if (!empty($dbData)){
				$i = 99999;
				foreach ($dbData as $dbItem){
					$id = (isset($dbItem->id)) ? $dbItem->id : $i;
					$i++;
					?><tr id="tr_dbTable_<?php echo $safeName; ?>_<?php echo $id; ?>" class="tr_dbTable"><?php
					foreach ($this->fields as $field){
						if ($field->getSettings() !== null and $field->getSettings()->getShow()){
							?>
							<td>
								<?php $field->tableItemDisplay($safeName, $id, $dbItem->{$field->getName()}); ?>
							</td>
							<?php
						}
					}
					?></tr><?php
				}
			}
			?>
			<tr id="<?php echo $safeName; ?>_new_tr">
				<?php
				$id = 'new';
				foreach ($this->fields as $field){
					if ($field->getSettings() !== null and $field->getSettings()->getShow()){
						?>
						<td>
							<?php $field->tableItemDisplay($safeName, $id); ?>
						</td>
					<?php
					}
				}
				?>
			</tr>
			</tbody>
		</table>
		<noscript><span class="help-block">Pour supprimer une ligne, il vous suffit d'effacer toutes les valeurs contenues dans celle-ci.</span></noscript>
	<?php
	}

	/**
	 * Extrait la structure de la table dans la base de données
	 */
	protected function extractTableFromDb(){
		global $db;
		$fields = $indexes = $fieldsInIndex = $foreignKeys = array();
		$sql = $db->query('SHOW CREATE TABLE `'.$this->name.'`');
		//var_dump($sql);
		$columns = explode("\n", $sql[0]->{'Create Table'});
		// On supprime le premier et le dernier item du tableau car ils ne sont d'aucune utilité
		array_shift($columns);
		array_pop($columns);
		foreach ($columns as $column){
			$matches = null;
			trim($column, ',');
			//var_dump($column);
			// Champs de la table
			preg_match("/\s*`(.*)` ((\w*)\((\d*)\)|\w*text|\w*blob)\s?(NOT NULL|NULL|)\s?(DEFAULT '\w*'|DEFAULT \w*|)\s?(AUTO_INCREMENT|)\s?(COMMENT '.*'|)/i", $column, $matches);
			/*
			 * Renvoie :
			 *  0 : Ligne complète
			 *  1 : Nom de champ
			 *  2 : Type de champ + longueur
			 *  3 : Type de champ
			 *  4 : Longueur de champ
			 *  5 : Valeur `null` acceptée (oui si vide)
			 *  6 : Valeur par défaut (aucune si vide)
			 *  7 : Auto-Incrémentation (non si vide)
			 *  8 : Commentaire (rien si vide)
			 */
			if (!empty($matches)) {
				// On supprime la ligne complète
				// On ajoute le tableau du champ à la liste des champs, indexée par le nom des champs
				$fields[$matches[1]] = $matches;
			}
			if (empty($matches)){
				// Indexes de la table
				preg_match("/^\s*(PRIMARY|UNIQUE|)\s*KEY\s?(`\w*`|)\s?\((.*)\)/i", $column, $matches);
				/*
				 * Renvoie :
				 *  0 : Ligne complète
				 *  1 : Type d'index (index si vide)
				 *  2 : Nom d'index
				 *  3 : Champs sur lesquels sont construits l'index
				 */
				if (!empty($matches)){
					//var_dump($matches);
					$fieldsFoundInIndex = explode(',', $matches[3]);
					if (count($fieldsFoundInIndex) > 1 ){
						foreach ($fieldsFoundInIndex as $fieldInIndex){
							$fieldInIndex = str_replace('`', '', $fieldInIndex);
							$fieldsInIndex[] = $fieldInIndex;
						}
					}else{
						$matches[3] = str_replace('`', '', $matches[3]);
						$indexes[$matches[3]] = $matches;
					}
				}
				if (empty($matches)){
					preg_match("/\s*CONSTRAINT `\w*` FOREIGN KEY \(`(\w*)`\) REFERENCES `(\w*)` \(`(\w*)`\) ON DELETE (\w+\s?\w*) ON UPDATE (\w+\s?\w*)/i", $column, $matches);
					/*
					 * Renvoie :
					 *  0 : Ligne complète
					 *  1 : Nom du champ sur lequel on applique la contrainte
					 *  2 : Nom de la table liée
					 *  3 : Nom du champ dans la table liée
					 *  4 : Action sur suppression
					 *  5 : Action sur mise à jour
					 */
					if (!empty($matches)){
						//var_dump($matches);
						//array_shift($matches);
						$foreignKeys[$matches[1]] = $matches;
					}
				}
			}
		}
		$removeFromDb = array();
		$alterDb = $addToDb = array(
			'fields'  => null,
			'indexes' => null
		);
		//var_dump($this->fields);
		foreach ($fields as $dbFieldName => $dbField){
			if (isset($this->fields[$dbFieldName])){
				$scriptField = $this->fields[$dbFieldName];
				// Si l'index du type n'est pas rempli, on le compplète avec l'index 2 qui contient le type complet
				if (empty($dbField[3])) $dbField[3] = $dbField[2];
				// l'index 4 étant la taille du champ, on le transforme en entier
				$dbField[4] = (empty($dbField[4])) ? 0 : (int)$dbField[4];
				// l'index 5 définit si le champ doit avoir une valeur ou non, on le transforme donc en booléen
				$dbField[5] = (empty($dbField[5])) ? false : true;
				// l'index 6 est la valeur par défaut, il faut donc la transformer pour être raccord avec la valeur du champ Field
				$dbField[6] = trim(str_replace('DEFAULT ', '', $dbField[6]), "'");
				if ($dbField[6] == 'NULL' or $dbField[6] == '') $dbField[6] = null;
				if ($dbField[3] == 'tinyint') $dbField[6] = (bool)(int)$dbField[6];
				// l'index 7 définit si le champ est auto-incrémenté ou non, on le transforme en booléen
				$dbField[7] = (!empty($dbField[7])) ? true : false;
				// l'index 8 est le commentaire du champ. Comme MySQL peut mettre le bazar avec les apostrophes, on les vire du champ Field
				$dbField[8] = substr(preg_replace("/COMMENT '/i", '', $dbField[8]), 0, -1);

				if (
					$scriptField->getDbType() != $dbField[3] or // Type de champ
					$scriptField->getPattern()->getMaxLength() != $dbField[4] or // Longueur de champ
					$scriptField->getPattern()->getRequired() != $dbField[5] or // Peut être `null` ou non
					$scriptField->getValue() != $dbField[6] or // Valeur par défaut
					$scriptField->getPattern()->getAutoIncrement() != $dbField[7] or // Auto-incrémentation
					str_replace("'", ' ', $scriptField->getLabel()) != $dbField[8] // Commentaire
				){
					var_dump($dbField);
					var_dump($scriptField);
					$alterDb['fields'][$dbFieldName] = $scriptField;
				}
			}else{
				$removeFromDb[$dbFieldName] = $dbField;
			}
		}
		//foreach ()
		/*var_dump($indexes);
		var_dump($fieldsInIndex);
		var_dump($foreignKeys);*/
	}

	/**
	 * Vérifie si la table existe dans la base de données
	 * @return bool
	 */
	protected function existsInDb(){
		global $db;
		$ret = $db->query('DESCRIBE `'.$this->name.'`', 'all', true);
		return ($ret === false) ? false : true;
	}

	/**
	 * Création de la table en base de données
	 *
	 * @return bool
	 */
	public function createInDb(){
		global $db;

		if ($this->existsInDb()) $this->extractTableFromDb();

		$sql = 'CREATE TABLE IF NOT EXISTS `'.$this->name.'` (';
		if (count($this->fields) == 0){
			new Alert('debug', '<code>DbTable->createInDb()</code> : aucun champ n\'est défini dans le tableau d\'entrée !');
			return false;
		}
		$indexes = $uniqueIndexes = $multipleIndexes = $foreignKeys = array();
		$primaryIndex = $autoIncrementValue = null;
		/**
		 * @var Field $field
		 */
		foreach ($this->fields as $field){
			$type = Sanitize::PHPToSQLType($field->getType());
			$sql .= '`'.$field->getName().'` '.$type;
			/**
			 * @var DbFieldSettings $settings
			 */
			$settings = $field->getSettings();
			if (empty($settings)){
				new Alert('debug', '<code>DbTable->createInDb()</code> : Les paramètres de champ <code>'.$field->getName().'</code> ne sont pas définis via un objet DbFieldSettings !');
				return false;
			}
			if ($type == 'tinyint') {
				$sql .= '(1)';
			}elseif ($type != 'text' and $type != 'bool' and $settings->getLength() == 0){
				new Alert('debug', '<code>DbTable->createInDb()</code> : La longueur du champ <code>'.$field->getName().'</code> n\'est pas définie !');
				return false;
			}elseif($type != 'bool'){
				$sql .= '('.$settings->getLength().')';
			}
			if ($settings->getRequired()){
				$sql .= ' NOT NULL';
			}
			$value = $field->getValue();
			if (!empty($value)){
				if (is_string($value)) $value = str_replace('\'', ' ', $value);
				$sql .= ' DEFAULT \''.$value.'\'';
			}
			if ($field->getLabel() != null) $sql .= ' COMMENT \''.str_replace('\'', ' ', $field->getLabel()).'\'';
			if ($settings->getAutoIncrement()){
				$sql .= ' AUTO_INCREMENT';
			}
			$sql .=', ';
			switch ($settings->getIndex()){
				case 'index':
					$indexes[] = $field->getName();
					break;
				case 'primary':
					$primaryIndex = $field->getName();
					break;
				case 'unique':
					$uniqueIndexes[] = $field->getName();
					break;
			}
			// Si le champ fait partie de l'index multiple, on l'ajoute au tableau
			if ($settings->getInMultipleIndex()){
				$multipleIndexes[] = $field->getName();
			}
			if ($settings->getForeignKey() !== null){
				$foreignKeys[$field->getName()] = $settings->getForeignKey();
			}
			if ($settings->getAutoIncrementValue() !== 0){
				$autoIncrementValue = $settings->getAutoIncrementValue();
			}
		}
		if (!empty($primaryIndex)){
			$sql .= 'PRIMARY KEY (`'.$primaryIndex.'`), ';
		}
		if (!empty($uniqueIndexes)){
			foreach ($uniqueIndexes as $key){
				$sql .= 'UNIQUE KEY `'.$key.'` (`'.$key.'`), ';
			}
		}
		if (!empty($multipleIndexes)){
			$fieldsMultiKey = $multipleIndexes;
			array_walk($multipleIndexes, function(&$value){
				$value = '`'.$value.'`';
			});
			$sql .= 'UNIQUE KEY `'.implode('_', $fieldsMultiKey).'` ('.implode(', ', $multipleIndexes).'), ';
		}
		if (!empty($indexes)){
			foreach ($indexes as $key){
				$sql .= 'KEY `'.$key.'` (`'.$key.'`), ';
			}
		}
		if (!empty($foreignKeys)){
			/**
			 * @var ForeignKey $foreignKey
			 */
			foreach ($foreignKeys as $key => $foreignKey){
				$sql .= 'FOREIGN KEY (`'.$key.'`) REFERENCES `'.$foreignKey->getTable().'` (`'.$foreignKey->getKey().'`) ON DELETE '.$foreignKey->getOnDelete().' ON UPDATE '.$foreignKey->getOnUpdate().', ';
			}
		}
		$sql = rtrim($sql, ', ');
		$sql .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8';
		if (!empty($autoIncrementValue)){
			$sql .= ' AUTO_INCREMENT='.$autoIncrementValue;
		}
		$sql .= ';';
		$ret = $db->query($sql);
		return ($ret !== false) ? true : false;
	}

	/**
	 * Insère des lignes dans la table
	 *
	 * Les valeurs à insérer sont définies en tant que tableau associatif :
	 *
	 *  $row[ID] = array(
	 *    champ   => Valeur
	 *    champ2  => Valeur2
	 *  );
	 *
	 * @param array $rows Tableau associatif de lignes à insérer, avec l'ID de la ligne en tant que clé
	 *
	 * @return bool
	 */
	public function insertRows($rows){
		global $db;
		if (!empty($rows)){
			$itemsIdsDb = $db->get($this->name, 'id');

			$itemsToDelete = $keysToUpdate = array();
			foreach ($itemsIdsDb as $itemId){
				$itemsToDelete[] = $itemId->id;
			}
			$sql = 'INSERT INTO `'.$this->name.'` (';
			/**
			 * @var Field $field
			 */
			foreach ($this->fields as $field){
				$sql .= '`'.$field->getName().'`, ';
				if ($field->getSettings()->getOnDuplicateKeyUpdate()){
					$keysToUpdate[] = $field->getName();
				}
			}
			$sql = rtrim($sql, ', ').') VALUES ';
			// Les valeurs à présent
			foreach ($rows as $id => $row){
				$sql .= '(';
				// Si l'ID n'est pas affichée (et donc pas renvoyée par le formulaire), il va falloir la renseigner quand même via le nom du champ renvoyé
				if (!isset($row['id'])){
					if ($id != 'new' or empty($id)){
						$sql .= $id.', ';
						// On enlève l'id du tableau des ids d'items, afin de pouvoir supprimer les lignes restantes
						unset($itemsToDelete[array_search($id, $itemsToDelete)]);
					}else{
						// Si l'ID est 'new', c'est un nouveau champ. Pour laisser faire l'auto-incrémentation, on envoie une valeur nulle
						$sql .= 'NULL, ';
					}
				}
				// Valeurs de chaque colonne pour une ligne
				foreach ($this->fields as $field){
					// On vient de traiter l'id juste avant la boucle, on l'enlève donc des champs à traiter
					if ($field->getName() != 'id'){
						$sql .= ((isset($row[$field->getName()])) ? '"'.str_replace('\\', '\\\\', $row[$field->getName()]).'"' : 'NULL').', ';
					}
				}
				$sql = rtrim($sql, ', ');
				$sql .= '),';
			}
			$sql = rtrim($sql, ', ');

			// Évidemment, on risque de trouver des enregistrements déjà présents. Grâce à 'onDuplicateKeyUpdate', on va savoir quels colonnes mettre à jour en cas d'enregistrements déjà présents
			if (!empty($keysToUpdate)){
				$sql .= ' ON DUPLICATE KEY UPDATE ';
				foreach ($keysToUpdate as $update){
					$sql.= '`'.$update.'` = VALUES(`'.$update.'`), ';
				}
				$sql = rtrim($sql, ', ');
			}
			// Enfin, on exécute la requête SQL.
			$ret = $db->query($sql);
			// On supprime les enregistrements qui n'ont pas été renvoyés par le formulaire, ce qui signifie qu'ils ont été effacés.
			$ret2 = true;
			if (!empty($itemsToDelete)){
				foreach ($itemsToDelete as $item){
					$ret2 = $db->delete($this->name, array('id'=>$item));
				}
			}
			if ($ret === false or $ret2 === false){
				new Alert('error', 'Impossible de sauvegarder les enregistrements de la table <code>'.$this->name.'</code> !');
				return false;
			}else{
				new Alert('success', 'Les enregistrements de la table <code>'.$this->name.'</code> ont été mis à jour.');
				return true;
			}
		}else{
			return true;
		}
	}

	/**
	 * Retourne le nom de la table
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Retourne les champs de la table
	 * @return Field[]
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Retourne le titre de la table
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Ajoute un champ à la table.
	 *
	 * Ce champ doit avoir un objet DbFieldSettings associé (via la variable Pattern)
	 * @param Field $field
	 */
	public function addField(Field $field) {
		$this->fields[$field->getName()] = $field;
	}

	/**
	 * Retourne l'aide succinte associée à la table
	 * @return string
	 */
	public function getHelp() {
		return $this->help;
	}

	/**
	 * Retourne la classe CSS utilisée lors de l'affichage de la table
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}
} 