# ElementCriteriaModel

ElementCriteriaModel objects are at the center of how Craft fetches elements from the database.

## How it Works

Whenever you fetch elements in your templates, this happens:

1. [An ElementCriteriaModel object is created](#creating-an-elementCriteriaModel), and wired to fetch elements of a single type (entries, assets, etc.).
2. [Parameters are set on the model](#setting-the-parameters), which help filter the elements, set the order they should be returned in, limit how many should be returned, etc.
3. [The elements are fetched from the database.](#fetching-the-elements)

## Creating an ElementCriteriaModel

Craft provides five functions which create and return new ElementCriteriaModel objects. They are:

* [craft.assets](craft.assets.md)
* [craft.categories](craft.categories.md)
* [craft.entries](craft.entries.md)
* [craft.tags](craft.tags.md)
* [craft.users](craft.users.md)

Those aren’t the only ways you’ll interact with ElementCriteriaModel objects, though. They’re actually used all over the place. Here are two examples:

* Calling a relational field (e.g. an [Assets](../assets-fields.md) field) from an element will give you an ElementCriteriaModel with its [relatedTo](../relations.md#the-relatedTo-param) parameter pre-populated.

    ```twig
    {% set assets = entry.myAssetsField %}
    ```

* Calling a category’s [getChildren()](categorymodel.md#getchildren) function will give you an ElementCriteriaModel with its [descendantOf](craft.categories.md#descendantof) and [descendantDist](craft.categories.md#descendantdist) parameters pre-populated.

    ```twig
    {% set children = category.getChildren() %}
    ```


## Setting the Parameters

The main point of ElementCriteriaModel objects is to define the parameters Craft should factor in when it’s searching for elements.

::: tip
The parameters that are available to your ElementCriteriaModel object, and their default values, will vary depending on what type element you are fetching.
:::

There are two ways you can add parameters to your ElementCriteriaModel object:

1. You can chain parameters onto the ElementCriteriaModel as functions:

    ```twig
    {% set entries = craft.entries.section('blog').limit(10) %}
    ```

2. You can pass them all at once to the function that’s creating your ElementCriteriaModel using an object:

    ```twig
    {% set entries = craft.entries({
        section: 'blog',
        limit: 10
    }) %}
    ```

Both of these ways are perfectly valid, and each have their place. The chaining syntax is usually more readable, but the object syntax is more DRY if you have a need to reuse the parameters multiple times in your template:

```twig
{% set params = { section: 'blog' } %}

Total entries: {{ craft.entries(params).total() }}

The last 10 entries:

<ul>
    {% for entry in craft.entries(params).limit(10) %}
        <li><a href="{{ entry.url }}">{{ entry.title }}</a></li>
    {% endfor %}
</ul>
```

Notice how in that example, we only had to define the ‘section’ parameter once, but were able to use it twice – once to get the grand total number of entries that meet the criteria, and a second time to actually grab the latest ten.

That was also an example of how the two syntaxes can be mixed and matched. Notice how on that for-loop line, we are setting the ‘limit’ parameter using the chaining syntax, but applying the ‘section’ parameter by passing `params` as an argument to `craft.entries()`.

### Parameter Value Syntax

ElementCriteriaModel parameters can generally be set to a single value or multiple values, and can optionally be used to *exclude* which elements get returned.

To pass in multiple values, you can either pass them as an array, comma-delimited string, or even as separate arguments if you’re using the chaining method:

    craft.entries.id(1, 2, 3)
    craft.entries.id('1,2,3')
    craft.entries({ id: [1, 2, 3] })
    craft.entries({ id: '1,2,3' })

To exclude entries with a given value, you must pass in a string, prefixed with `not`:

    craft.entries.id('not 1')
    craft.entries({ id: 'not 1' })

Some parameters can also be prefixed with `<=`, `>=`, `<`, and `>`:

    craft.assets.width('>= 100')

Some parameters support partial-match searching, by placing an asterisk (`*`) before/after the value:

    craft.entries.myBodyField('*blue*')


## Fetching the Elements

Once you’ve set all of the parameters on your ElementCriteriaModel, the last step is to have it fetch the elements from the database. You have a few different options on that front, depending on what you need.

### `find()`

This function will fetch all the matching elements and return them as an array. If it can’t find any, an empty array will be returned.

```twig
{% set entries = craft.entries.section('blog').limit(10).find() %}
{# `entries` is an array of EntryModel objects #}
```

You don’t actually need to call `find()` yourself though: It will be called automatically as soon as the ElementCriteriaModel is treated like an array (that is, as soon as you check how many elements there are using the [length](https://twig.symfony.com/doc/filters/length.html) filter, or start looping through the elements with a [for](https://twig.symfony.com/doc/tags/for.html) loop).

```twig
{% set entries = craft.entries.section('blog').limit(10) %}
{# `entries` is still an ElementCriteriaModel here #}

{% for entry in entries %}
    {# Behind the scenes, the {% for %} tag just called our ElementCriteriaModel’s find() method for us.
       `entries` is still an ElementCriteriaModel, though #}
    <li>{{ entry.getLink() }}</li>
{% endfor %}
```

### `first()`

This function will return the first matching element, if it can find one.

```twig
{% set image = entry.myAssetsField.first() %}
{# `image` is either an AssetFileModel or null #}

{% if image %}
    <img src="{{ image.url }}">
{% endif %}
```

::: tip
Since there’s a chance that `first()` won’t return anything if there are no matching elements, you should **always** make sure it actually returned something before you start working with the element it supposedly returned. (The same applies to [last()](#last) and [nth()](#nth).)
:::

### `last()`

This function will return the last matching element, if it can find one.

```twig
{% set lastEntry = craft.entries.section('features').last() %}
{# `lastEntry` is either an EntryModel or null #}

{% if lastEntry %}
    <a href="{{ lastEntry.url }}" title="{{ lastEntry.title }}">&larr; Previous</a>
{% endif %}
```

### `nth( n )`

This function will return the *n*<wbr>th matching element, if it can find one.

```twig
{% set secondEntry = craft.entries.section('features').nth(1) %}
{# `secondEntry` is either an EntryModel or null #}

{% if secondEntry %}
    <a href="{{ secondEntry.url }}" title="{{ secondEntry.title }}">{{ secondEntry.title }}</a>
{% endif %}
```

### `ids()`

This function will fetch the IDs of all the matching elements and return them as an array. If it can’t find any, an empty array will be returned.

```twig
{% set allTagIds = craft.tags.group('blogTags').limit(null).ids() %}
{# `allTagIds` is an array of tag IDs #}
```

### `total()`

This function will find the total number of matching elements, without actually fetching them.

```twig
{% set totalBlogPosts = craft.entries.section('blog').total() %}
```

::: tip
The `offset` and `limit` parameters (which are shared among all element types) will be ignored when you call `total()`, so you don’t need to worry about overriding the default values.
:::

::: tip
If you are going to be looping through elements using the **exact** same parameters later on in the same template, use the [length](https://twig.symfony.com/doc/filters/length.html) filter rather than `total()`. You’ll save Craft from running an unnecessary database query.

```twig
{% if entry.myAssetsField|length %}
    <h3>Image Gallery</h3>
    <ul>
        {% for image in entry.myAssetsField %}
            <img src="{{ image.getUrl('thumb') }}">
        {% endfor %}
    </ul>
{% endif %}
```
:::
