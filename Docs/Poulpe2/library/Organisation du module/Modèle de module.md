# Modèle de module

Ce modèle de module vous servira de base lors de la création d'un module. Le module en exemple s'appelle `LittlePony`.

## Emplacement des fichiers

On commence par créer un répertoire `LittlePony` dans le répertoire `Modules`. Attention à la casse : du point de vue de PHP, `littlepony` est différent de `LittlePony`.

Dans ce répertoire, créez un fichier nommé `LittlePony.php`.

## Déclaration du module

Dans ce fichier, commencez par ceci :

    <?php
    namespace Modules\LittlePony;

    use Modules\Module;

    class LittlePony extends Module{
    	protected $name = 'Mon petit Poney';
    	protected $title = 'Le meilleur des poneys !';
    }
    ?>

Le nom de la classe, le nom de fichier, le namespace et le nom de répertoire doivent être identiques.

Un module doit avoir une propriété `$name` et une propriété `$title`. Ne mettez pas de caractères spéciaux dans le nom.

### Affichage principal du module

Un module sans affichage, ça ne sert à rien. Cet affichage est défini par la méthode `mainDisplay()` :

    	/**
       * Affichage principal
       */
      public function mainDisplay(){
        ?>
        <div class="row">
          <div class="col-md-10 col-md-offset-1">
            <div class="page-header">
              <h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
            </div>
            <p><?php echo $this->title; ?>.</p>
            <p>Ceci est le module <strong>LittlePony</strong></p>
          </div>
        </div>
        <?php
      }

L'affichage doit respecter les standards de [Bootstrap](../Bootstrap/index.html). Vous noterez dans la balise `<h1>` qu'on affiche le nom du module suivi de `<?php $this->manageModuleButtons(); ?>`, qui permet d'afficher les boutons de paramétrage et d'autorisations sur le module.

