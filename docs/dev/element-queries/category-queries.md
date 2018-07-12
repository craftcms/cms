# Category Queries

Category queries are a type of [element query](README.md) used to fetch your project’s categories.

They are implemented by <api:craft\elements\db\CategoryQuery>, and the elements returned by them will be of type <api:craft\elements\Category>.

## Creating Category Queries

You can create a new category query from Twig by calling `craft.categories()`, or from PHP by calling <api:craft\elements\Category::find()>.

::: code
```twig
{% set categories = craft.categories()
    .group('newsCategories')
    .all() %}

<ul>
    {% nav category in categories %}
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
```php
/** @var \craft\elements\Category[] $categories */
$categories = \craft\elements\Category::find()
    ->group('newsCategories')
    ->all();
```
:::

## Parameters

Category queries support the following parameters:

<!-- BEGIN PARAMS -->

### `ancestorDist`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$ancestorDist](api:craft\elements\db\ElementQuery::$ancestorDist)

Settable by

:   [ancestorDist()](api:craft\elements\db\ElementQuery::ancestorDist())



The maximum number of levels that results may be separated from [ancestorOf()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-ancestorof).


### `ancestorOf`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$ancestorOf](api:craft\elements\db\ElementQuery::$ancestorOf)

Settable by

:   [ancestorOf()](api:craft\elements\db\ElementQuery::ancestorOf())



The element (or its ID) that results must be an ancestor of.


### `archived`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$archived](api:craft\elements\db\ElementQuery::$archived)

Settable by

:   [archived()](api:craft\elements\db\ElementQuery::archived())



Whether to return only archived elements.


### `asArray`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$asArray](api:craft\elements\db\ElementQuery::$asArray)

Settable by

:   [asArray()](api:craft\elements\db\ElementQuery::asArray())



Whether to return each element as an array. If false (default), an object
of [$elementType](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#property-$elementtype) will be created to represent each element.


### `dateCreated`

Allowed types

:   `mixed`

Defined by

:   [ElementQuery::$dateCreated](api:craft\elements\db\ElementQuery::$dateCreated)

Settable by

:   [dateCreated()](api:craft\elements\db\ElementQuery::dateCreated())



When the resulting elements must have been created.


### `dateUpdated`

Allowed types

:   `mixed`

Defined by

:   [ElementQuery::$dateUpdated](api:craft\elements\db\ElementQuery::$dateUpdated)

Settable by

:   [dateUpdated()](api:craft\elements\db\ElementQuery::dateUpdated())



When the resulting elements must have been last updated.


### `descendantDist`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$descendantDist](api:craft\elements\db\ElementQuery::$descendantDist)

Settable by

:   [descendantDist()](api:craft\elements\db\ElementQuery::descendantDist())



The maximum number of levels that results may be separated from [descendantOf()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-descendantof).


### `descendantOf`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$descendantOf](api:craft\elements\db\ElementQuery::$descendantOf)

Settable by

:   [descendantOf()](api:craft\elements\db\ElementQuery::descendantOf())



The element (or its ID) that results must be a descendant of.


### `editable`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [CategoryQuery::$editable](api:craft\elements\db\CategoryQuery::$editable)

Settable by

:   [editable()](api:craft\elements\db\CategoryQuery::editable())



Whether to only return categories that the user has permission to edit.


### `enabledForSite`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$enabledForSite](api:craft\elements\db\ElementQuery::$enabledForSite)

Settable by

:   [enabledForSite()](api:craft\elements\db\ElementQuery::enabledForSite())



Whether the elements must be enabled for the chosen site.


### `fixedOrder`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$fixedOrder](api:craft\elements\db\ElementQuery::$fixedOrder)

Settable by

:   [fixedOrder()](api:craft\elements\db\ElementQuery::fixedOrder())



Whether results should be returned in the order specified by [id()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-id).


### `groupId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [null](http://www.php.net/language.types.null)

Defined by

:   [CategoryQuery::$groupId](api:craft\elements\db\CategoryQuery::$groupId)

Settable by

:   [group()](api:craft\elements\db\CategoryQuery::group()), [groupId()](api:craft\elements\db\CategoryQuery::groupId())



The category group ID(s) that the resulting categories must be in.


::: code
```twig
{# fetch categories in the Topics group #}
{% set categories = craft.categories()
    .group('topics')
    .all() %}
```

```php
// fetch categories in the Topics group
$categories = \craft\elements\Category::find()
    ->group('topics')
    ->all();
```
:::
### `hasDescendants`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$hasDescendants](api:craft\elements\db\ElementQuery::$hasDescendants)

Settable by

:   [hasDescendants()](api:craft\elements\db\ElementQuery::hasDescendants())



Whether the resulting elements must have descendants.


### `id`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [false](http://www.php.net/language.types.boolean), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$id](api:craft\elements\db\ElementQuery::$id)

Settable by

:   [id()](api:craft\elements\db\ElementQuery::id())



The element ID(s). Prefix IDs with `'not '` to exclude them.


### `inReverse`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$inReverse](api:craft\elements\db\ElementQuery::$inReverse)

Settable by

:   [inReverse()](api:craft\elements\db\ElementQuery::inReverse())



Whether the results should be queried in reverse.


### `leaves`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$leaves](api:craft\elements\db\ElementQuery::$leaves)

Settable by

:   [leaves()](api:craft\elements\db\ElementQuery::leaves())



Whether the elements must be “leaves” in the structure.


### `level`

Allowed types

:   `mixed`

Defined by

:   [ElementQuery::$level](api:craft\elements\db\ElementQuery::$level)

Settable by

:   [level()](api:craft\elements\db\ElementQuery::level())



The element’s level within the structure


### `nextSiblingOf`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$nextSiblingOf](api:craft\elements\db\ElementQuery::$nextSiblingOf)

Settable by

:   [nextSiblingOf()](api:craft\elements\db\ElementQuery::nextSiblingOf())



The element (or its ID) that the result must be the next sibling of.


### `positionedAfter`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$positionedAfter](api:craft\elements\db\ElementQuery::$positionedAfter)

Settable by

:   [positionedAfter()](api:craft\elements\db\ElementQuery::positionedAfter())



The element (or its ID) that the results must be positioned after.


### `positionedBefore`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$positionedBefore](api:craft\elements\db\ElementQuery::$positionedBefore)

Settable by

:   [positionedBefore()](api:craft\elements\db\ElementQuery::positionedBefore())



The element (or its ID) that the results must be positioned before.


### `prevSiblingOf`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$prevSiblingOf](api:craft\elements\db\ElementQuery::$prevSiblingOf)

Settable by

:   [prevSiblingOf()](api:craft\elements\db\ElementQuery::prevSiblingOf())



The element (or its ID) that the result must be the previous sibling of.


### `ref`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$ref](api:craft\elements\db\ElementQuery::$ref)

Settable by

:   [ref()](api:craft\elements\db\ElementQuery::ref())



The reference code(s) used to identify the element(s).

This property is set when accessing elements via their reference tags, e.g. `{entry:section/slug}`.


### `relatedTo`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [array](http://www.php.net/language.types.array), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$relatedTo](api:craft\elements\db\ElementQuery::$relatedTo)

Settable by

:   [relatedTo()](api:craft\elements\db\ElementQuery::relatedTo())



The element relation criteria.

See [Relations](https://docs.craftcms.com/v3/relations.html) for supported syntax options.


### `search`

Allowed types

:   [string](http://www.php.net/language.types.string), [array](http://www.php.net/language.types.array), [craft\search\SearchQuery](<api:craft\search\SearchQuery>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$search](api:craft\elements\db\ElementQuery::$search)

Settable by

:   [search()](api:craft\elements\db\ElementQuery::search())



The search term to filter the resulting elements by.

See [Searching](https://docs.craftcms.com/v3/searching.html) for supported syntax options.


### `siblingOf`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](<api:craft\base\ElementInterface>), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$siblingOf](api:craft\elements\db\ElementQuery::$siblingOf)

Settable by

:   [siblingOf()](api:craft\elements\db\ElementQuery::siblingOf())



The element (or its ID) that the results must be a sibling of.


### `siteId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$siteId](api:craft\elements\db\ElementQuery::$siteId)

Settable by

:   [site()](api:craft\elements\db\ElementQuery::site()), [siteId()](api:craft\elements\db\ElementQuery::siteId())



The site ID that the elements should be returned in.


### `slug`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$slug](api:craft\elements\db\ElementQuery::$slug)

Settable by

:   [slug()](api:craft\elements\db\ElementQuery::slug())



The slug that resulting elements must have.


### `status`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$status](api:craft\elements\db\ElementQuery::$status)

Settable by

:   [status()](api:craft\elements\db\ElementQuery::status())



The status(es) that the resulting elements must have.


### `structureId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [false](http://www.php.net/language.types.boolean), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$structureId](api:craft\elements\db\ElementQuery::$structureId)

Settable by

:   [structureId()](api:craft\elements\db\ElementQuery::structureId())



The structure ID that should be used to join in the structureelements table.


### `title`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$title](api:craft\elements\db\ElementQuery::$title)

Settable by

:   [title()](api:craft\elements\db\ElementQuery::title())



The title that resulting elements must have.


### `uid`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$uid](api:craft\elements\db\ElementQuery::$uid)

Settable by

:   [uid()](api:craft\elements\db\ElementQuery::uid())



The element UID(s). Prefix UIDs with `'not '` to exclude them.


### `uri`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$uri](api:craft\elements\db\ElementQuery::$uri)

Settable by

:   [uri()](api:craft\elements\db\ElementQuery::uri())



The URI that the resulting element must have.


### `with`

Allowed types

:   [string](http://www.php.net/language.types.string), [array](http://www.php.net/language.types.array), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$with](api:craft\elements\db\ElementQuery::$with)

Settable by

:   [with()](api:craft\elements\db\ElementQuery::with()), [andWith()](api:craft\elements\db\ElementQuery::andWith())



The eager-loading declaration.

See [Eager-Loading Elements](https://docs.craftcms.com/v3/eager-loading-elements.html) for supported syntax options.


### `withStructure`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$withStructure](api:craft\elements\db\ElementQuery::$withStructure)

Settable by

:   [withStructure()](api:craft\elements\db\ElementQuery::withStructure())



Whether element structure data should automatically be left-joined into the query.



<!-- END PARAMS -->
