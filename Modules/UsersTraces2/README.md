Module UsersTraces2
==========================

Centralisation et visualisation des logs de connexion à la ferme XenApp7

## Générer un événement de connexion

UsersTraces2 utilise une API pour stocker un événement de connexion dans la base de données.

Il faut envoyer une requête de la forme `http://poulpe2/api/traces/:server/:app/:user/:event/:data`

* `:server` : Serveur sur lequel est connecté l'utilisateur qui a généré l'événement
* `:app`    : Application ou service (ex : `Login` pour un événement de connexion/déconnexion, `Cariatides` pour une appli, etc.)
* `:user`   : Utilisateur ayant généré le log
* `:event`  : Type d'événement (ex : `connexion`, `fermeture`, `déconnexion`, `reconnexion`...)
* `:data`   : Données supplémentaires éventuelles

### Linux shell

    curl http://poulpe2/api/traces/:server/:app/:user/:event/:data

### Powershell

    Invoke-RestMethod -Uri http://poulpe2/api/traces/:server/:app/:user/:event/:data -Method get