# Eager-Loading Elements

If a template is looping through a list of elements, and each of those elements must display one or more sub-elements, there’s a good chance the page’s performance will suffer.

For example, here’s a template that loops through a list of entries, and displays images related by an Assets field for each entry:

```twig
{% set entries = craft.entries()
    .section('news')
    .all() %}

{% for entry in entries %}
    {# Get the related asset, if there is one #}
    {% set image = entry.assetsField.one() %}
    {% if image %}
        <img src="{{ image.url }}" alt="{{ image.title }}">
    {% endif %}
{% endfor %}
```

This illustrates an _N+1_ query problem: In addition to the query executed to fetch the entries, an additional query will be executed for _each_ entry, to find its related asset. So the number of queries needed will be _N_ (the number of entries) _+ 1_ (the initial entries query). If there are 50 entries, this innocent-looking template code will cost the page 51 queries.

All hope is not lost, though. You can solve this with **eager-loading**, using the `with` criteria parameter.

The purpose of the `with` param is to tell Craft which sub-elements you’re going to be needing in advance, so that it can fetch them all up front, in as few queries as possible.

Here’s how to apply the `with` param to our example:

```twig
{% set entries = craft.entries()
    .section('news')
    .with(['assetsField'])
    .all() %}

{% for entry in entries %}
    {# Get the eager-loaded asset, if there is one #}
    {% set image = entry.assetsField[0] ?? null %}
    {% if image %}
        <img src="{{ image.url }}" alt="{{ image.title }}">
    {% endif %}
{% endfor %}
```

This template code will only cost 3 queries: one to fetch the entries, one to determine which assets should be eager-loaded, and one to fetch the assets. Then the entries are automatically populated with their respective related assets.

### Accessing Eager-Loaded Elements

Accessing eager-loaded elements works a little differently than accessing lazy-loaded elements.

Take a look at how we assigned the `image` variable in our examples, before and after applying the `with` param:

```twig
{# Before: #}
{% set image = entry.assetsField.one() %}

{# After: #}
{% set image = entry.assetsField[0] ?? null %}
```

When the assets _aren’t_ eager-loaded, `entry.assetsField` gives you an [asset query](element-queries/asset-queries.md) that is preconfigured to return the related assets.

However when the assets _are_ eager-loaded, `entry.assetsField` gets overwritten with an array of the eager-loaded assets. So `one()`, `all()`, and other element query methods are not available. Instead you must stick to the standard array syntaxes. In our example, we’re grabbing the first asset with `entry.assetsField[0]`, and we’re using Twig’s _null-coalescing operator_ (`??`) to define a default value (`null`) in case there is no related asset.


### Eager-Loading Multiple Sets of Elements

If you have multiple sets of elements you wish to eager-load off of the top list of elements, just add additional values to your `with` parameter.

```twig
{% set entries = craft.entries
    .section('news')
    .with([
        'assetsField',
        'matrixField'
    ])
    .all() %}

{% for entry in entries %}
    {# Get the eager-loaded asset, if there is one #}
    {% set image = entry.assetsField[0] ?? null %}
    {% if image %}
        <img src="{{ image.url }}" alt="{{ image.title }}">
    {% endif %}

    {# Loop through any eager-loaded Matrix blocks #}
    {% for block in entry.matrixField %}
        {{ block.textField }}
    {% endfor %}
{% endfor %}
```



### Eager-Loading Nested Sets of Elements

It’s also possible to load _nested_ sets of elements, using this syntax:

```twig
{% set entries = craft.entries()
    .section('news')
    .with([
        'entriesField.assetsField'
    ])
    .all() %}

{% for entry in entries %}
    {# Loop through any eager-loaded sub-entries #}
    {% for relatedEntry in entry.entriesField %}
        {# Get the eager-loaded asset, if there is one #}
        {% set image = relatedEntry.assetsField[0] ?? null %}
        {% if image %}
            <img src="{{ image.url }}" alt="{{ image.title }}">
        {% endif %}
    {% endfor %}
{% endfor %}
```

### Defining Custom Parameters on Eager-Loaded Elements

You can define custom criteria parameters that will get applied as elements are being eager-loaded, by replacing its key with an array that has two values: the key, and an object that defines the criteria parameters that should be applied.

```twig
{% set entries = craft.entries()
    .section('news')
    .with([
        ['assetsField', { kind: 'image' }]
    ])
    .all() %}
```

When eager-loading nested sets of elements, you can apply parameters at any level of the eager-loading path.

```twig
{% set entries = craft.entries()
    .section('news')
    .with([
        ['entriesField', { authorId: 5 }],
        ['entriesField.assetsField', { kind: 'image' }]
    ])
    .all() %}
```

### Eager-Loading Elements Related to Matrix Blocks

The syntax for eager-loading relations from Matrix blocks is a little different than other contexts. You need to prefix your relational field’s handle with the block type’s handle:

```twig
{% set blocks = entry.matrixField
    .with(['blockType:assetsField'])
    .all() %}
```

The reason for this is that Matrix fields can have multiple sub-fields that each share the same handle, as long as they’re in different block types. By requiring the block type handle as part of the eager-loading key, Matrix can be confident that it is eager-loading the right set of elements.

This applies if the Matrix blocks themselves are being eager-loaded, too.

```twig
{% set entries = craft.entries()
    .section('news')
    .with(['matrixField.blockType:assetsField'])
    .all() %}
```

## Eager-Loading Image Transform Indexes

Another _N+1_ problem occurs when looping through a set of assets, and applying image transforms to each of them. For each transform, Craft needs to execute a query to see if the transform already exists.

This problem can be solved with the `withTransforms` asset criteria parameter:

```twig
{% set assets = entry.assetsField
    .withTransforms([
        'heroImage',
        { width: 100, height: 100 }
    ])
    .all() %}
```

Note that each transform definition you want to eager-load can either be a string (the handle of a transform defined in Settings → Assets → Image Transforms) or an object that defines the transform properties.

Using the `withTransforms` param has no effect on how you’d access image transforms further down in the template.

Image transform indexes can be eager-loaded on assets that are also eager-loaded:

```twig
{% set entries = craft.entries()
    .with([
        ['assetsField', {
            withTransforms: ['heroImage']
        }]
    ])
    .all() %}
```
