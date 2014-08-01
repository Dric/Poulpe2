# Opérations sur la base de données

La base de données est gérée par un objet `Db`, qui est accessible par la variable globale `$db`.
Pour accéder à cet objet dans une méthode de module, il suffit de saisir cette commande :

    global $db;

Documentation de la classe : [Db](../Code/class-Db.Db.html)

## Interrogations (SELECT)

### Récupérer plusieurs enregistrements

Pour récupérer tous les enregistrements d'une table, on utilise la méthode `get()` :

    $items = $db->get('nom de la table');

Cette méthode accepte bien évidemment des arguments permettant de filtrer les enregistrements retournés. Par exemple pour récupérer les colonnes `id` et `name` des enregistrements qui sont actifs et les trier par le nom, on lance :

    $items = $db->get('nom de la table', array('id', 'name'), array('enabled' => true), array('name' => 'ASC'));

Les enregistrements sont retournés sous forme d'un tableau d'objets dont les propriétés sont les colonnes sélectionnées lors de la requête. Pour afficher les enregistrements dans `$items`, on utilise :

    foreach ($items as $item){
      echo $item->id.' - '.$item->name;
    }

### Récupérer une seule valeur

Pour récupérer directement une valeur, on utilise la méthode `getVal()`. Pour récupérer le nom d'un utilisateur dont l'identifiant est `6`, on utilise :

    $name = $db->getVal('users', 'name', array('id' => 6));

## Insertions (INSERT)

Pour insérer un enregistrement dans la base, on utilise la méthode `insert()` :

    $enregistrementID = $db->insert('produits', array('nom' => 'patate', 'prix' => 10000));

La commande retourne l'identifiant de l'enregistrement inséré.

Pour des insertions multiples, il faudra passer par une commande SQL classique.

## Mises à jour (UPDATE)

Pour mettre à jour des enregistrements, on utilise la méthode `update()` :

    $db->update('produits', array('prix' => 20000), array('catégorie' => 'patates'));

## Suppressions (DELETE)

Pour supprimer des enregistrements, on utilise la méthode `delete()` :

    $db->delete('produits', array('catégorie' => 'patates'));

## requête SQL

Parfois, les commandes ci-dessus ne suffisent pas. Il faut alors passer par une commande SQL avec :

    $resultats = $db->query('SELECT * FROM produits WHERE catégorie = "patates"');