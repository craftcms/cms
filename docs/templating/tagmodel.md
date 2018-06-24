# TagModel

Whenever you’re dealing with a [tag](../tags.md) in your template, you’re actually working with a TagModel object.

## Simple Output

Outputting a TagModel object without attaching a property or method will return the tag’s title:

```twig
<li>{{ tag }}</li>
```

## Properties

TagModel objects have the following properties:

### `group`

Alias of [getGroup()](#getgroup).

### `groupId`

The tag’s group ID.

### `id`

The tag’s ID.

### `locale`

The locale the tag was fetched in.

### `next`

Alias of [getNext()](#getnext).

### `prev`

Alias of [getPrev()](#getprev).

### `slug`

The tag’s slug.

### `title`

The tag’s title.


## Methods

TagModel objects have the following methods:

### `getGroup()`

Returns a [TagGroupModel](taggroupmodel.md) object representing the tag group that the tag belongs to.

### `getNext( params )`

Returns the next tag that should show up in a list based on the parameters entered. This function accepts either a `craft.tags` variable (sans output function), or a parameter array.

### `getPrev( params )`

Returns the previous tag that would have shown up in a list based on the parameters entered. This function accepts either a `craft.tags` variable (sans output function), or a parameter array.

Here’s an example of the `getPrev()` and `getNext()` methods in action:

```twig
{% set params = {
    setId: 3
} %}

{% set prevTag = entry.getPrev(params) %}
{% set nextTag = entry.getNext(params) %}

{% if prevTag %}
    <p>Previous: <a href="/tags/{{ prevTag | url_encode }}">{{ prevTag }}</a></p>
{% endif %}

{% if nextTag %}
    <p>Next: <a href="/tags/{{ nextTag | url_encode }}">{{ nextTag }}</a></p>
{% endif %}
```
