---
"title": "Gestion des formulaires"
---

# Gestion des formulaires

## Créer un formulaire

Les formulaires sont créés en instanciant des objets `Form` :

    $form = New Form('nom du formulaire');

Documentation de code : [Form](../Code/class-Forms.Form.html)

Astuce : pour créer des formulaires horizontaux, utilisez la classe CSS `form-inline`.

## Champs des formulaires

Les champs sont des objets qui doivent avoir un nom et une catégorie.

Chaque type de champ est géré par une classe PHP. Pour voir les propriétés de chaque type de champ, référez-vous à la [documentation du code](../Code/namespace-Forms.Fields.html).

Un paramètre de module peut être personnalisable par les utilisateurs. Pour passer un paramètre en paramètre personnalisable, il suffit de faire :

    $field->setUserDefinable();

(voir gestion des paramètres de modules)

## Ajouter des champs

Les champs sont créés en instanciant des objets de champ. Chaque type de champ a sa propre classe.

On ajoute ensuite les champs au formulaire via la méthode `addField()` :

    $field = new String('name', null, 'Nom ou pseudo');
    $form->addField($field);

## Afficher le formulaire

Une fois tous vos champs, boutons et champs masqués ajoutés, il vous suffit d'afficher le formulaire grâce à cette commande :

    $form->display();

## Récupérer les données envoyées par le formulaire

Les formulaires envoyées par les objets Form et récupérés à l'aide de la classe `PostedData` que ce soit dans un module ou en dehors sont protégés contre les failles CSRF.

Les données sont déjà typées (les nombres entiers sont de type `int`, les checkbox sont de type `bool`, etc.) et organisées dans un tableau associatif `variable => valeur`. Elles ne sont en revanche pas traitées en dehors du typage, aussi faut-il les sécuriser (avec `htmlspecialchars` par exemple)

### Dans un module

Vous pouvez accéder aux données envoyées avec la propriété `$this->postedData` :

    $name = $this->postedData['name'];

### Hors-module

Utilisez la commande suivante :

    $data = \PostedData::get();
    $name = $data['name'];


