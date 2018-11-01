# Relations

Craft has a very powerful engine for relating elements together.

That engine manifests itself through relational field types. Craft comes with 5 of them built-in:

* [assets-fields](assets-fields.md)
* [categories-fields](categories-fields.md)
* [entries-fields](entries-fields.md)
* [tags-fields](tags-fields.md)
* [users-fields](users-fields.md)

Just like the other field types, you can add these to your [section](sections-and-entries.md#sections), [user](users.md), [asset](assets.md), [category group](categories.md), [tag group](tags.md), and [global sets](globals.md)’ field layouts.

## Terminology

Each relation involves two elements: the **source** and the **target**.

* The **source** element is the one that has the relational field, where you selected the other element.
* The **target** element is the one that was selected by the source.

It’s important to grasp those terms, as they are relevant to the templating side of things.


## Templating

You’ve got a ton of options when it comes to outputting related elements in your templates.


### Getting Target Elements via the Source Element

If you’ve already got a hold of the source element in your template, you can access its target elements for a particular field in the same way you access any other field’s value: by its handle.

Calling the relational field’s handle will return an [ElementCriteriaModel](templating/elementcriteriamodel.md) that’s fully prepped to output the field’s target elements, in the field-defined order.

```twig
{% if cocktail.ingredients | length %}
    <h3>Featured Blog Posts</h3>

    <ul>
        {% for ingredient in cocktail.ingredients %}
            <li>{{ ingredient.getLink() }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

You can also add any additional parameters that are supported by the element type:

```twig
{% for ingredient in cocktail.ingredients.section('ingredients') %}
    <li>{{ ingredient.getLink() }}</li>
{% endfor %}
```




### The `relatedTo` Param

[craft.assets](templating/craft.assets.md), [craft.categories](templating/craft.categories.md), [craft.entries](templating/craft.entries.md), [craft.users](templating/craft.users.md), and [craft.tags](templating/craft.tags.md) each support a `relatedTo` param, enabling all kinds of crazy things.

#### Simple Usage

In its simplest form, you can pass in one of these things to it:

* An [AssetFileModel](templating/assetfilemodel.md), [CategoryModel](templating/categorymodel.md), [EntryModel](templating/entrymodel.md), [UserModel](templating/usermodel.md), or [TagModel](templating/tagmodel.md) object
* An element’s ID
* An array of element objects and/or IDs

By doing that, Craft will return all of the elements that are related to the given element(s), regardless of which one’s the source or target.

```twig
{% set relatedDrinks = craft.entries.section('cocktails').relatedTo(cocktail) %}
```

#### Getting More Serious

If you want to be a little more specific, `relatedTo` also accepts an object that contains the following properties:

* `element`, `sourceElement`, or `targetElement`
* `field` _(optional)_
* `sourceLocale` _(optional)_

Set the first property’s key depending on what you want to get back:

* Use `element` if you don’t care whether the returned elements are the source or the target of a relation with the element(s) you’re passing in
* Use `sourceElement` if you want to find elements related to the given element, where the given element is the source of the relation
* Use `targetElement` if you want to find elements related to the given element, where the given element is the target of the relation

Set the `field` property if you want to limit the scope to relations created by a particular field. You can set this to either a field handle or a field ID (or an array of handles and/or IDs).

```twig
{% set ingredients = craft.entries.section('ingredients').relatedTo({
    sourceElement: cocktail,
    field: 'ingredients'
}) %}
```

Set the `sourceLocale` property if you want to limit the scope to relations created from a particular field. (Only do this if your relational field is set to be translatable.) You can set this to a locale ID.

```twig
{% set ingredients = craft.entries.section('ingredients').relatedTo({
    sourceElement: cocktail,
    sourceLocale: craft.locale
}) %}
```

#### Going Through Matrix

If you want to find elements that are related to a source element through a [Matrix](matrix-fields.md) field, just pass the Matrix field’s handle to the `field` param. If that Matrix field has more than one relational field and you want to target a specific one, you can specify the block type field’s handle using a dot notation:

```twig
{% set ingredients = craft.entries.section('ingredients').relatedTo({
    sourceElement: cocktail,
    field: 'ingredientsMatrix.relatedIngredient'
}) %}
```

#### Passing Multiple Relation Criteria

There might be times when you need to factor multiple types of relations into the mix. For example, outputting all of the current user’s favorite cocktails that include Gin:

```twig
{% set gin = craft.entries.section('ingredients').slug('gin').first() %}

{% set cocktails = craft.entries.section('cocktails').relatedTo('and',
    { sourceElement: currentUser, field: 'favoriteCocktails' },
    { targetElement: gin, field: 'ingredients' }
) %}
```

That first argument (`'and'`) specified that _all_ of the relation criteria must be matched. You can pass `'or'` instead if you want _any_ of the relation criteria to match.