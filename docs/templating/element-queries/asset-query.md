# `craft.assets()`

You can access your site’s assets from your templates with `craft.assets()`. It returns a new [element query](../../element-queries.md) of type <api:craft\elements\db\AssetQuery>.

Elements returned by [all()](api:craft\elements\db\ElementQuery::all()), [one()](api:craft\elements\db\ElementQuery::one()), etc., will be of type <api:craft\elements\Asset>.

```twig
{% set images = craft.assets()
    .kind('image')
    .all() %}

<ul>
    {% for image in images %}
        <li><img src="{{ image.getUrl('thumb') }}" alt="{{ image.title }}"></li>
    {% endfor %}
</ul>
```

## Parameters

Asset queries support the following parameters:

<!-- BEGIN PARAMS -->

### `inReverse`

Whether the results should be queried in reverse.


[Reference](api:craft\elements\db\ElementQuery::$inReverse)

### `asArray`

Whether to return each element as an array. If false (default), an object
of [$elementType](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery#property-$elementtype) will be created to represent each element.


[Reference](api:craft\elements\db\ElementQuery::$asArray)

### `id`

The element ID(s). Prefix IDs with "not " to exclude them.


[Reference](api:craft\elements\db\ElementQuery::$id)

### `uid`

The element UID(s). Prefix UIDs with "not " to exclude them.


[Reference](api:craft\elements\db\ElementQuery::$uid)

### `fixedOrder`

Whether results should be returned in the order specified by [id()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery#method-id).


[Reference](api:craft\elements\db\ElementQuery::$fixedOrder)

### `status`

The status(es) that the resulting elements must have.


[Reference](api:craft\elements\db\ElementQuery::$status)

### `archived`

Whether to return only archived elements.


[Reference](api:craft\elements\db\ElementQuery::$archived)

### `dateCreated`

When the resulting elements must have been created.


[Reference](api:craft\elements\db\ElementQuery::$dateCreated)

### `dateUpdated`

When the resulting elements must have been last updated.


[Reference](api:craft\elements\db\ElementQuery::$dateUpdated)

### `siteId`

The site ID that the elements should be returned in.


[Reference](api:craft\elements\db\ElementQuery::$siteId)

### `enabledForSite`

Whether the elements must be enabled for the chosen site.


[Reference](api:craft\elements\db\ElementQuery::$enabledForSite)

### `relatedTo`

The element relation criteria.


[Reference](api:craft\elements\db\ElementQuery::$relatedTo)

### `title`

The title that resulting elements must have.


[Reference](api:craft\elements\db\ElementQuery::$title)

### `slug`

The slug that resulting elements must have.


[Reference](api:craft\elements\db\ElementQuery::$slug)

### `uri`

The URI that the resulting element must have.


[Reference](api:craft\elements\db\ElementQuery::$uri)

### `search`

The search term to filter the resulting elements by.


[Reference](api:craft\elements\db\ElementQuery::$search)

### `ref`

The reference code(s) used to identify the element(s).

This property is set when accessing elements via their reference tags, e.g. {entry:section/slug}.


[Reference](api:craft\elements\db\ElementQuery::$ref)

### `with`

The eager-loading declaration


[Reference](api:craft\elements\db\ElementQuery::$with)

### `volumeId`

The volume ID(s) that the resulting assets must be in.


[Reference](api:craft\elements\db\AssetQuery::$volumeId)

### `folderId`

The asset folder ID(s) that the resulting assets must be in.


[Reference](api:craft\elements\db\AssetQuery::$folderId)

### `filename`

The filename(s) that the resulting assets must have.


[Reference](api:craft\elements\db\AssetQuery::$filename)

### `kind`

The file kind(s) that the resulting assets must be.


[Reference](api:craft\elements\db\AssetQuery::$kind)

### `width`

The width (in pixels) that the resulting assets must have.


[Reference](api:craft\elements\db\AssetQuery::$width)

### `height`

The height (in pixels) that the resulting assets must have.


[Reference](api:craft\elements\db\AssetQuery::$height)

### `size`

The size (in bytes) that the resulting assets must have.


[Reference](api:craft\elements\db\AssetQuery::$size)

### `dateModified`

The Date Modified that the resulting assets must have.


[Reference](api:craft\elements\db\AssetQuery::$dateModified)

### `includeSubfolders`

Whether the query should search the subfolders of [folderId()](https://docs.craftcms.com/api/v3/craft-elements-db-assetquery#method-folderid).


[Reference](api:craft\elements\db\AssetQuery::$includeSubfolders)

### `withTransforms`

The asset transform indexes that should be eager-loaded, if they exist


[Reference](api:craft\elements\db\AssetQuery::$withTransforms)


<!-- END PARAMS -->
