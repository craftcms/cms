# `craft.categories`

{intro} You can access your site’s [categories](../categories.md) from your templates via `craft.categories`. It returns an [ElementCriteriaModel](elementcriteriamodel.md) object.

```twig
<ul>
    {% nav category in craft.categories.group('newsCategories') %}
        <li>
            <a href="{{ category.url }}">{{ category.title }}</a>
            {% ifchildren %}
                <ul>
                    {% children %}
                </ul>
            {% endifchildren %}
        </li>
    {% endnav %}
</ul>
```

## Parameters

`craft.categories` supports the following parameters:

### `ancestorOf`

Only fetch categories that are an ancestor of a given category. Accepts a [CategoryModel](categorymodel.md) object.

### `ancestorDist`

Only fetch categories that are a given number of levels above the category specified by the `ancestorOf` param.

### `level`

Only fetch categories located at a certain level.

### `descendantOf`

Only fetch categories that are a descendant of a given category. Accepts a [CategoryModel](categorymodel.md) object.

### `descendantDist`

Only fetch categories that are a given number of levels below the category specified by the `descendantOf` param.

### `fixedOrder`

If set to `true`, categories will be returned in the same order as the IDs entered in the [`id`](#id) param.

### `group`

Only fetch categories that belong to a given category group(s), referenced by its handle.

### `groupId`

Only fetch categories that belong to a given category group(s), referenced by its ID.

### `id`

Only fetch the category with the given ID.

### `indexBy`

Indexes the results by a given property. Possible values include `'id'` and `'title'`.

### `limit`

Limits the results to *X* categories.

### `locale`

The locale the categories should be returned in. (Defaults to the current site locale.)

### `nextSiblingOf`

Only fetch the category which is the next sibling of the given category. Accepts either a [CategoryModel](categorymodel.md) object or a category’s ID.

### `offset`

Skips the first *X* categories.

For example, if you set `offset(1)`, the would-be second category returned becomes the first.

### `order`

The order the categories should be returned in. Possible values include `'title'`, `'id'`, `'groupId'`, `'slug'`, `'uri'`, `'dateCreated'`, and `'dateUpdated'`, as well as any textual custom field handles. If you want the entries to be sorted in descending order, add “`desc`” after the property name (ex: `'slug desc'`). The default value is `'postDate desc'`.

### `positionedAfter`

Only fetch categories which are positioned after the given category. Accepts either a [CategoryModel](categorymodel.md) object or a category’s ID.

### `positionedBefore`

Only fetch categories which are positioned before the given category. Accepts either a [CategoryModel](categorymodel.md) object or a category’s ID.

### `prevSiblingOf`

Only fetch the category which is the previous sibling of the given category. Accepts either a [CategoryModel](categorymodel.md) object or a category’s ID.

### `relatedTo`

Only fetch categories that are related to certain other elements. (See [relations](../relations.md) for the syntax options.)

### `search`

Only fetch entries that match a given search query. (See [searching](../searching.md) for the syntax and available search attributes.)

### `siblingOf`

Only fetch categories which are siblings of the given category. Accepts either a [CategoryModel](categorymodel.md) object or a category’s ID.

### `slug`

Only fetch the category with the given slug.

### `title`

Only fetch categories with the given title.

### `uri`

Only fetch the category with the given URI.