Module gestion_bandes_tina
==========================

Gestion de l'externalisation des bandes du robot de sauvegarde

## Prérequis

Il va falloir installer winexe sur le serveur ubuntu qui héberge les Petits Outils Informatique.

Ouvrez une session sur le serveur :

	cd /var/www/tools/modules/gestion_bandes_tina/
	sudo dpkg -i winexe_1.00-1_i386.deb

Winexe installe un petit service sur le serveur Windows, nommé `winexesvc`. Pour le supprimer après exécution, utlisez le paramètre `--uninstall`.

Liste des paramètres : <http://opensourceinfo.blogspot.fr/2010/01/winexe.html>