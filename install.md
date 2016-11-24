# Installation de Poulpe2

Il existe un installeur qui automatise pas mal de choses : [Installeur de Poulpe2](install.php)

Pour info, voici la méthode manuelle :

## SQL

Créez une base de données puis importez-y ce script SQL :

	--
	-- Structure de la table `ACL`
	--

	CREATE TABLE `ACL` (
	  `component` varchar(50) NOT NULL COMMENT 'Composant (module, profil, site, etc.)',
	  `id` mediumint(9) NOT NULL COMMENT 'ID du composant',
	  `user` int(6) NOT NULL COMMENT 'ID de l''utilisateur',
	  `type` varchar(50) NOT NULL COMMENT 'Type d''ACL (admin, accès)',
	  `value` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Valeur de l''ACL (0 : refusé, 1: permis)',
	  UNIQUE KEY `componentID_User` (`component`,`id`,`user`,`type`),
	  KEY `id` (`id`),
	  KEY `user` (`user`),
	  KEY `component` (`component`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	--
	-- Contenu de la table `ACL`
	--

	INSERT INTO `ACL` (`component`, `id`, `user`, `type`, `value`) VALUES
	('admin', 0, 1, 'access', 1),
	('admin', 0, 1, 'admin', 1),
	('admin', 0, 1, 'modify', 1);

	--
	-- Structure de la table `logs`
	--

	CREATE TABLE `logs` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `user` int(6) DEFAULT NULL COMMENT 'ID de l''utilisateur qui a généré l''événement',
	  `component` varchar(50) DEFAULT NULL COMMENT 'Composant - On ne relie pas les événements directement aux modules, afin que si on supprime le module l''événement reste',
	  `type` varchar(50) NOT NULL COMMENT 'Type de log',
	  `data` varchar(255) DEFAULT NULL COMMENT 'Données liées à l''événement',
	  `time` int(11) NOT NULL COMMENT 'Timestamp Unix de l''événement',
	  PRIMARY KEY (`id`),
	  KEY `user` (`user`),
	  KEY `component` (`component`),
	  KEY `type` (`type`),
	  KEY `time` (`time`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

	--
	-- Structure de la table `modules`
	--

	CREATE TABLE `modules` (
	  `id` int(6) NOT NULL AUTO_INCREMENT,
	  `name` varchar(150) NOT NULL,
	  `class` varchar(100) NOT NULL COMMENT 'Classe sous laquelle est déclarée le module',
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `name` (`name`),
	  UNIQUE KEY `classe` (`class`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

	--
	-- Structure de la table `modules_settings`
	--

	CREATE TABLE `modules_settings` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `module` int(6) NOT NULL COMMENT 'ID du module',
	  `setting` varchar(150) NOT NULL COMMENT 'Nom du paramètre',
	  `type` varchar(20) NOT NULL DEFAULT 'global' COMMENT 'Type de paramètre : global ou user',
	  `value` varchar(255) DEFAULT NULL COMMENT 'Valeur du paramètre',
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `module_2` (`module`,`setting`),
	  KEY `module` (`module`),
	  KEY `type` (`type`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

	--
	-- Structure de la table `modules_users_settings`
	--

	CREATE TABLE `modules_users_settings` (
	  `moduleSetting` int(11) NOT NULL COMMENT 'Id du paramètre de module',
	  `module` int(6) NOT NULL COMMENT 'ID du module',
	  `user` int(6) NOT NULL COMMENT 'ID de l''utilisateur',
	  `value` varchar(255) NOT NULL COMMENT 'Valeur du paramètre défini par l''utilisateur',
	  UNIQUE KEY `userSetting` (`moduleSetting`,`user`),
	  KEY `moduleSetting` (`moduleSetting`),
	  KEY `user` (`user`),
	  KEY `module` (`module`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	--
	-- Structure de la table `users`
	--

	CREATE TABLE `users` (
	  `id` int(6) NOT NULL AUTO_INCREMENT,
	  `name` varchar(150) NOT NULL,
	  `email` varchar(250) DEFAULT NULL,
	  `pwd` varchar(150) DEFAULT NULL,
	  `hash` varchar(255) DEFAULT NULL,
	  `avatar` varchar(100) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `name` (`name`),
	  KEY `email` (`email`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

	--
	-- Contraintes pour la table `modules_settings`
	--
	ALTER TABLE `modules_settings`
	  ADD CONSTRAINT `modules_settings_ibfk_1` FOREIGN KEY (`module`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

	--
	-- Contraintes pour la table `modules_users_settings`
	--
	ALTER TABLE `modules_users_settings`
	  ADD CONSTRAINT `modules_users_settings_ibfk_3` FOREIGN KEY (`module`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	  ADD CONSTRAINT `modules_users_settings_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	  ADD CONSTRAINT `modules_users_settings_ibfk_2` FOREIGN KEY (`moduleSetting`) REFERENCES `modules_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

## Fichier de config

Créez un fichier dans le répertoire `classes` nommé `Settings.php` et collez-y le code suivant :

    <?php
    
    /**
     * Paramètres de poulpe2
     *
     * Importez les constantes de `classes/DefaultSettings` et modifiez-les pour adapter les paramètres à votre instance
     */
    class Settings extends DefaultSettings {
    
    }
    
    ?>

Editez le fichier `classes/DefaultSettings` et copiez dans `classes/Settings` les constantes (en modifiant leur valeur) que vous souhaitez adapter pour votre instance de Poulpe2.

Les clés de salage doivent être changées impérativement. Vous pouvez vous contenter de changer quelques caractères, ou bien aller piocher sur Internet une clé de salage auto-générée.

Je vous suggère toutefois de choisir une longue phrase qui n'aura de sens que pour vous, du type : `Cette clé de salage est longue, mais parfaitement compréhensible pour moi !`

## Serveur Linux

### Vous devez cloner le dépôt des modules dans un répertoire `Modules`.

Dans le répertoire racine, faites par exemple :

	git clone https://github.com/Dric/Poulpe2_Modules.git Modules

### Dépôt CHGS

- Pour effectuer des opérations à distance sur des serveurs Windows, il faut installer un package debian situé dans `Modules/BandesTina`. Une fois dans ce répertoire, saisissez cette commande :

    sudo dpkg -i winexe_1.00-1_i386.deb

- Le répertoire `/mnt` doit être accessible en écriture à l'utilisateur apache (`www-data` par défaut)
- La possibilité de monter des répertoires en cifs doit être active sur le serveur. Avec Ubuntu 14, il faut installer :

    sudo apt-get install cifs-utils
    
- Les modules php suivants doivent être installés sur le serveur : `curl`, `ldap`

- L'utilisateur apache doit avoir le droit d'invoquer sudo sans mot de passe pour la commande `mount`. Ceci est quand potentiellement un trou de sécurité...

Dans un terminal, saisir :

    sudo visudo

Ajouter à la dernière ligne :

    Defaults        verifypw = any
    www-data ALL= (ALL:ALL) NOPASSWD: /bin/mount,/bin/umount,/sbin/mount.cifs,/sbin/umount.cifs,/usr/bin/timeout

Attention : si vous lancez apache sous un autre nom (`administrateur` par exemple), modifiez la ligne du dessus en conséquence.

### Première connexion

Le premier utilisateur à se connecter est automatiquement bombardé administrateur.