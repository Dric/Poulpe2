<?php
    
/**
* Paramètres de poulpe2
*
* Importez les constantes de `classes/DefaultSettings` et modifiez-les pour adapter les paramètres à votre instance
*/
class Settings extends DefaultSettings {


	/** Nom du site */
	const SITE_NAME = 'Serveur magnifique de dev';

	/** Nom de la base de données */
	const DB_NAME = 'tools2';

	/** Utilisateur de la base de données */
	const DB_USER = 'tools2';

	/** Mot de passe de la base de données */
	const DB_PASSWORD = 'tools2';

	/** Authentification obligatoire */
	const AUTH_MANDATORY = true;

	/** Authentification via ldap ou sql */
	const AUTH_MODE = 'ldap';

	/** Nom courant des serveurs LDAP */
	const LDAP_SERVERS = array('172.32.1.90', '172.32.1.91', '192.168.2.3', '192.168.1.2');

	/** Nom du domaine LDAP */
	const LDAP_DOMAIN = 'intra.epsi.fr';

	/** Nom utilisateur pour se connecter à LDAP */
	const LDAP_BIND_NAME = 'tech_intranet';

	/** Mot de passe utilisateur pour se connecter à LDAP */
	const LDAP_BIND_PWD = 'Compte 2 service pour Intr@net';

	/** Conteneur de recherche des comptes LDAP (DC=contoso,DC=com) */
	const LDAP_DC = 'DC=intra,DC=epsi,DC=fr';

	/** Groupe autorisé à se connecter (tous les groupes si vide) */
	const LDAP_GROUP = 'Informatique';

	/** Clé de salage d'authentification */
	const SALT_AUTH = 'wziB.(eaI^^?X/`r}I|rwF{q1%fM_^8xj/}&|r5#!QC0 ]8SDaU&(}va+tNg`>9d';

	/** Clé de salage du cookie */
	const SALT_COOKIE = 'wziB.(eaI^^?X/`r}I|fj_hq1%fM_^8xj/}&|P$s8u60 ]8SDaU&(}va+tNg`>9d';

	/** Nom du cookie d'authentification */
	const COOKIE_NAME = 'tools2Test';

	/** URL des appels aux modules */
	const MODULE_URL = 'module/';

	/** Répertoire des modules (respecter la casse) */
	const MODULE_DIR = 'Modules_CHGS';

	/** Afficher le fil d'ariane */
	const DISPLAY_BREADCRUMB = true;

	/** Afficher le lien vers la page d'accueil du site */
	const DISPLAY_HOME = true;

	/** Module en page d'accueil */
	const HOME_MODULE = 'CHGS';

	/** Debug mode */
	const DEBUG = true;
}
