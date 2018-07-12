# Asset Queries

Asset queries are a type of [element query](README.md) used to fetch your project’s assets.

They are implemented by <api:craft\elements\db\AssetQuery>, and the elements returned by them will be of type <api:craft\elements\Asset>.

## Creating Asset Queries

You can create a new asset query from Twig by calling `craft.assets()`, or from PHP by calling <api:craft\elements\Asset::find()>.

::: code
```twig
{% set images = craft.assets()
    .volume('photos')
    .kind('image')
    .withTransforms(['thumb'])
    .all() %}

<ul>
    {% for image in images %}
        <li><img src="{{ image.getUrl('thumb') }}" alt="{{ image.title }}"></li>
    {% endfor %}
</ul>
```
```php
/** @var \craft\elements\Asset[] $images */
$images = \craft\elements\Asset::find()
    ->volume('photos')
    ->kind('image')
    ->withTransforms(['thumb'])
    ->all();
```
:::

## Parameters

Asset queries support the following parameters:

<!-- BEGIN PARAMS -->

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


### `dateModified`

Allowed types

:   `mixed`

Defined by

:   [AssetQuery::$dateModified](api:craft\elements\db\AssetQuery::$dateModified)

Settable by

:   [dateModified()](api:craft\elements\db\AssetQuery::dateModified())



The Date Modified that the resulting assets must have.


### `dateUpdated`

Allowed types

:   `mixed`

Defined by

:   [ElementQuery::$dateUpdated](api:craft\elements\db\ElementQuery::$dateUpdated)

Settable by

:   [dateUpdated()](api:craft\elements\db\ElementQuery::dateUpdated())



When the resulting elements must have been last updated.


### `enabledForSite`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$enabledForSite](api:craft\elements\db\ElementQuery::$enabledForSite)

Settable by

:   [enabledForSite()](api:craft\elements\db\ElementQuery::enabledForSite())



Whether the elements must be enabled for the chosen site.


### `filename`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$filename](api:craft\elements\db\AssetQuery::$filename)

Settable by

:   [filename()](api:craft\elements\db\AssetQuery::filename())



The filename(s) that the resulting assets must have.


### `fixedOrder`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [ElementQuery::$fixedOrder](api:craft\elements\db\ElementQuery::$fixedOrder)

Settable by

:   [fixedOrder()](api:craft\elements\db\ElementQuery::fixedOrder())



Whether results should be returned in the order specified by [id()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-id).


### `folderId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$folderId](api:craft\elements\db\AssetQuery::$folderId)

Settable by

:   [folderId()](api:craft\elements\db\AssetQuery::folderId())



The asset folder ID(s) that the resulting assets must be in.


### `height`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$height](api:craft\elements\db\AssetQuery::$height)

Settable by

:   [height()](api:craft\elements\db\AssetQuery::height())



The height (in pixels) that the resulting assets must have.


::: code
```twig{4}
{# fetch images that are at least 500 pixes high #}
{% set logos = craft.assets()
    .kind('image')
    .height('>= 500')
    .all() %}
```

```php{4}
// fetch images that are at least 500 pixels high
$images = \craft\elements\Asset::find()
    ->kind('image')
    ->height('>= 500')
    ->all();
```
:::
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


### `includeSubfolders`

Allowed types

:   [boolean](http://www.php.net/language.types.boolean)

Defined by

:   [AssetQuery::$includeSubfolders](api:craft\elements\db\AssetQuery::$includeSubfolders)

Settable by

:   [includeSubfolders()](api:craft\elements\db\AssetQuery::includeSubfolders())



Whether the query should search the subfolders of [folderId()](https://docs.craftcms.com/api/v3/craft-elements-db-assetquery.html#method-folderid).


### `kind`

Allowed types

:   [string](http://www.php.net/language.types.string), [string](http://www.php.net/language.types.string)[], [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$kind](api:craft\elements\db\AssetQuery::$kind)

Settable by

:   [kind()](api:craft\elements\db\AssetQuery::kind())



The file kind(s) that the resulting assets must be.

Supported file kinds:
- access
- audio
- compressed
- excel
- flash
- html
- illustrator
- image
- javascript
- json
- pdf
- photoshop
- php
- powerpoint
- text
- video
- word
- xml
- unknown



::: code
```twig
{# fetch only images #}
{% set logos = craft.assets()
    .kind('image')
    .all() %}
```

```php
// fetch only images
$logos = \craft\elements\Asset::find()
    ->kind('image')
    ->all();
```
:::
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


### `siteId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$siteId](api:craft\elements\db\ElementQuery::$siteId)

Settable by

:   [site()](api:craft\elements\db\ElementQuery::site()), [siteId()](api:craft\elements\db\ElementQuery::siteId())



The site ID that the elements should be returned in.


### `size`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$size](api:craft\elements\db\AssetQuery::$size)

Settable by

:   [size()](api:craft\elements\db\AssetQuery::size())



The size (in bytes) that the resulting assets must have.


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


### `volumeId`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [integer](http://www.php.net/language.types.integer)[], [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$volumeId](api:craft\elements\db\AssetQuery::$volumeId)

Settable by

:   [volume()](api:craft\elements\db\AssetQuery::volume()), [volumeId()](api:craft\elements\db\AssetQuery::volumeId())



The volume ID(s) that the resulting assets must be in.


::: code
```twig
{# fetch assets in the Logos volume #}
{% set logos = craft.assets()
    .volume('logos')
    .all() %}
```

```php
// fetch assets in the Logos volume
$logos = \craft\elements\Asset::find()
    ->volume('logos')
    ->all();
```
:::
### `width`

Allowed types

:   [integer](http://www.php.net/language.types.integer), [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$width](api:craft\elements\db\AssetQuery::$width)

Settable by

:   [width()](api:craft\elements\db\AssetQuery::width())



The width (in pixels) that the resulting assets must have.


::: code
```twig{4}
{# fetch images that are at least 500 pixes wide #}
{% set logos = craft.assets()
    .kind('image')
    .width('>= 500')
    .all() %}
```

```php{4}
// fetch images that are at least 500 pixels wide
$images = \craft\elements\Asset::find()
    ->kind('image')
    ->width('>= 500')
    ->all();
```
:::
### `with`

Allowed types

:   [string](http://www.php.net/language.types.string), [array](http://www.php.net/language.types.array), [null](http://www.php.net/language.types.null)

Defined by

:   [ElementQuery::$with](api:craft\elements\db\ElementQuery::$with)

Settable by

:   [with()](api:craft\elements\db\ElementQuery::with()), [andWith()](api:craft\elements\db\ElementQuery::andWith())



The eager-loading declaration.

See [Eager-Loading Elements](https://docs.craftcms.com/v3/eager-loading-elements.html) for supported syntax options.


### `withTransforms`

Allowed types

:   [string](http://www.php.net/language.types.string), [array](http://www.php.net/language.types.array), [null](http://www.php.net/language.types.null)

Defined by

:   [AssetQuery::$withTransforms](api:craft\elements\db\AssetQuery::$withTransforms)

Settable by

:   [withTransforms()](api:craft\elements\db\AssetQuery::withTransforms())



The asset transform indexes that should be eager-loaded, if they exist


::: code
```twig{4}
{# fetch images with their 'thumb' transforms preloaded #}
{% set logos = craft.assets()
    .kind('image')
    .withTransforms(['thumb'])
    .all() %}
```

```php{4}
// fetch images with their 'thumb' transforms preloaded
$images = \craft\elements\Asset::find()
    ->kind('image')
    ->withTransforms(['thumb'])
    ->all();
```
:::

<!-- END PARAMS -->
