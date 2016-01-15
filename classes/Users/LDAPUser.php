<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 03/04/14
 * Time: 09:23
 */

namespace Users;

/**
 * Infos d'un utilisateur LDAP
 *
 * @package Users
 */
class LDAPUser {

	/**
	 * Common Name
	 * @var string
	 */
	public $cn = '';

	/**
	 * Compte désactivé
	 * @var bool
	 */
	public $isDisabled =  false;

	/**
	 * Nom d'affichage
	 * @var string
	 */
	public $displayName = '';

	/**
	 * Prénom de l'utilisateur
	 * @var string
	 */
	public $givenName = '';

	/**
	 * Nom de l'utilisateur
	 * @var string
	 */
	public $sn = '';

	/**
	 * Adresse email
	 * @var string
	 */
	public $email = null;

	/**
	 * Alias de boîte exchange
	 * @var string
	 */
	public $exchangeAlias = null;

	/**
	 * Base de données sur laquelle est stockée la boîte exchange de l'utilisateur
	 * @var string
	 */
	public $exchangeBdd = null;

	/**
	 * Date de création (timestamp)
	 * @var int
	 */
	public $created = 0;

	/**
	 * Date de dernière connexion (timestamp)
	 * @var int
	 */
	public $lastLogon = 0;

	/**
	 * Groupes auxquels appartient l'utilisateur
	 * @var array
	 */
	public $groups = array();

	/**
	 * Matricule de l'utilisateur
	 * @var string
	 */
	public $employeeID = null;

} 