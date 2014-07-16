Petits Outils Informatiques
===========================

Des petits outils divers et variés pour simplifier la vie des informaticiens.

## Pré-requis

### Serveur

- PHP 5.4+
- Mysql 5.5+
- Serveur Linux
- Serveur web Apache ou autre (testé seulement sous Apache)

#### Serveur Linux

- Le répertoire `/mnt` doit être accessible en écriture à l'utilisateur apache (`www-data` par défaut)
- L'utilisateur apache doit avoir le droit d'invoquer `sudo` sans mot de passe pour monter tous les répertoires réseau. Ceci est quand même un gros trou de sécurité...

Dans un terminal, saisir :

    sudo visudo

Ajouter à la dernière ligne :

    www-data ALL = NOPASSWD: ALL

Attention : si vous lancez apache sous un autre nom (`administrateur` par exemple), modifiez la ligne du dessus en conséquence.

### Client

- Javascript recommandé (c'est activé par défaut) mais pas obligatoire
- L'utilisation d'un cookie (c'est permis par défaut) et c'est obligatoire
- Un navigateur décent : Firefox, Opera, Chrome et IE8 minimum (mais pour un visuel plus sympa, prenez IE9+)
- Des yeux (ça aussi c'est fourni par défaut)
- Un peu d'amour

## Composants externes

- [Twitter Bootstrap](http://getbootstrap.com) 3.2.0 : est un framework html, css et javascript. En clair il peut gérer toute la partie affichage dès lors qu'on lui file quelque chose à afficher.
- [jQuery](http://jquery.com) 1.11.0 : framework Javascript utilisé par Bootstrap.
- [Bootstrap Switch](http://www.bootstrap-switch.org) 3 : plugin jQuery qui change les cases à cocher en switch d'activation (similaire à ce qu'on voit sur les smartphones).
- [PNotify Plugin](http://sciactive.com/pnotify/) 1.3.1 : plugin jQuery de notifications flottantes.
- [Bootstrap Confirmation](https://github.com/mistic100/Bootstrap-Confirmation) 2.0 : plugin de jQuery et Bootstrap qui gère les confirmations via popover.
- [Bootstrap 3 Typeahead](https://github.com/bassjobsen/Bootstrap-3-Typeahead) : Plugin d'auto-completion qui a été supprimé de Bootstrap 3.
- [Pagedown Bootstrap](http://kevin.oconnor.mp/pagedown-bootstrap) : Editeur gérant la syntaxe Markdown
- [Highlight.js](http://highlightjs.org) : Plugin de coloration syntaxique de code
- [DataTables](http://datatables.net) : Plugin jQuery d'organisation de tables HTML avec tri, recherche et pagination
- [Bootstrap Validator](https://github.com/1000hz/bootstrap-validator) 0.5 : plugin jQuery et Bootstrap qui gère la validation des champs de formulaires

## Installation

Voir Le fichier `install.md`.

## TODO

- Script d'installation
- Doc de développement de plugins
- Gérer la barre de menu avec JS désactivé

## Documentation

- Voir le répertoire `Docs`

## Divers

- Codé sous PHPStorm 7
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