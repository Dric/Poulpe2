<?php
/**
 * Classe de communication avec une base SQL
 * User: cedric.gallard
 * Date: 19/03/14
 * Time: 09:45
 *
 */

namespace Db;


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
	 * @param string $type Type de bdd
	 * @param string $dbServer Nom du serveur hébergeant la bdd
	 * @param string $dbName Nom de la bdd
	 * @param string $dbLogin Compte utilisé pour se connecter à la bdd
	 * @param string $dbPwd Mot de passe du compte utilisé pour se connecter à la bdd
	 */
	public function __construct($type = '', $dbServer = '', $dbName = '', $dbLogin = '', $dbPwd = ''){
		$this->type = (!empty($type)) ? $type : DB_TYPE;
		$this->dbServer = (!empty($dbServer)) ? $dbServer : DB_HOST;
		$this->dbName = (!empty($dbName)) ? $dbName : DB_NAME;
		$this->dbLogin = (!empty($dbLogin)) ? $dbLogin : DB_USER;
		$this->dbPwd = (!empty($dbPwd)) ? $dbPwd : DB_PASSWORD;

		try {
			$this->db = new PDO($this->type.':host='.$this->dbServer.';dbname='.$this->dbName, $this->dbLogin, $this->dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
		} catch (PDOException $e) {
			new Alert('danger', 'Erreur : Impossible de se connecter à la base de données !<br/>'.$e->getMessage());
		}
	}

	/**
	 * Lance une requête SQL
	 *
	 * WARNING : la requête est lancée telle quelle, elle doit être protégée avant
	 *
	 * @param string $sql requête SQL
	 * @param string $get Renvoie
	 * 'row' une ligne
	 * 'col' une colonne
	 * 'all' un tableau de résultat
	 * 'val' une valeur
	 *
	 * @return object|bool Résultat de la requête
	 */
	public function query($sql, $get = 'all'){

		$sql = str_replace('"', '\'', $sql);
		$statement = $this->db->prepare($sql);
		if ($statement === false){
			new Alert('debug', '<code>Db->query</code> : Impossible d\'effectuer la requête car elle est mal formée.<br /><pre>'.$sql.'</pre>');
			return false;
		}
		$ret = $statement->execute();
		$this->queriesCount ++;
		if (!$ret){
			new Alert('error', 'Erreur lors de l\'exécution de la requête !<br><code>'.$statement->errorInfo()[2].'</code><br><code>'.$sql.'</code>');
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
		new Alert('debug', '<code>Db->query</code> : Impossible de retourner la requête.<br /><code>get='.$get.'</code> n\'est pas dans la liste des codes autorisés (all, row ou col)');
		return false;
	}

	/**
	 * Retourne des lignes de la base de données
	 *
	 * @param string       $table Table sur laquelle effectuer la requête
	 * @param string|array $fields Champs à retourner
	 * - tableau array('champ1', 'champ2')
	 * - '*' pour tous les champs de la table
	 * - 'all' pour tous les champs de la table
	 * - null pour tous les champs de la table
	 * @param array        $where Conditions de la requête de la forme array('champ' => valeur)
	 * @param array        $orderBy Tableau de tri, de la forme array('champ' => 'ordre de tri (ASC, DESC)')
	 * @param string       $get Renvoie :
	 * - 'row' une ligne
	 * - 'col' une colonne
	 * - 'all' un tableau de résultat
	 * - 'val' une valeur
	 *
	 * @return object|bool
	 */
	public function get($table, $fields = null, $where = null, $orderBy = null, $get = 'all'){
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
		$sql = 'SELECT '.$prepFields.' FROM `'.htmlspecialchars($table).'`';
		if (!empty($where)){
			foreach ($where as $key => $value) {
				if (!is_array($value)) {
					$prepWhere[] = '`'.htmlspecialchars($key). '` = '. Sanitize::SanitizeForDb($value);
				}else{
					$prepWhere[] = '`'.htmlspecialchars($key). '` IN ('. Sanitize::SanitizeForDb($value).')';
				}
			}
			$sql .= ' WHERE '.implode(' AND ', $prepWhere);
		}
		if (!empty($orderBy)){
			$sql .= ' ORDER BY';
			foreach ($orderBy as $row => $sort){
				$sql .= '`'.htmlspecialchars($row).'` '.htmlspecialchars($sort).', ';
			}
			$sql = rtrim($sql, ', ');
		}
		return $this->query($sql, $get);
	}

	/**
	 * Retourne une ligne d'une requête SQL
	 *
	 * @param string       $table Table sur laquelle effectuer la requête
	 * @param string|array $fields Champs à retourner
	 * - tableau array('champ1', 'champ2')
	 * - 'champ' pour un seul champ de la table
	 * - '*' pour tous les champs de la table
	 * - 'all' pour tous les champs de la table
	 * - null pour tous les champs de la table
	 * @param array $where Conditions de la requête de la forme array('champ' => valeur)
	 * @param array $orderBy Tableau de tri, de la forme array('champ' => 'ordre de tri (ASC, DESC)')
	 *
	 * @return object
	 */
	public function getRow($table, $fields = null, $where = null, $orderBy = null){
		return $this->get($table, $fields, $where, $orderBy, 'row');
	}

	/**
	 * Retourne une colonne d'une requête SQL
	 * @param string $table Table sur laquelle effectuer la requête
	 * @param string|array $field Champ à retourner
	 * - tableau array('champ1', 'champ2')
	 * - 'champ' pour un seul champ de la table
	 * - '*' pour tous les champs de la table
	 * - 'all' pour tous les champs de la table
	 * - null pour tous les champs de la table
	 * @param array $where Conditions de la requête de la forme array('champ' => valeur)
	 * @param array $orderBy Tableau de tri, de la forme array('champ' => 'ordre de tri (ASC, DESC)')
	 *
	 * @return object
	 */
	public function getCol($table, $field, $where = null, $orderBy = null){
		return $this->get($table, $field, $where, $orderBy, 'col');
	}

	/**
	 * Retourne une valeur d'une requête SQL
	 * @param string $table Table sur laquelle effectuer la requête
	 * @param string|array $field Champ à retourner
	 * - tableau array('champ1', 'champ2')
	 * - 'champ' pour un seul champ de la table
	 * - '*' pour tous les champs de la table
	 * - 'all' pour tous les champs de la table
	 * - null pour tous les champs de la table
	 * @param array $where Conditions de la requête de la forme array('champ' => valeur)
	 * @param array $orderBy Tableau de tri, de la forme array('champ' => 'ordre de tri (ASC, DESC)')
	 *
	 * @return mixed
	 */
	public function getVal($table, $field, $where = null, $orderBy = null){
		return $this->get($table, $field, $where, $orderBy, 'val');
	}

	/**
	 * Insère une ligne dans une table SQL
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
			$prepUpdates[] = '`'.htmlspecialchars($key). '` = :'.htmlspecialchars($key);
		}
		foreach ($where as $key => $value) {
			if (isset($updates[$key])) {
				$updates[$key.'2'] = $updates[$key];
				unset($updates['key']);
			}
			$prepWhere[] = '`'.htmlspecialchars($key). '` = :'.htmlspecialchars($key);
		}
		$sql = 'UPDATE `'.htmlspecialchars($table).'` SET '.implode(', ', $prepUpdates).' WHERE '.implode(' AND ', $prepWhere);
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
			if (isset($updates[$key])) {
				$updates[$key.'2'] = $updates[$key];
				unset($updates['key']);
			}
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
		if (!$ret){
			new Alert('error', 'Erreur lors de l\'exécution de la requête !<br>'.$statement->errorInfo()[2]);
			return false;
		}
		return true;
	}

	/**
	 * Retourne le typage d'un champ suivant un tableau de typage
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
	 * @return int
	 */
	public function getQueriesCount() {
		return $this->queriesCount;
	}
}