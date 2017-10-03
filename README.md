MonsieurPoulpe2 (alias Poulpe2)
===============================

Un framework modulaire en php.

## Pré-requis

### Serveur

- PHP 7.0+
- Mysql 5.5+
- Serveur Linux (testé sous Ubuntu seulement)
- Serveur web Apache, lighttpd ou autre (testé seulement sous Apache et lighttpd)

#### Serveur Linux

Les pretty URL (du type `http://poulpe2/module/FileBrowser`) sont disponibles seulement sous Apache. Pour les activer, il faut modifier la constante `MODULE_URL` dans le fichier `config.php` en `module/`.

### Client

- Javascript recommandé (c'est activé par défaut) mais pas obligatoire
- L'utilisation d'un cookie (c'est permis par défaut) et c'est obligatoire
- Un navigateur décent : Firefox, Opera, Chrome et IE8 minimum (mais pour un visuel plus sympa, prenez IE9+)
- Des yeux (ça aussi c'est fourni par défaut)
- Un peu d'amour

## Composants externes

- [Twitter Bootstrap](http://getbootstrap.com) 3.3.7 : est un framework html, css et javascript. En clair il peut gérer toute la partie affichage dès lors qu'on lui file quelque chose à afficher.
- [Yeti](https://bootswatch.com/yeti/) 3.3.6 : Thème pour Bootstrap
- [Font Awesome](http://fortawesome.github.io/Font-Awesome/) 4.7.0 : ensemble d'icônes contenues dans une police d'écriture, remplaçant `Glyphicon` de Bootstrap.
- [jQuery](http://jquery.com) 1.11.0 : framework Javascript utilisé par Bootstrap.
- [Bootstrap Switch](http://www.bootstrap-switch.org) 3 : plugin jQuery qui change les cases à cocher en switch d'activation (similaire à ce qu'on voit sur les smartphones).
- [PNotify Plugin](http://sciactive.com/pnotify/) 1.3.1 : plugin jQuery de notifications flottantes.
- [Bootstrap Confirmation](https://github.com/mistic100/Bootstrap-Confirmation) 2.0 : plugin de jQuery et Bootstrap qui gère les confirmations via popover.
- [Bootstrap 3 Typeahead](https://github.com/bassjobsen/Bootstrap-3-Typeahead) : Plugin d'auto-completion qui a été supprimé de Bootstrap 3.
- [Pagedown Bootstrap](http://kevin.oconnor.mp/pagedown-bootstrap) : Editeur gérant la syntaxe Markdown
- [Highlight.js](http://highlightjs.org) : Plugin de coloration syntaxique de code
- [DataTables](http://datatables.net) 1.10.15 : Plugin jQuery d'organisation de tables HTML avec tri, recherche et pagination
- [Bootstrap Validator](https://github.com/1000hz/bootstrap-validator) 0.11.9 : plugin jQuery et Bootstrap qui gère la validation des champs de formulaires
- [Bootstrap v3 datetimepicker](https://github.com/Eonasdan/bootstrap-datetimepicker) 3.0.0 : Plugin jQuery de saisie de date et heure
- [Autosize](http://www.jacklmoore.com/autosize/) 3.0.5 : Plugin jQuery permettant de changer dynamiquement la hauteur des Textareas pour qu'ils s'adaptent à leurs contenus
- [bootstrap-waitingfor](https://github.com/ehpc/bootstrap-waitingfor) Affiche un modal pour patienter un peu pendant un chargement
- [php-markdown](https://michelf.ca/projets/php-markdown/) 1.7.0 : Gestion de la syntaxe Markdown en PHP

## Installation

- Clonez poulpe2
- Clonez les modules dans un sous répertoire de Poulpe2. Par exemple, en étant dans le répertoire d'install de Poulpe2 :

		git clone https://github.com/Dric/Poulpe2_Modules.git Modules
- Accédez à Poulpe2 au sein de votre navigateur internet, l'installeur va se lancer.


Pour une installation manuelle, voir Le fichier `install.md`.

## TODO

- Compléter le script d'installation avec l'installation automatique des modules
- Doc de développement de modules (en cours)
- Gérer la barre de menu avec JS désactivé

## Bugs connus

- L'affichage des mots de passe ne permet plus au champ de mot de passe de passer la validation.

## Documentation

- Activer le module `ModuleCreator` qui donne les liens vers les différentes documentations disponibles
- Voir le répertoire `Docs`
- Documentation générée avec [ApiGen](http://apigen.org)

Paramètres de génération de doc (commande lancée à partir du répertoire racine de Poulpe2) :

     apigen -s . -s "classes/Modules/Module.php" -s "classes/Modules/ModulesManagement.php" -s "classes/Settings/config.php" -d ./Docs/Code --exclude "*/Docs" --exclude "*/fonts" --exclude "*/img" --exclude "*/js" --exclude "*/Modules*" --template-config "/usr/share/php/data/ApiGen/templates/bootstrap/config.neon"  --allowed-html "b,i,a,ul,ol,li,p,br,var,samp,kbd,tt,h1,h2,h3,h4" --report toDocument.txt

## Divers

- Codé sous PHPStorm
- Favicons générées sur <http://realfavicongenerator.net>
- Expressions régulières créées à l'aide de <http://regex101.com>

### Evolutions majeures depuis la v1

- Le script reste fonctionnel même avec Javascript désactivé (à l'exception de la barre de menus)
- Utilisation de PHP5 et de la programmation orientée objet
- Utilisation de Bootstrap 3 - le site est maintenant adaptatif aux différentes résolutions (PC, tablettes, smartphones)
- Meilleure cohérence entre les modules
- Design revu
- Les autorisations ne sont plus basées sur des groupes mais directement sur les utilisateurs. Elles sont en revanche plus détaillées, un utilisateur pouvant être admin d'un module sans être admin du site complet.
- Meilleure fiabilité
- Sécurité améliorée

### Régressions depuis la v1

- Faible utilisation des requêtes asynchrones, rendant la navigation potentiellement moins légère

### Troubleshooting

- Si les interrogations à LDAP mettent 5s à se faire, c'est qu'il y a un problème de résolution DNS sur le serveur. Le plus simple est d'indiquer les adresses IP des serveurs LDAP au lieu de leur nom.