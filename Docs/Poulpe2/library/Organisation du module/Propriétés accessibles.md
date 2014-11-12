# Propriétés accessibles au sein d'un module

Plusieurs propriétés sont accessibles de base au sein d'un module. Ces propriétés ne sont pas publiquement accessibles.

Vous pouvez bien évidemment définir d'autres propriétés pour un module, il vous suffit pour cela de les déclarer en début de classe. Ex :

    /**
     * Mode patate activé
     * @var bool
     */
    protected $patator = true;

## Name

Le nom du module, de type `string`

    echo $this->name;
    // affiche 'LittlePony'

## Title

Un court descriptif du module, de type `string`

    echo $this->title;
    // affiche 'Le meilleur des poneys !'
    
## Id

Le numéro identifiant un module dans Poulpe2, de type `int`

    echo $this->id;
    // affiche 2

## Settings

Ensemble des paramètres d'un module, de type `array`. Tous les paramètres inscrits dans ce tableau sont personnalisables par les administrateurs du module.

Voir [Paramètres de module](Organisation%20du%20module/Paramètres%20de%20module.md).

## AllowUsersSettings

Autorise ou non certains paramètres à être personnalisables par les utilisateurs non-administrateurs du module, de type `bool`.

    echo $this->allowUserSettings;
    // affiche false

## DbTables

Liste des tables définies au sein du module, de type `array`. Définir les tables du module permet de les déclarer en base de données automatiquement à l'activation du module.

Voir [Gestion des tables](Base%20de%20données/Gestion%20des%20tables.md).

## BreadCrumb

Tableau permettant de définir le fil d'ariane de la page (chemin dans l'arborescence du site pour arriver jusqu'à la page), de type `array`.

Ce fil d'ariane est défini automatiquement pour les pages créées de façon non dynamique : en déclarant une méthode `module<Page>`, on crée une page de module qui aura son fil d'ariane nommé `<Page>`.

## PostedData

Ce tableau permet de récupérer automatiquement les envois de formulaires, de type `array` associatif.

Voir [Gestion des formulaires](Formulaires/Gestion%20des%20formulaires.md).

## Url

Url du module, définie de façon automatique, de type `string`.

    echo $this->url;
    // affiche index.php?module=LittlePony