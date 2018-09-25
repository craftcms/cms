# EntryModel

Whenever you’re dealing with an [entry](../sections-and-entries.md) in your template, you’re actually working with an EntryModel object.

## Simple Output

Outputting an EntryModel object without attaching a property or method will return the entry’s Title:

```twig
<h1>{{ entry }}</h1>
```

## Properties

EntryModel objects have the following properties:

### `ancestors`

Alias of [getAncestors()](#getancestors).

### `author`

Alias of [getAuthor()](#getauthor).

### `authorId`

The entry’s author’s ID.

### `children`

Alias of [getChildren()](#getchildren).

### `cpEditUrl`

Alias of [getCpEditUrl()](#getcpediturl).

### `dateCreated`

A [DateTime](datetime.md) object of the date the entry was created.

### `dateUpdated`

A [DateTime](datetime.md) object of the date the entry was last updated.

### `descendants`

Alias of [getDescendants()](#getdescendants).

### `enabled`

Whether the entry is enabled.

### `expiryDate`

A [DateTime](datetime.md) object of the entry’s Expiration Date, if any.

### `hasDescendants`

Whether the entry has any descendants.

::: tip
`hasDescendants` will return `true` even if all of the descendants are disabled. If you want to determine if the entry has any enabled descendants, you can do this instead:

```twig
{% set hasDescendants = entry.getDescendants().total() != 0 %}
```
:::

### `id`

The entry’s ID.

### `level`

The entry’s level (if it’s in a Structure section).

### `link`

Alias of [getLink()](#getlink).

### `locale`

The locale the entry was fetched in.

### `next`

Alias of [getNext()](#getnext).

### `nextSibling`

Alias of [getNextSibling()](#getnextsibling).

### `parent`

Alias of [getParent()](#getparent).

### `postDate`

A [DateTime](datetime.md) object of the entry’s Post Date, if any.

### `prev`

Alias of [getPrev()](#getprev).

### `prevSibling`

Alias of [getPrevSibling()](#getprevsibling).

### `section`

Alias of [getSection()](#getsection).

### `sectionId`

The ID of the entry’s [section](../sections-and-entries.md#sections).

### `siblings`

Alias of [getSiblings()](#getsiblings).

### `slug`

The entry’s slug.

### `status`

The entry’s status (‘live’, ‘pending’, ‘expired’, or ‘disabled’).

### `title`

The entry’s title.

### `type`

Alias of [getType()](#gettype).

### `uri`

The entry’s URI.

### `url`

Alias of [getUrl()](#geturl)


## Methods

EntryModel objects have the following methods:

### `getAncestors( distance )`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) prepped to return the entry’s ancestors (if it lives in a Structure section). You can limit it to only return ancestors that are up to a certain distance away by passing the distance as an argument.

### `getAuthor()`

Returns a [UserModel](usermodel.md) object representing the entry’s author, if there is one.

### `getChildren()`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) prepped to return the entry’s children (if it lives in a Structure section). (This is an alias for `getDescendants(1)`)

::: tip
If `getChildren()` is called for an entry in a Channel section, it will return any entries that are related by the entry. This behavior is deprecated, though, and removed in Craft 3.
:::

### `getCpEditUrl()`

Returns the URL to the entry’s edit page within the Control Panel.

### `getDescendants( distance )`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) prepped to return the entry’s descendants (if it lives in a Structure section). You can limit it to only return descendants that are up to a certain distance away by passing the distance as an argument.

### `getLink()`

Returns an `<a>` tag, set to the entry’s URL, and using the entry’s title as the text.

### `getNext( params )`

Returns the next entry that should show up in a list based on the parameters entered. This function accepts either a `craft.entries` variable (sans output function), or a parameter array. If you use this within a `craft.entries` loop, it will return the next entry in that loop by default.

### `getNextSibling()`

Returns a Structured entry’s next sibling, if there is one.

::: tip
`getNextSibling()` will return the next sibling whether or not it’s enabled. If you want to get the closest enabled sibling, you can do this instead:

```twig
{% set nextSibling = craft.entries.positionedAfter(entry).order('lft asc').first() %}
```
:::

### `getParent()`

Returns a Structured entry’s parent, if it’s not a top-level entry.

::: tip
`getParent()` will return the parent whether or not it’s enabled. If you want to get the closest enabled ancestor, you can do this instead:

```twig
{% set parent = craft.entries.ancestorOf(entry).order('lft desc').first() %}
```
:::

### `getPrev( params )`

Returns the previous entry that would have shown up in a list based on the parameters entered. This function accepts either a `craft.entries` variable (sans output function), or a parameter array. If you use this within a `craft.entries` loop, it will return the previous entry in that loop by default.

### `getPrevSibling()`

Returns a Structured entry’s previous sibling, if there is one.

::: tip
`getPrevSibling()` will return the previous sibling whether or not it’s enabled. If you want to get the closest enabled sibling, you can do this instead:

```twig
{% set prevSibling = craft.entries.positionedBefore(entry).order('lft desc').first() %}
```
:::

### `getSection()`

Returns a [SectionModel](sectionmodel.md) object representing the entry’s section.

### `getSiblings()`

Returns an [ElementCriteriaModel](elementcriteriamodel.md) object prepped to return the entry’s siblings (if it lives in a Structure section).

### `getType()`

Returns an [EntryTypeModel](entrytypemodel.md) object representing the entry’s type.

### `getUrl()`

Returns the entry’s URL, if any.

### `hasDescendants()`

Returns whether the entry has any descendants (if it lives in a Structure section).

### `isAncestorOf( entry )`

Returns whether the entry is an ancestor of another entry.

```twig
{% nav page in craft.entries.section('pages') %}
    {% set expanded = entry is defined and page.isAncestorOf(entry) %}
    <li{% if expanded %} class="expanded"{% endif %}>
        {{ page.getLink() }}
        {% ifchildren %}
            <ul>
                {% children %}
            </ul>
        {% endifchildren %}
    </li>
{% endnav %}
```

### `isChildOf( entry )`

Returns whether the entry is a direct child of another entry.

### `isDescendantOf( entry )`

Returns whether the entry is a descendant of another entry.

### `isNextSiblingOf( entry )`

Returns whether the entry is the next sibling of another entry.

### `isParentOf( entry )`

Returns whether the entry is a direct parent of another entry.

### `isPrevSiblingOf( entry )`

Returns whether the entry is the previous sibling of another entry.

### `isSiblingOf( entry )`

Returns whether the entry is a sibling of another entry.

Here’s an example of `getNext()` and `getPrev()` in action:

```twig
{% set params = {
    section: 'cocktails',
    order:   'title'
} %}

{% set prevCocktail = entry.getPrev(params) %}
{% set nextCocktail = entry.getNext(params) %}

{% if prevCocktail %}
    <p>Previous: <a href="{{ prevCocktail.url }}">{{ prevCocktail.title }}</a></p>
{% endif %}

{% if nextCocktail %}
    <p>Next: <a href="{{ nextCocktail.url }}">{{ nextCocktail.title }}</a></p>
{% endif %}
```
