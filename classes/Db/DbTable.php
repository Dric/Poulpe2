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

class DbTable {

	/**
	 * Nom de la table en bdd
	 * @var string
	 */
	protected $name = null;
	/**
	 * Tableau d'objets Field
	 * @var array
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
	 * @param string  $name     Nom de la table
	 * @param string  $title    Titre lorsqu'on affiche la table
	 * @param string  $class    Classe CSS optionnelle à appliquer à la table à l'affichage
	 * @param string  $help     Aide optionnelle affichée en infobulle à côté du titre
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
	 * Création de la table en base de données
	 *
	 * @return bool
	 */
	public function createInDb(){
		global $db;
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
			if ($settings->getInMultipleIndex()){
				$multipleIndexes[] = $settings->getInMultipleIndex();
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
	 * Insérer des lignes dans la table
	 *
	 * @param array $rows Tableau associatif de lignes à insérer, avec l'ID de la ligne en tant que clé
	 *
	 * @return bool
	 */
	public function insertRows($rows){
		global $db;
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
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Ajout un champ à la table.
	 * Ce champ doit avoir un objet DbFieldSettings associé (via la variable Pattern)
	 * @param Field $field
	 */
	public function addField(Field $field) {
		$this->fields[$field->getName()] = $field;
	}

	/**
	 * @return string
	 */
	public function getHelp() {
		return $this->help;
	}

	/**
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}
} 