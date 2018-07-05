# `craft.entries()`

You can access your site’s entries from your templates with `craft.entries()`. It returns a new [element query](../../element-queries.md) of type <api:craft\elements\db\EntryQuery>.

Elements returned by [all()](api:craft\elements\db\ElementQuery::all()), [one()](api:craft\elements\db\ElementQuery::one()), etc., will be of type <api:craft\elements\Entry>.

```twig
{% set entries = craft.entries()
    .section('news')
    .limit(10)
    .all() %}

{% for entry in entries %}
    <article>
        <h1><a href="{{ entry.url }}">{{ entry.title }}</a></h1>
        {{ entry.summary }}
        <a href="{{ entry.url }}">Continue reading</a>
    </article>
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

### `leaves`

Whether the elements must be “leaves” in the structure.


[Reference](api:craft\elements\db\ElementQuery::$leaves)

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

### `withStructure`

Whether element structure data should automatically be left-joined into the query.


[Reference](api:craft\elements\db\ElementQuery::$withStructure)

### `structureId`

The structure ID that should be used to join in the structureelements table.


[Reference](api:craft\elements\db\ElementQuery::$structureId)

### `level`

The element’s level within the structure


[Reference](api:craft\elements\db\ElementQuery::$level)

### `hasDescendants`

Whether the resulting elements must have descendants.


[Reference](api:craft\elements\db\ElementQuery::$hasDescendants)

### `ancestorOf`

The element (or its ID) that results must be an ancestor of.


[Reference](api:craft\elements\db\ElementQuery::$ancestorOf)

### `ancestorDist`

The maximum number of levels that results may be separated from [ancestorOf()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery#method-ancestorof).


[Reference](api:craft\elements\db\ElementQuery::$ancestorDist)

### `descendantOf`

The element (or its ID) that results must be a descendant of.


[Reference](api:craft\elements\db\ElementQuery::$descendantOf)

### `descendantDist`

The maximum number of levels that results may be separated from [descendantOf()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery#method-descendantof).


[Reference](api:craft\elements\db\ElementQuery::$descendantDist)

### `siblingOf`

The element (or its ID) that the results must be a sibling of.


[Reference](api:craft\elements\db\ElementQuery::$siblingOf)

### `prevSiblingOf`

The element (or its ID) that the result must be the previous sibling of.


[Reference](api:craft\elements\db\ElementQuery::$prevSiblingOf)

### `nextSiblingOf`

The element (or its ID) that the result must be the next sibling of.


[Reference](api:craft\elements\db\ElementQuery::$nextSiblingOf)

### `positionedBefore`

The element (or its ID) that the results must be positioned before.


[Reference](api:craft\elements\db\ElementQuery::$positionedBefore)

### `positionedAfter`

The element (or its ID) that the results must be positioned after.


[Reference](api:craft\elements\db\ElementQuery::$positionedAfter)

### `editable`

Whether to only return entries that the user has permission to edit.


[Reference](api:craft\elements\db\EntryQuery::$editable)

### `sectionId`

The section ID(s) that the resulting entries must be in.


[Reference](api:craft\elements\db\EntryQuery::$sectionId)

### `typeId`

The entry type ID(s) that the resulting entries must have.


[Reference](api:craft\elements\db\EntryQuery::$typeId)

### `authorId`

The user ID(s) that the resulting entries’ authors must have.


[Reference](api:craft\elements\db\EntryQuery::$authorId)

### `authorGroupId`

The user group ID(s) that the resulting entries’ authors must be in.


[Reference](api:craft\elements\db\EntryQuery::$authorGroupId)

### `postDate`

The Post Date that the resulting entries must have.


[Reference](api:craft\elements\db\EntryQuery::$postDate)

### `before`

The maximum Post Date that resulting entries can have.


[Reference](api:craft\elements\db\EntryQuery::$before)

### `after`

The minimum Post Date that resulting entries can have.


[Reference](api:craft\elements\db\EntryQuery::$after)

### `expiryDate`

The Expiry Date that the resulting entries must have.


[Reference](api:craft\elements\db\EntryQuery::$expiryDate)


<!-- END PARAMS -->
