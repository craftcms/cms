# Entry Queries

Entry queries are a type of [element query](README.md) used to fetch your project’s entries.

They are implemented by <api:craft\elements\db\EntryQuery>, and the elements returned by them will be of type <api:craft\elements\Entry>.

## Creating Entry Queries

You can create a new entry query from Twig by calling `craft.entries()`, or from PHP by calling <api:craft\elements\Entry::find()>.

::: code
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
```php
/** @var \craft\elements\Entry[] $entries */
$entries = \craft\elements\Entry::find()
    ->section('news')
    ->limit(10)
    ->all();
```
:::

## Parameters

Entry queries support the following parameters:

<!-- BEGIN PARAMS -->

### `after`

Allowed types

:   [string](http://www.php.net/language.types.string), [array](http://www.php.net/language.types.array), [DateTime](http://www.php.net/class.datetime)

Defined by

:   [EntryQuery::$after](api:craft\elements\db\EntryQuery::$after)

Settable by

:   [after()](api:craft\elements\db\EntryQuery::after())



The minimum Post Date that resulting entries can have.


::: code
```twig
{# fetch entries written in the last 7 days #}
{% set entries = craft.entries()
    .after(now|date_modify('-7 days'))
    .all() %}
```

```php
// fetch entries written in the last 7 days
$entries = \craft\elements\Entry::find()
    ->after((new \DateTime())->modify('-7 days'))
    ->all();
```
:::
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

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

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


### `authorGroupId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [null](http://www.php.net/language.types.null)

Defined by

:   [EntryQuery::$authorGroupId](api:craft\elements\db\EntryQuery::$authorGroupId)

Settable by

:   [authorGroup()](api:craft\elements\db\EntryQuery::authorGroup()), [authorGroupId()](api:craft\elements\db\EntryQuery::authorGroupId())



The user group ID(s) that the resulting entries’ authors must be in.


::: code
```twig
{# fetch entries authored by people in the Authors group #}
{% set entries = craft.entries()
    .authorGroup('authors')
    .all() %}
```

```php
// fetch entries authored by people in the Authors group
$entries = \craft\elements\Entry::find()
    ->authorGroup('authors')
    ->all();
```
:::
### `authorId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [null](http://www.php.net/language.types.null)

Defined by

:   [EntryQuery::$authorId](api:craft\elements\db\EntryQuery::$authorId)

Settable by

:   [authorId()](api:craft\elements\db\EntryQuery::authorId())



The user ID(s) that the resulting entries’ authors must have.


### `before`

Allowed types

:   [string](http://www.php.net/language.types.string), [array](http://www.php.net/language.types.array), [DateTime](http://www.php.net/class.datetime)

Defined by

:   [EntryQuery::$before](api:craft\elements\db\EntryQuery::$before)

Settable by

:   [before()](api:craft\elements\db\EntryQuery::before())



The maximum Post Date that resulting entries can have.


::: code
```twig
{# fetch entries written before 4/4/2018 #}
{% set entries = craft.entries()
    .before('2018-04-04')
    .all() %}
```

```php
// fetch entries written before 4/4/2018
$entries = \craft\elements\Entry::find()
    ->before('2018-04-04')
    ->all();
```
:::
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

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$descendantOf](api:craft\elements\db\ElementQuery::$descendantOf)

Settable by

:   [descendantOf()](api:craft\elements\db\ElementQuery::descendantOf())



The element (or its ID) that results must be a descendant of.


### `editable`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [EntryQuery::$editable](api:craft\elements\db\EntryQuery::$editable)

Settable by

:   [editable()](api:craft\elements\db\EntryQuery::editable())



Whether to only return entries that the user has permission to edit.


### `enabledForSite`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$enabledForSite](api:craft\elements\db\ElementQuery::$enabledForSite)

Settable by

:   [enabledForSite()](api:craft\elements\db\ElementQuery::enabledForSite())



Whether the elements must be enabled for the chosen site.


### `expiryDate`

Allowed types

:   `mixed`

Defined by

:   [EntryQuery::$expiryDate](api:craft\elements\db\EntryQuery::$expiryDate)

Settable by

:   [expiryDate()](api:craft\elements\db\EntryQuery::expiryDate())



The Expiry Date that the resulting entries must have.


### `fixedOrder`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$fixedOrder](api:craft\elements\db\ElementQuery::$fixedOrder)

Settable by

:   [fixedOrder()](api:craft\elements\db\ElementQuery::fixedOrder())



Whether results should be returned in the order specified by [id()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-id).


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

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$nextSiblingOf](api:craft\elements\db\ElementQuery::$nextSiblingOf)

Settable by

:   [nextSiblingOf()](api:craft\elements\db\ElementQuery::nextSiblingOf())



The element (or its ID) that the result must be the next sibling of.


### `positionedAfter`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$positionedAfter](api:craft\elements\db\ElementQuery::$positionedAfter)

Settable by

:   [positionedAfter()](api:craft\elements\db\ElementQuery::positionedAfter())



The element (or its ID) that the results must be positioned after.


### `positionedBefore`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$positionedBefore](api:craft\elements\db\ElementQuery::$positionedBefore)

Settable by

:   [positionedBefore()](api:craft\elements\db\ElementQuery::positionedBefore())



The element (or its ID) that the results must be positioned before.


### `postDate`

Allowed types

:   `mixed`

Defined by

:   [EntryQuery::$postDate](api:craft\elements\db\EntryQuery::$postDate)

Settable by

:   [postDate()](api:craft\elements\db\EntryQuery::postDate())



The Post Date that the resulting entries must have.


::: code
```twig
{# fetch entries written in 2018 #}
{% set entries = craft.entries()
    .postDate(['and', '>= 2018-01-01', '< 2019-01-01'])
    .all() %}
```

```php
// fetch entries written in 2018
$entries = \craft\elements\Entry::find()
    ->postDate(['and', '>= 2018-01-01', '< 2019-01-01'])
    ->all();
```
:::
### `prevSiblingOf`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

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

:   [integer](http://www.php.net/language.types.integer), [array](http://www.php.net/language.types.array), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$relatedTo](api:craft\elements\db\ElementQuery::$relatedTo)

Settable by

:   [relatedTo()](api:craft\elements\db\ElementQuery::relatedTo())



The element relation criteria.

See [Relations](https://docs.craftcms.com/v3/relations.html) for supported syntax options.


### `search`

Allowed types

:   [string](http://www.php.net/language.types.string), [array](http://www.php.net/language.types.array), [craft\search\SearchQuery](api:craft\search\SearchQuery), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$search](api:craft\elements\db\ElementQuery::$search)

Settable by

:   [search()](api:craft\elements\db\ElementQuery::search())



The search term to filter the resulting elements by.

See [Searching](https://docs.craftcms.com/v3/searching.html) for supported syntax options.


### `sectionId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [null](http://www.php.net/language.types.null)

Defined by

:   [EntryQuery::$sectionId](api:craft\elements\db\EntryQuery::$sectionId)

Settable by

:   [section()](api:craft\elements\db\EntryQuery::section()), [sectionId()](api:craft\elements\db\EntryQuery::sectionId())



The section ID(s) that the resulting entries must be in.


::: code
```twig
{# fetch entries in the News section #}
{% set entries = craft.entries()
    .section('news')
    .all() %}
```

```php
// fetch entries in the News section
$entries = \craft\elements\Entry::find()
    ->section('news')
    ->all();
```
:::
### `siblingOf`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [craft\base\ElementInterface](api:craft\base\ElementInterface), [null](http://www.php.net/language.types.null)

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


### `typeId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [null](http://www.php.net/language.types.null)

Defined by

:   [EntryQuery::$typeId](api:craft\elements\db\EntryQuery::$typeId)

Settable by

:   [type()](api:craft\elements\db\EntryQuery::type()), [typeId()](api:craft\elements\db\EntryQuery::typeId())



The entry type ID(s) that the resulting entries must have.


::: code
```twig{4}
{# fetch entries in the News section #}
{% set entries = craft.entries()
    .section('news')
    .type('article')
    .all() %}
```

```php{4}
// fetch Article entries in the News section
$entries = \craft\elements\Entry::find()
    ->section('news')
    ->type('article')
    ->all();
```
:::
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
