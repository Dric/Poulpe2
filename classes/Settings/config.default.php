<?php
/**
 *Configuration du site
 */

/** Nom du site */
define('SITE_NAME', 'Poulpe2');
/** Type de BDD. */
define('DB_TYPE', 'mysql');
/** Nom de la base de données. */
define('DB_NAME', 'poulpe2');
/** Utilisateur de la base de données MySQL. */
define('DB_USER', 'poulpe2');
/** Mot de passe de la base de données MySQL. */
define('DB_PASSWORD', 'poulpe2');
/** Adresse de l'hébergement MySQL. */
define('DB_HOST', 'localhost');
/** Authentification via ldap ou sql */
define('AUTH_MODE', 'sql');
/** Longueur minimale du mot de passe (authentification sql) */
define('PWD_MIN_SIZE', 6);
/** Authentification obligatoire */
define('AUTH_MANDATORY', true);
/** Nom courant des serveurs LDAP */
define('LDAP_SERVERS', '');
/** Nom du domaine LDAP */
define('LDAP_DOMAIN', '');
/** Nom utilisateur pour se connecter à LDAP */
define('LDAP_BIND_NAME', '');
/** Mot de passe utilisateur pour se connecter à LDAP */
define('LDAP_BIND_PWD', '');
/** Conteneur de recherche des comptes LDAP */
define('LDAP_DC', '');
/** OU dans laquelle chercher les comptes utilisateurs */
define('LDAP_AUTH_OU', '');
/** Groupe autorisé à se connecter */
define('LDAP_GROUP', '');
/** Clé de salage d'authentification */
define('SALT_AUTH', 'wziB.(eaI^^?X/`r}I|rwF{q1%r2__ij/}&|U&(}va+tNg`>gGf69A&_9d');
/** Clé de salage du cookie */
define('SALT_COOKIE', 'wziB.(eaI^^?X/`fgh99*=!_^8x&|P$s8u60 ]8SDaU&(}va+tNg`>9d');
/** Nom du cookie d'authentification */
define('COOKIE_NAME', 'poulpe2');
/** Durée de l'authentification par cookie (en heures) */
define('COOKIE_DURATION', '4320');
/** URL des appels aux modules */
define('MODULE_URL', 'index.php?module=');
/** Répertoire des images */
define('IMAGE_PATH', 'img/');
/** Répertoire des avatars */
define('AVATAR_PATH', 'img/avatars/');
/** Avatar par défaut */
define('AVATAR_DEFAULT', 'poulpe2.png');
/** Répertoire des images chargées */
define('UPLOAD_IMG', 'uploads/img');
/** Répertoire des fichiers chargés */
define('UPLOAD_FILE', 'uploads/files');
/** Extensions d'images autorisées */
define('ALLOWED_IMAGES_EXT', serialize(array('jpg', 'jpeg', 'gif', 'png')));
/** Taille maximum de l'image chargée pour l'avatar (en ko) */
define('AVATAR_MAX_SIZE', 400);
/** Liste des valeurs possibles pour le nombre d'entrées par page */
define('PER_PAGE_VALUES', '5, 10, 15, 20, 25, 50, 100');
/** Afficher le fil d'ariane */
define('DISPLAY_BREADCRUMB', true);
/** Afficher le lien vers la page d'accueil du site */
define('DISPLAY_HOME', true);
/** Module en page d'accueil */
define('HOME_MODULE', 'home');
/** Debug mode */
define('DEBUG', false);
/** Debug détaillé */
define('DETAILED_DEBUG', false);
