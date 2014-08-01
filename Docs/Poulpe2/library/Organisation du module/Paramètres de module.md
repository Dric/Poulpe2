# Paramètres d'un module

Les paramètres sont gérés via des objets `Field`, ce qui permet de gérer également leur affichage. En effet, l'écran de paramétrage du module est automatiquement généré à partir des paramètres définis dans le module.

Les paramètres sont définis au sein de la méthode `defineSettings()` du module. Si vous créez un module, vous devrez donc créer cette méthode :

    public function defineSettings(){
      // Paramètres du module
    }

## Ajouter un paramètre au module

Les paramètres sont ajoutés à la propriété `$settings` du module :

    $this->settings['NomPatate'] = new String('NomPatate', 'Toto', 'Nom de la patate');

L'index du paramètre dans le tableau `$settings` doit être identique au nom donné au paramètre (premier argument de l'objet `String`).

Si vous êtes administrateur du module, vous verrez en allant dans les paramètres du module un champ de saisie vous demandant un nom de pomme de terre, et pré-rempli avec `Toto`.

## Paramètres définissables par utilisateur

Vous pouvez définir que certains paramètres seront personnalisables par les utilisateurs :

    $this->settings['NomPatate']->setUserDefinable();

En tant qu'administrateur, vous devrez aller dans les paramètres du module et autoriser les utilisateurs à personnaliser certaines données. Les utilisateurs verront ensuite un écran de paramètres leur demandant un nom de pomme de terre.

## Accéder à la valeur d'un paramètre

On peut accéder à la valeur du paramètre grâce à sa méthode `getValue()` :

    $nom = $this->settings['NomPatate']->getValue();

Si le paramètre est personnalisable par les utilisateurs, cette méthode retourne automatiquement la valeur définie par l'utilisateur si elle existe.