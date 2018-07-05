# `craft.matrixBlocks()`

You can access your site’s Matrix blocks from your templates with `craft.matrixBlocks()`. It returns a new [element query](../../element-queries.md) of type <api:craft\elements\db\MatrixBlockQuery>.

Elements returned by [all()](api:craft\elements\db\ElementQuery::all()), [one()](api:craft\elements\db\ElementQuery::one()), etc., will be of type <api:craft\elements\MatrixBlock>.

::: warning
In order for the returned Matrix block(s) to be populated with their custom field content, you will need to either set the [fieldId](#fieldid) or [id](#id) parameter.
:::

```twig
{% set blocks = craft.matrixBlocks()
    .ownerId(100)
    .fieldId(10)
    .type('text')
    .all() %}

{% for block in blocks %}
    <p>{{ block.text }}</p>
{% endfor %}
```

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

### `fieldId`

The field ID(s) that the resulting Matrix blocks must belong to.


[Reference](api:craft\elements\db\MatrixBlockQuery::$fieldId)

### `ownerId`

The owner element ID(s) that the resulting Matrix blocks must belong to.


[Reference](api:craft\elements\db\MatrixBlockQuery::$ownerId)

### `ownerSiteId`

The site ID that the resulting Matrix blocks must have been defined in, or ':empty:' to find blocks without an owner site ID.


[Reference](api:craft\elements\db\MatrixBlockQuery::$ownerSiteId)

### `typeId`

The block type ID(s) that the resulting Matrix blocks must have.


[Reference](api:craft\elements\db\MatrixBlockQuery::$typeId)


<!-- END PARAMS -->
