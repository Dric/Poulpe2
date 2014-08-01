# Gestion des tables

La gestion des tables de base de données est intégrée dans Poulpe2. Pour gérer les tables, vous devez juste les déclarer en tant qu'objets `DbTable`. La table sera ensuite automatiquement créée à l'installation du module.

## Création

Dans un module, la déclaration des tables se fait dans la méthode `defineSettings()`. Comme toujours, il est conseillé de regarder le code source d'un module pour comprendre le fonctionnement de cette méthode.

    $table = new DbTable('nom de la table', 'titre de la table');

Pour que le module sache qu'il a une table à gérer, on ajoute celle-ci dans la propriété `$dbTables` du module.

    $this->dbTables['nom de la table'] = $table;

## Champs de la table

Les champs sont gérés par des objets `Field`, les mêmes utilisés pour les formulaires.

Chaque type de champ est géré par une classe PHP. Pour voir les propriétés de chaque type de champ, référez-vous à la [documentation des classes Field](../Code/namespace-Forms.Fields.html).

La contrainte supplémentaire par rapport aux champs de formulaire est que dans une table, les objets `Field` devront obligatoirement posséder un objet de paramètres `DbFieldSetting`, qui va définir la longueur du champ, s'il peut être nul, s'il doit être indexé, etc.

Pour voir quelles paramètres on peut définir, référez-vous à la [documentation de DbFieldSettings](../Code/class-Db.DbFieldSettings.html).

## Ajouter des champs

On commence par définir l'objet de paramètres du champ :

    $fieldSettings = new DbFieldSettings('text', true, 150, 'unique');

On vient de définir un champ qui sera affiché sous forme de texte, dont la valeur ne peut être nulle, d'une longueur de 150 caractères et sur lequel on crée index unique.

Ensuite on crée le champ en lui-même :

    $field = new String('name', null, 'Nom/Pseudo', null, null, $fieldSettings);

On a créé une colonne de type texte (`varchar`) dont le nom est `name`, le commentaire sur la colonne est `Nom/Pseudo`, et qui a les propriétés définies dans l'objet de paramètres.

Pour finir, on ajoute cette colonne à la table :

    $table->addField($field);

## Gérer automatiquement la table dans les paramètres

Si la table doit faire partie des paramètres du module, son affichage et ses enregistrements seront automatiquement gérés dans l'écran de paramétrage du module dès lors que vous l'affectez à un champ de paramètre spécial :

    $this->settings['nom de la table'] = new Table($table);
