# Relations

Craft has a powerful engine for relating elements to one another. You create those relationships using relational field types.

Craft comes with five relational field types:

* [Assets Fields](assets-fields.md)
* [Categories Fields](categories-fields.md)
* [Entries Fields](entries-fields.md)
* [Tags Fields](tags-fields.md)
* [Users Fields](users-fields.md)

Just like the other field types, you can add these to your [section](sections-and-entries.md#sections), [user](users.md), [asset](assets.md), [category group](categories.md), [tag group](tags.md), and [global sets](globals.md)’ field layouts.

## Terminology

Before working with relations in Craft, it's important to grasp the following terms, as they are relevant to the templating side of things.

Each relation involves two elements:

* **Source** element - it has the relational field, where you selected the other element.
* **Target** element - the one selected by the source.

How does this look in practice?

If we have an entry for a drink recipe where we select the ingredients as relationships (via an Entries Field), we'd label the elements as follows:

* Drink Recipe Entry: Source
* Ingredients: Target

To set this up, we create a new field of the Entries Field Type, give it the name Ingredients, check Ingredients as the source (the available elements will be from the Ingredients section), and leave the Limit field blank so we can choose as many ingredients as each recipe dictates.

Now we can assign the ingredients to each Drink entry via the new Ingredients relation field.


## Templating

Once we have our relations field set up, we can look at the options for outputting related elements in our templates.

### Getting Target Elements via the Source Element

If you’ve already got a hold of the source element in your template, like in the example below where we're outputting the Drink entry, you can access its target elements for a particular field in the same way you access any other field’s value: by the handle.

Calling the source's relational field handle (`ingredients`) returns an Element Criteria Model that can output the field’s target elements, in the field-defined order.

If we want to output the ingredients list for a drink recipe, we'd use the following:

```twig
{% if drink.ingredients|length %}

    <h3>Ingredients</h3>

    <ul>
        {% for ingredient in drink.ingredients %}
            <li>{{ ingredient.title }}</li>
        {% endfor %}
    </ul>

{% endif %}
```

You can also add any additional parameters supported by the element type:

```twig
{% for ingredient in drink.ingredients.section('ingredients') %}
    <li>{{ ingredient.title }}</li>
{% endfor %}
```


### The `relatedTo` Parameter

Assets, Categories, Entries, Users, and Tags each support a `relatedTo` parameter, enabling all kinds of crazy things.

In its simplest form, you can pass in one of these things to it:

* A <api:craft\elements\Asset>, <api:craft\elements\Category>, <api:craft\elements\Entry>, <api:craft\elements\User>, or <api:craft\elements\Tag> object
* An element’s ID
* An array of element objects and/or IDs

By doing that, Craft will return all of the elements related to the given element(s), regardless of which one’s the source or target.

```twig
{% set relatedDrinks = craft.entries.section('drinks').relatedTo(drink).all() %}
```

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
    sourceElement: drink,
    field: 'ingredients'
}) %}
```

Set the `sourceLocale` property if you want to limit the scope to relations created from a particular field. (Only do this if you set your relational field to be translatable.) You can set this to a locale ID.

```twig
{% set ingredients = craft.entries.section('ingredients').relatedTo({
    sourceElement: drink,
    sourceLocale: craft.locale
}) %}
```

#### Going Through Matrix

If you want to find elements related to a source element through a [Matrix](matrix-fields.md) field, just pass the Matrix field’s handle to the `field` parameter. If that Matrix field has more than one relational field and you want to target a specific one, you can specify the block type field’s handle using a dot notation:

```twig
{% set ingredients = craft.entries.section('ingredients').relatedTo({
    sourceElement: drink,
    field: 'ingredientsMatrix.relatedIngredient'
}).all() %}
```

#### Passing Multiple Relation Criteria

There might be times when you need to factor multiple types of relations into the mix. For example, outputting all of the current user’s favorite drinks that include espresso:

```twig
{% set espresso = craft.entries.section('ingredients').slug('espresso').first() %}

{% set cocktails = craft.entries.section('drinks').relatedTo(['and',
    { sourceElement: currentUser, field: 'favoriteDrinks' },
    { targetElement: espresso, field: 'ingredients' }
]).all() %}
```

That first argument (`'and'`) specified that the query must match _all_ of the relation criteria. You can pass `'or'` instead if you want _any_ of the relation criteria to match.
