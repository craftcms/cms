# `craft.assets`

{intro} You can access your site’s [assets](../assets.md) from your templates via `craft.assets`. It returns an [ElementCriteriaModel](elementcriteriamodel.md) object.

```twig
{% for image in craft.assets.kind('image') %}
    <li><img src="{{ image.getUrl('thumb') }}" alt="{{ image.title }}"></li>
{% endfor %}
```

## Parameters

`craft.assets` supports the following parameters:

### `filename`

Only fetch the asset(s) with the given filename.

### `fixedOrder`

If set to `true`, assets will be returned in the same order as the IDs entered in the [`id`](#id) param.

### `folderId`

Only fetch assets that live within a given folder(s), referenced by its ID.

### `height`

Only fetch assets of a given height(s) in pixels.

### `id`

Only fetch the asset with the given ID(s).

### `indexBy`

Indexes the results by a given property. Possible values include `'id'` and `'title'`.

### `kind`

Only fetch assets of the given file kind.

The supported values are:

* access
* audio
* compressed
* excel
* flash
* html
* illustrator
* image
* pdf
* photoshop
* php
* powerpoint
* text
* video
* word

### `limit`

Limits the results to *X* assets.

### `locale`

The locale the assets should be returned in. (Defaults to the current site locale.)

### `offset`

Skips the first *X* assets. For example, if you set `offset(1)`, the would-be second asset returned becomes the first.

### `order`

The order the assets should be returned in. Possible values include `'title'`, `'id'`, `'sourceId'`, `'folderId'`, `'filename'`, `'kind'`, `'width'`, `'height'`, `'size'`, `'dateCreated'`, and `'dateUpdated'`, as well as any textual custom field handles. If you want the entries to be sorted in descending order, add “`desc`” after the property name (ex: `'size desc'`). The default value is `'title asc'`.

### `relatedTo`

Only fetch assets that are related to certain other elements. (See [relations](../relations.md) for the syntax options.)

### `search`

Only fetch assets that match a given search query. (See [searching](../searching.md) for the syntax and available search attributes.)

### `size`

Only fetch assets with a given size(s) in bytes.

### `title`

Only fetch assets with the given title.

### `source`

Only fetch assets that belong to a given asset source(s), referenced by its handle.

### `sourceId`

Only fetch assets that belong to a given asset source(s), referenced by its ID.

### `width`

Only fetch assets of a given width(s) in pixels.