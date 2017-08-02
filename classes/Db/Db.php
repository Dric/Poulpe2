<?php
/**
 * Classe de communication avec une base SQL
 * User: cedric.gallard
 * Date: 19/03/14
 * Time: 09:45
 *
 */

namespace Db;


use Exception;
use Forms\Field;
use Forms\Pattern;
use Logs\Alert;
use Get;
use PDO;
use PDOException;
use PDOStatement;
use Sanitize;

/**
 * Classe de communication avec une base SQL
 *
 * La connexion est ouverte à l'initialisation de la classe, et est fermée automatiquement à la fin du script.
 *
 * Si les identifiants de connexion à la base de données ne sont pas indiqués, le script prend les paramètres dans `config.php`.
 *
 * <h4>Exemples</h4>
 * <code>
 * use Db\Db;
 * $db = new Db('mysql', 'localhost', 'dbName', 'DbUser', 'DbPwd');
 * </code>
 *
 * <code>
 * use Db\Db;
 * $db = new Db();
 * </code>
 *
 * Une variable globale `$db` est déclarée au début du script. Si vous souhaitez utiliser un accès à la base de données principale dans une méthode, utilisez :
 * <code>
 * global $db;
 * </code>
 *
 * Vous pourrez ensuite utiliser l'objet `$db` :
 * <code>
 * $items = $db->get('table');
 * </code>
 *
 * @package Db
 */
class Db {

	/**
	 * Type de SQL (MySQL par défaut)
	 * @var string
	 */
	protected $type = 'mysql';

	/**
	 * Serveur SQL
	 * @var string
	 */
	protected $dbServer = '';

	/**
	 * Nom de la base de données
	 * @var string
	 */
	protected $dbName = '';

	/**
	 * Compte utilisé pour se connecter à la base de données
	 * @var string
	 */
	protected $dbLogin = '';

	/**
	 * Mot de passe du compte utilisé pour se connecter à la bdd
	 * @var string
	 */
	protected $dbPwd = '';

	/**
	 * Objet de base de données
	 * @var object
	 */
	protected $db = null;

	/**
	 * Nombre de requêtes effectuées
	 * @var int
	 */
	protected $queriesCount = 0;

	/**
	 * Connexion à une base de données
	 *
	 * Si les arguments de connexion ne sont pas indiqués, le script ira les chercher dans le fichier `config.php`.
	 *
	 * @param string $type Type de bdd
	 * @param string $dbServer Nom du serveur hébergeant la bdd
	 * @param string $dbName Nom de la bdd
	 * @param string $dbLogin Compte utilisé pour se connecter à la bdd
	 * @param string $dbPwd Mot de passe du compte utilisé pour se connecter à la bdd
	 */
	public function __construct($type = '', $dbServer = '', $dbName = '', $dbLogin = '', $dbPwd = ''){
		$this->type = (!empty($type)) ? $type : \Settings::DB_TYPE;
		$this->dbServer = (!empty($dbServer)) ? $dbServer : \Settings::DB_HOST;
		$this->dbName = (!empty($dbName)) ? $dbName : \Settings::DB_NAME;
		$this->dbLogin = (!empty($dbLogin)) ? $dbLogin : \Settings::DB_USER;
		$this->dbPwd = (!empty($dbPwd)) ? $dbPwd : \Settings::DB_PASSWORD;
		try {
			switch ($this->type){
				case 'mysql':
					$this->db = new PDO($this->type.':host='.$this->dbServer.';dbname='.$this->dbName, $this->dbLogin, $this->dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
					break;
				case 'firebird':
					$this->db = new PDO($this->type.':dbname='.$this->dbServer.':'.$this->dbName, $this->dbLogin, $this->dbPwd);
					break;
			}
		} catch (PDOException $e) {
			new Alert('danger', 'Erreur : Impossible de se connecter à la base de données !<br/>'.$e->getMessage());
		}
	}

	/**
	 * Lance une requête SQL
	 *
	 * WARNING : la requête est lancée telle quelle, elle doit être protégée avant
	 *
	 * <h4>Exemple</h4>
	 * <code>
	 * $ret = $db->query('SELECT * FROM Table WHERE name = "Bob"');
	 * </code>
	 *
	 * @param string $sql     requête SQL
	 * @param string $get     Renvoie <br>
	 *                        'row' une ligne <br>
	 *                        'col' une colonne <br>
	 *                        'all' un tableau de résultat <br>
	 *                        'val' une valeur <br>
	 *
	 * @param bool   $noAlert Si `true`, n'affiche pas d'alerte en cas d'erreur
	 *
	 * @param array  $bindColumns permet de définir des paramètres sur les colonnes, utilisés avec bindColumns();. Ex : array('conteneur' => array('pos' => position du champ dans la requête, 'option' => PDO::PARAM_LOB) (notez l'absence de guillemets pour PDO::PARAMS_LOB)
	 *
	 * @return bool|object Résultat de la requête
	 */
	public function query($sql, $get = 'all', $noAlert = false, $bindColumns = array()){

		$sql = str_replace('"', '\'', $sql);
		$statement = $this->db->prepare($sql);
		if ($statement === false){
			if (!$noAlert) new Alert('debug', '<code>Db->query</code> : Impossible d\'effectuer la requête car elle est mal formée.<br /><pre>'.$sql.'</pre>');
			return false;
		}
		$ret = $statement->execute();
		if (!empty($bindColumns)){
			foreach ($bindColumns as $field => $col){
				$statement->bindColumn($col['pos'], ${$field}, $col['option']);
			}
		}
		$this->queriesCount ++;
		if (!$ret){
			if (!$noAlert) new Alert('error', 'Erreur lors de l\'exécution de la requête !<br><code>'.$statement->errorInfo()[2].'</code><br><code>'.$sql.'</code>');
			return false;
		}
		switch ($get){
			case 'all':
				return $statement->fetchAll(PDO::FETCH_OBJ);
			case 'row':
				return $statement->fetch(PDO::FETCH_OBJ);
			case 'col':
				return $statement->fetchColumn();
			case 'val':
				return $statement->fetch()[0];
		}
		if (!$noAlert) new Alert('debug', '<code>Db->query</code> : Impossible de retourner la requête.<br /><code>get='.$get.'</code> n\'est pas dans la liste des codes autorisés (all, row ou col)');
		return false;
	}

	/**
	 * Exécute un groupe de commandes SQL via une transaction
	 *
	 * Si une des commandes échoue, toutes les modifications sont annulées.
	 * On est ainsi certain soit que tout s'est bien passé, soit qu'aucune modification n'aura été faite dans la base de données si une erreur survient.
	 *
	 * @warning Avec MySQL, un commit implicite est réalisé à chaque commande DDL :
	 *    CREATE – to create table (objects) in the database
	 *    ALTER – alters the structure of the database
	 *    DROP – delete table from the database
	 *    TRUNCATE – remove all records from a table, including all spaces allocated for the records are removed
	 *    COMMENT – add comments to the data dictionary
	 *    RENAME – rename a table
	 * @see <http://php.net/manual/en/pdo.rollback.php>
	 * @see <http://www.nextstep4it.com/ddl-statements-in-mysql/>
	 *
	 * @param array $sqlQueries Requêtes SQL sous forme de tableau
	 *
	 * @return bool
	 */
	public function queryGroup(Array $sqlQueries){
		$noRollBack = false;
		if ($this->type == 'firebird') {
			$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
		}
		$this->startTransaction();
		foreach ($sqlQueries as $query){
			if (preg_match('/(CREATE|ALTER|DROP|TRUNCATE|COMMENT|RENAME)/mi', $query)){
				$noRollBack = true;
			}
			$ret = $this->query($query);
			if ($ret === false){
				if ($noRollBack){
					new Alert('warning', 'ATTENTION : la présence de commandes DDL (CREATE, ALTER, DROP, COMMENT, TRUNCATE, RENAME) empêche l\'annulation des modifications !');
				}
				$this->rollBackTransaction();
				if ($this->type == 'firebird') {
					$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
				}
				return false;
			}
		}
		$this->commitTransaction();
		if ($this->type == 'firebird') {
			$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
		}
		return true;
	}

	public function startTransaction(){
		$this->db->beginTransaction();
	}

	public function commitTransaction(){
		$this->db->commit();
	}

	public function rollBackTransaction(){
		$this->db->rollBack();
	}

	/**
	 * Retourne des lignes de la base de données
	 *
	 * <h4>Exemple</h4>
	 * <code>
	 * // Retourne les Id et les noms des lignes dont le nom est Bob, trié par Id décroissante
	 * $fields = array('id', 'name');
	 * $where = array('name' => 'Bob');
	 * $orderBy = array('id' => 'DESC);
	 * $items = $db->get('table', $fields, $where, $orderBy);
	 * </code>
	 *
	 * @param string       $table   Table sur laquelle effectuer la requête
	 * @param string|array $fields  Champs à retourner <br>
	 *                              - tableau array('champ1', 'champ2') <br>
	 *                              - '*' pour tous les champs de la table <br>
	 *                              - 'all' pour tous les champs de la table <br>
	 *                              - null pour tous les champs de la table
	 * @param array        $where   Conditions de la requête de la forme array('champ' => valeur)
	 * @param array        $orderBy Tableau de tri, de la forme array('champ' => 'ordre de tri (ASC, DESC)')
	 * @param string       $get     Renvoie : <br>
	 *                              - 'row' une ligne <br>
	 *                              - 'col' une colonne <br>
	 *                              - 'all' un tableau de résultat <br>
	 *                              - 'val' une valeur <br>
	 *
	 * @param array         $fieldsTypes Tableau des types de données, de la forme array('champ' => 'type')
	 *                                   - 'string'
	 *                                   - 'bool'
	 *                                   - 'array'
	 *                                   - 'int'
	 *                                   - 'float'
	 *
	 * @return bool|object
	 */
	public function get($table, $fields = null, $where = null, $orderBy = null, $get = 'all', $fieldsTypes = null){
		if (!empty($where) and !is_array($where)){
			new Alert('debug', '<code>Db->get()</code> : <code>$where</code> n\'est pas un tableau !'.Get::varDump($where));
			return false;
		}
		if (empty($fields) or $fields == '*' or $fields == 'all'){
			$fields = array('*');
		}elseif (!is_array($fields)) {
			$fields = array($fields);
		}
		$prepWhere = array();
		array_walk($fields, function(&$value){
			$value = htmlspecialchars(str_replace(' ', '',$value));
		});
		$prepFields = implode(", ", $fields);
		if ($this->type == 'firebird') {
			$sql = 'SELECT ' . $prepFields . ' FROM ' . htmlspecialchars($table);
		} else {
			$sql = 'SELECT ' . $prepFields . ' FROM `' . htmlspecialchars($table) . '`';
		}
		if (!empty($where)){
			foreach ($where as $key => $value) {
				$fieldType = (isset($fieldsTypes[$key])) ? $fieldsTypes[$key] : null;
				if (!is_array($value)) {
					if ($this->type == 'firebird') {
						$prepWhere[] = htmlspecialchars($key) . ' = ' . Sanitize::SanitizeForDb($value, true, $fieldType);
					} else {
						$prepWhere[] = '`' . htmlspecialchars($key) . '` = ' . Sanitize::SanitizeForDb($value, true, $fieldType);
					}
				}else{
					if ($this->type == 'firebird') {
						$prepWhere[] = htmlspecialchars($key) . ' IN (' . Sanitize::SanitizeForDb($value, true, $fieldType) . ')';
					} else {
						$prepWhere[] = '`' . htmlspecialchars($key) . '` IN (' . Sanitize::SanitizeForDb($value, true, $fieldType) . ')';
					}
				}
			}
			$sql .= ' WHERE '.implode(' AND ', $prepWhere);
		}
		if (!empty($orderBy)){
			$sql .= ' ORDER BY';
			foreach ($orderBy as $row => $sort){
				if ($this->type == 'firebird') {
					$sql .= htmlspecialchars($row) . ' ' . htmlspecialchars($sort) . ', ';
				} else {
					$sql .= '`' . htmlspecialchars($row) . '` ' . htmlspecialchars($sort) . ', ';
				}
			}
			$sql = rtrim($sql, ', ');
		}
		return $this->query($sql, $get);
	}

	/**
	 * Retourne une ligne d'une requête SQL
	 *
	 * <h4>Exemple</h4>
	 * <code>
	 * // Retourne l'Id et le nom de la ligne dont le nom est Bob
	 * $fields = array('id', 'name');
	 * $where = array('name' => 'Bob');
	 * $items = $db->getRow('table', $fields, $where);
	 * </code>
	 *
	 * @param string       $table Table sur laquelle effectuer la requête
	 * @param string|array $fields Champs à retourner <br>
	 * - tableau array('champ1', 'champ2') <br>
	 * - 'champ' pour un seul champ de la table <br>
	 * - '*' pour tous les champs de la table <br>
	 * - 'all' pour tous les champs de la table <br>
	 * - null pour tous les champs de la table <br>
	 * @param array $where Conditions de la requête de la forme array('champ' => valeur)
	 * @param array $orderBy Tableau de tri, de la forme array('champ' => 'ordre de tri (ASC, DESC)')
	 * @param array         $fieldsTypes Tableau des types de données, de la forme array('champ' => 'type')
	 *                                   - 'string'
	 *                                   - 'bool'
	 *                                   - 'array'
	 *                                   - 'int'
	 *                                   - 'float'
	 *
	 * @return object
	 */
	public function getRow($table, $fields = null, $where = null, $orderBy = null, $fieldsTypes = null){
		return $this->get($table, $fields, $where, $orderBy, 'row', $fieldsTypes);
	}

	/**
	 * Retourne une valeur d'une requête SQL
	 *
	 * <h4>Exemple</h4>
	 * <code>
	 * // Retourne l'Id de la ligne dont le nom est Bob
	 * $where = array('name' => 'Bob');
	 * $id = $db->getVal('table', 'id', $where);
	 * </code>
	 *
	 * @param string $table Table sur laquelle effectuer la requête
	 * @param string|array $field Champ à retourner <br>
	 * - tableau array('champ1', 'champ2') <br>
	 * - 'champ' pour un seul champ de la table <br>
	 * - '*' pour tous les champs de la table <br>
	 * - 'all' pour tous les champs de la table <br>
	 * - null pour tous les champs de la table <br>
	 * @param array $where Conditions de la requête de la forme array('champ' => valeur)
	 * @param array $orderBy Tableau de tri, de la forme array('champ' => 'ordre de tri (ASC, DESC)')
	 * @param array $fieldsTypes Tableau des types de données, de la forme array('champ' => 'type')
	 *                                   - 'string'
	 *                                   - 'bool'
	 *                                   - 'array'
	 *                                   - 'int'
	 *                                   - 'float'
	 *
	 * @return mixed
	 */
	public function getVal($table, $field, $where = null, $orderBy = null, $fieldsTypes = null){
		return $this->get($table, $field, $where, $orderBy, 'val', $fieldsTypes);
	}

	/**
	 * Insère une ligne dans une table SQL
	 *
	 * <h4>Exemple</h4>
	 * <code>
	 * // Insère une ligne dans la table
	 * $fields = array( 'name'    => 'Bob',
	 *                  'active'  => true
	 *              );
	 * $ret = $db->insert('table', $fields, array('active' => 'bool'));
	 * </code>
	 *
	 * @param string $table Table SQL
	 * @param array  $fields Champs et valeurs à insérer, de la forme 'champ' => valeur
	 *
	 * @param array  $format Type facultatif des valeurs à traiter pour les champs dans $updates et $where, de la forme 'champ' => type (int, float, bool, string, null)
	 *
	 * @return int|bool ID de la ligne insérée ou false si erreur
	 */
	public function insert($table, $fields, $format = array()){
		if (!is_array($fields)) {
			new Alert('debug', '<code>Db->insert()</code> : <code>$fields</code> n\'est pas un tableau.');
			return false;
		}
		$prepFields = implode(", :", array_keys($fields));
		$sql = 'INSERT INTO `'.htmlspecialchars($table).'` (`'.implode('`, `', array_keys($fields)).'`) VALUES (:'.$prepFields.')';
		$statement = $this->db->prepare($sql);
		if ($statement === false){
			new Alert('debug', '<code>Db->insert()</code> : Impossible d\'effectuer la requête d\'insertion car elle est mal formée.<br /><pre>'.$sql.'</pre>');
			return false;
		}
		foreach ($fields as $field => &$value){
			$type = $this->getType($field, $format);
			$statement->bindParam(':'.$field, $value, $type);
		}
		$ret = $statement->execute();
		$this->queriesCount ++;
		if (!$ret){
			new Alert('error', 'Erreur lors de l\'exécution de la requête d\'insertion !<br>'.$statement->errorInfo()[2]);
			return false;
		}
		return $this->db->lastInsertId();
	}

	/**
	 * Met à jour des lignes dans une table
	 *
	 * @param string $table Table SQL
	 * @param array  $updates Valeurs à mettre à jour, de la forme 'champ' => valeur
	 * @param array  $where Critères de mise à jour, de la forme 'champ' => valeur
	 * @param array  $format Type facultatif des valeurs à traiter pour les champs dans $updates et $where, de la forme 'champ' => type (int, float, bool, string, null)
	 *
	 * @return bool
	 */
	public function update($table, $updates, $where, $format = array()){
		if (!is_array($updates)) {
			new Alert('debug', '<code>Db->update()</code> : <code>$updates</code> n\'est pas un tableau.');
			return false;
		}
		if (!is_array($where)) {
			new Alert('debug', '<code>Db->update()</code> : <code>$where</code> n\'est pas un tableau.');
			return false;
		}
		$prepUpdates = array();
		$prepWhere = array();
		foreach ($updates as $key => $value) {
			if ($this->type == 'firebird'){
				$prepUpdates[] = htmlspecialchars($key). ' = :'.htmlspecialchars($key);
			} else {
				$prepUpdates[] = '`'.htmlspecialchars($key). '` = :'.htmlspecialchars($key);
			}
		}
		foreach ($where as $key => $value) {
			if (isset($updates[$key])) {
				$updates[$key.'2'] = $updates[$key];
				unset($updates['key']);
			}
			if ($this->type == 'firebird') {
				$prepWhere[] = htmlspecialchars($key) . ' = :' . htmlspecialchars($key);
			} else {
				$prepWhere[] = '`' . htmlspecialchars($key) . '` = :' . htmlspecialchars($key);
			}
		}
		if ($this->type == 'firebird') {
			$sql = 'UPDATE ' . htmlspecialchars($table) . ' SET ' . implode(', ', $prepUpdates) . ' WHERE ' . implode(' AND ', $prepWhere);
		} else {
			$sql = 'UPDATE `' . htmlspecialchars($table) . '` SET ' . implode(', ', $prepUpdates) . ' WHERE ' . implode(' AND ', $prepWhere);
		}
		$statement = $this->db->prepare($sql);
		if ($statement === false){
			new Alert('debug', '<code>Db->update()</code> : Impossible d\'effectuer la requête d\'update car elle est mal formée.<br /><code>'.$sql.'</code>');
			return false;
		}
		foreach ($updates as $field => &$value){
			$type = $this->getType($field, $format);
			$statement->bindParam(":$field", $value, $type);
		}
		foreach ($where as $fieldW => &$valueW){
			$type = $this->getType($fieldW, $format);
			$statement->bindParam(":$fieldW", $valueW, $type);
		}
		$ret = $statement->execute();
		$this->queriesCount ++;
		if (!$ret){
			new Alert('error', 'Erreur lors de l\'exécution de la requête d\'update !<br>'.$statement->errorInfo()[2]);
			return false;
		}
		return true;
	}

	/**
	 * Supprime des enregistrements dans une table
	 *
	 *
	 * @param string $table Table dans laquelle supprimer les enregistrements
	 * @param array $where Critères de suppression, de la forme 'champ' => valeur
	 *
	 * @return bool
	 */
	public function delete($table, $where){
		if (!is_array($where)) {
			new Alert('debug', '<code>Db->delete()</code> : <code>$where</code> n\'est pas un tableau.');
			return false;
		}
		$prepWhere = array();
		foreach ($where as $key => $value) {
			/*if (isset($updates[$key])) {
				$updates[$key.'2'] = $updates[$key];
				unset($updates['key']);
			}*/
			$prepWhere[] = '`'.htmlspecialchars($key). '` = :'.htmlspecialchars($key);
		}
		$sql = 'DELETE FROM `'.htmlspecialchars($table).'` WHERE '.implode(' AND ', $prepWhere);
		$statement = $this->db->prepare($sql);
		if ($statement === false){
			new Alert('debug', '<code>Db->delete()</code> : Impossible d\'effectuer la requête car elle est mal formée.<br /><code>'.$sql.'</code>');
			return false;
		}
		foreach ($where as $fieldW => &$valueW){
			$statement->bindParam(":$fieldW", $valueW);
		}
		$ret = $statement->execute();
		$this->queriesCount ++;
		if ($ret === false){
			new Alert('error', 'Erreur lors de l\'exécution de la requête !<br>'.$statement->errorInfo()[2]);
			return false;
		}
		return true;
	}

	/**
	 * Retourne le typage d'un champ suivant un tableau de typage
	 *
	 * @param string $field Champ dont on veut retourner le type
	 * @param array $formatArray Tableau des typages de la forme 'champ' => type
	 *
	 * @return int|null
	 */
	protected function getType($field, $formatArray){
		$type = null;
		if (isset($formatArray[$field])){
			switch ($formatArray[$field]){
				case 'int':
					$type = PDO::PARAM_INT;
					break;
				case 'float':
				case 'double':
					// Il n'existe pas de PDO::PARAM_FLOAT...
				case 'string':
				default:
					$type = PDO::PARAM_STR;
					break;
				case 'bool':
				case 'boolean':
					$type = PDO::PARAM_BOOL;
					break;
				case 'null':
					$type = PDO::PARAM_NULL;
			}
		}
		return $type;
	}

	/**
	 * Retourne le nombre de requêtes effectuées
	 *
	 * @return int
	 */
	public function getQueriesCount() {
		return $this->queriesCount;
	}
}