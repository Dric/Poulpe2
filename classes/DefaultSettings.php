<?php

/**
 * Creator: Dric
 * Date: 03/10/2016
 * Time: 13:54
 */
class DefaultSettings {

	/** Nom du site */
	const SITE_NAME = 'Serveur de dev';

	/** Type de BDD. */
	const DB_TYPE = 'mysql';

	/** Nom de la base de données */
	const DB_NAME = 'poulpe2';

	/** Utilisateur de la base de données */
	const DB_USER = 'poulpe2';

	/** Mot de passe de la base de données */
	const DB_PASSWORD = 'poulpe2';

	/** Serveur de base de données */
	const DB_HOST = 'localhost';

	/** Authentification obligatoire */
	const AUTH_MANDATORY = true;

	/** Authentification via ldap ou sql */
	const AUTH_MODE = 'sql';

	/** Longueur minimale du mot de passe (authentification sql) */
	const PWD_MIN_SIZE = 6;

	/** Nom courant des serveurs LDAP */
	const LDAP_SERVERS = array();

	/** Nom du domaine LDAP */
	const LDAP_DOMAIN = '';

	/** Nom utilisateur pour se connecter à LDAP */
	const LDAP_BIND_NAME = 'LDAPUser';

	/** Mot de passe utilisateur pour se connecter à LDAP */
	const LDAP_BIND_PWD = 'pwd';

	/** Conteneur de recherche des comptes LDAP (DC=contoso,DC=com) */
	const LDAP_DC = '';

	/** OU dans laquelle chercher les comptes utilisateurs */
	const LDAP_AUTH_OU = '*';

	/** Groupe autorisé à se connecter (tous les groupes si vide) */
	const LDAP_GROUP = '';

	/** Clé de salage d'authentification */
	const SALT_AUTH = 'Change me at setup';

	/** Clé de salage du cookie */
	const SALT_COOKIE = 'Change me at setup';

	/** Nom du cookie d'authentification */
	const COOKIE_NAME = 'poulpe2';

	/** Durée de l'authentification par cookie (en heures) */
	const COOKIE_DURATION = 4320;

	/** URL des appels aux modules */
	const MODULE_URL = 'module/';

	/** Répertoire des modules (respecter la casse) */
	const MODULE_DIR = 'Modules';

	/** Répertoire des images */
	const IMAGE_PATH = 'img/';

	/** Répertoire des avatars */
	const AVATAR_PATH = 'img/avatars/';

	/** Avatar par défaut */
	const AVATAR_DEFAULT = 'default.jpg';

	/** Répertoire des images chargées */
	const UPLOAD_IMG = 'uploads/img';

	/** Répertoire des fichiers chargés */
	const UPLOAD_FILE = 'uploads/files';

	/** Extensions d'images autorisées */
	const ALLOWED_IMAGES_EXT = array('jpg', 'jpeg', 'gif', 'png');

	/** Taille maximum de l'image chargée pour l'avatar (en ko) */
	const AVATAR_MAX_SIZE = 400;

	/** Liste des valeurs possibles pour le nombre d'entrées par page */
	const PER_PAGE_VALUES = array(5, 10, 15, 20, 25, 50, 100);

	/** Afficher le fil d'ariane */
	const DISPLAY_BREADCRUMB = true;

	/** Afficher le lien vers la page d'accueil du site */
	const DISPLAY_HOME = true;

	/** Module en page d'accueil */
	const HOME_MODULE = 'home';

	/** Intervalle de vérification des mises à jour (en jours ) */
	const UPDATE_CHECKS_INTERVAL = 7;

	/** Serveur SSH distant de référence */
	const SSH_REMOTE_SERVER = '';

	/** Identifiant de connexion au serveur SSH */
	const SSH_USER = '';

	/** Mot de passe de connexion SSH */
	const SSH_PWD = '';

	/** Port d'ouverture de session SSH */
	const SSH_PORT = 22;

	/** Debug mode */
	const DEBUG = true;

	/**
	 * Retourne les différences entre deux tableaux en prenant en compte les clés, de façon récursive
	 * (un array_diff_assoc pour tableaux multi-dimensionnels)
	 *
	 * @from http://php.net/manual/fr/function.array-diff-assoc.php#111675
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return array
	 */
	protected static function array_diff_assoc_recursive($array1, $array2) {
		$difference=array();
		foreach($array1 as $key => $value) {
			if( is_array($value) ) {
				if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
					$difference[$key] = $value;
				} else {
					$new_diff = self::array_diff_assoc_recursive($value, $array2[$key]);
					if( !empty($new_diff) )
						$difference[$key] = $new_diff;
				}
			} else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
				$difference[$key] = $value;
			}
		}
		return $difference;
	}

	/**
	 * Retourne la liste des constantes définies
	 * @return array
	 */
	public static function get_class_constants($inheritance = true)
	{
		if (get_parent_class(static::class)){
			$parent = new ReflectionClass(get_parent_class(static::class));
			$parentConsts = $parent->getConstants();
		}

		$reflect = new ReflectionClass(static::class);
		$classConsts = $reflect->getConstants();
		if (!$inheritance and isset($parentConsts)){
			return self::array_diff_assoc_recursive($classConsts, $parentConsts);
		}else{
			return $classConsts;
		}
	}
}