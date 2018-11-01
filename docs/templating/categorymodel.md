# CategoryModel

Whenever you’re dealing with a [category](../categories.md) in your template, you’re actually working with a CategoryModel object.

## Simple Output

Outputting a CategoryModel object without attaching a property or method will return the category’s title:

```twig
<h1>{{ category }}</h1>
```


## Properties

CategoryModel objects have the following properties:

### `ancestors`

Alias of [getAncestors()](#getancestors).

### `children`

Alias of [getChildren()](#getchildren).

### `cpEditUrl`

Alias of [getCpEditUrl()](#getcpediturl).

### `dateCreated`

A [DateTime](datetime.md) object of the date the category was created.

### `dateUpdated`

A [DateTime](datetime.md) object of the date the category was last updated.

### `descendants`

Alias of [getDescendants()](#getdescendants).

### `enabled`

Whether the category is enabled.

### `group`

Alias of [getGroup()](#getgroup).

### `hasDescendants`

Whether the category has any descendants.

::: tip
`hasDescendants` will return `true` even if all of the descendants are disabled. If you want to determine if the category has any enabled descendants, you can do this instead:

```twig
{% set hasDescendants = category.getDescendants().total() != 0 %}
```
:::

### `id`

The category’s ID.

### `level`

The category’s level.

### `link`

Alias of [getLink()](#getlink).

### `locale`

The locale the category was fetched in.

### `next`

Alias of [getNext()](#getnext).

### `nextSibling`

Alias of [getNextSibling()](#getnextsibling).

### `parent`

Alias of [getParent()](#getparent).

### `prev`

Alias of [getPrev()](#getprev).

### `prevSibling`

Alias of [getPrevSibling()](#getprevsibling).

### `siblings`

Alias of [getSiblings()](#getsiblings).

### `slug`

The category’s slug.

### `title`

The category’s title.

### `uri`

The category’s URI.

### `url`

Alias of [getUrl()](#geturl)


## Methods

CategoryModel objects have the following methods:

### `getAncestors( distance )`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) prepped to return the category’s ancestors (if it lives in a Structure section). You can limit it to only return ancestors that are up to a certain distance away by passing the distance as an argument.

### `getChildren()`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) prepped to return the category’s children. (This is an alias for `getDescendants(1)`)

### `getDescendants( distance )`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) prepped to return the category’s descendants. You can limit it to only return descendants that are up to a certain distance away by passing the distance as an argument.

### `getGroup()`

Returns a [CategoryGroupModel](categorygroupmodel.md) object representing the category’s group.

### `getLink()`

Returns an `<a>` tag, set to the category’s URL, and using the category’s title as the text.

### `getNext( params )`

Returns the next category that should show up in a list based on the parameters entered. This function accepts either a `craft.categories` variable (sans output function), or a parameter array. If you use this within a `craft.categories` loop, it will return the next category in that loop by default.

### `getNextSibling()`

Returns the category’s next sibling, if there is one.

::: tip
`getNextSibling()` will return the next sibling whether or not it’s enabled. If you want to get the closest enabled sibling, you can do this instead:

```twig
{% set nextSibling = craft.categories.positionedAfter(category).order('lft asc').first() %}
```
:::

### `getParent()`

Returns the category’s parent, if it’s not a top-level category.

::: tip
`getParent()` will return the parent whether or not it’s enabled. If you want to get the closest enabled ancestor, you can do this instead:

```twig
{% set parent = craft.categories.ancestorOf(category).order('lft desc').first() %}
```
:::

### `getPrev( params )`

Returns the previous category that would have shown up in a list based on the parameters entered. This function accepts either a `craft.categories` variable (sans output function), or a parameter array. If you use this within a `craft.categories` loop, it will return the previous category in that loop by default.

### `getPrevSibling()`

Returns a category’s previous sibling, if there is one.

::: tip
`getPrevSibling()` will return the previous sibling whether or not it’s enabled. If you want to get the closest enabled sibling, you can do this instead:

```twig
{% set prevSibling = craft.categories.positionedBefore(category).order('lft desc').first() %}`
```
:::

### `getSiblings()`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) prepped to return the category’s siblings (if it lives in a Structure section).

### `getUrl()`

Returns the category’s URL, if any.

### `hasDescendants()`

Returns whether the category has any descendants.

### `isAncestorOf( category )`

Returns whether the category is an ancestor of another category.

```twig
{% nav page in craft.categories.group('whiskey') %}
    {% set expanded = category is defined and item.isAncestorOf(category) %}
    <li{% if expanded %} class="expanded"{% endif %}>
        {{ item.getLink() }}
        {% ifchildren %}
            <ul>
                {% children %}
            </ul>
        {% endifchildren %}
    </li>
{% endnav %}
```

### `isChildOf( category )`

Returns whether the category is a direct child of another category.

### `isDescendantOf( category )`

Returns whether the category is a descendant of another category.

### `isNextSiblingOf( category )`

Returns whether the category is the next sibling of another category.

### `isParentOf( category )`

Returns whether the category is a direct parent of another category.

### `isPrevSiblingOf( category )`

Returns whether the category is the previous sibling of another category.

### `isSiblingOf( category )`

Returns whether the category is a sibling of another category.

Here’s an example of `getNext()` and `getPrev()` in action:

```twig
{% set params = {
    section: 'cocktails',
    order:   'title'
} %}

{% set prevWhiskey = category.getPrev(params) %}
{% set nextWhiskey = category.getNext(params) %}

{% if prevWhiskey %}
    <p>Previous: <a href="{{ prevWhiskey.url }}">{{ prevWhiskey.title }}</a></p>
{% endif %}

{% if nextWhiskey %}
    <p>Next: <a href="{{ nextWhiskey.url }}">{{ nexWhiskey.title }}</a></p>
{% endif %}
```
