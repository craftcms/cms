# Asset Queries

You can fetch assets in your templates or PHP code using **asset queries**.

::: code
```twig
{# Create a new asset query #}
{% set myAssetQuery = craft.assets() %}
```
```php
// Create a new asset query
$myAssetQuery = \craft\elements\Asset::find();
```
:::

Once you’ve created an asset query, you can set [parameters](#parameters) on it to narrow down the results, and then [execute it](README.md#executing-element-queries) by calling `.all()`. An array of [Asset](api:craft\elements\Asset) objects will be returned.

::: tip
See [Introduction to Element Queries](README.md) to learn about how element queries work.
:::

## Example

We can display a list of thumbnails for images in a “Photos” volume by doing the following:

1. Create an asset query with `craft.assets()`.
2. Set the [volume](#volume) and [kind](#kind) parameters on it.
3. Fetch the assets with `.all()`.
4. Loop through the assets using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to create the thumbnail list HTML.

```twig
{# Create an asset query with the 'volume' and 'kind' parameters #}
{% set myAssetQuery = craft.assets()
    .volume('photos')
    .kind('image') %}

{# Fetch the assets #}
{% set images = myAssetQuery.all() %}

{# Display the thumbnail list #}
<ul>
    {% for image in images %}
        <li><img src="{{ image.getUrl('thumb') }}" alt="{{ image.title }}"></li>
    {% endfor %}
</ul>
```

## Parameters

Asset queries support the following parameters:

<!-- BEGIN PARAMS -->

### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.





::: code
```twig
{# Fetch all assets, regardless of status #}
{% set assets = craft.assets()
    .anyStatus()
    .all() %}
```

```php
// Fetch all assets, regardless of status
$assets = \craft\elements\Asset::find()
    ->anyStatus()
    ->all();
```
:::


### `asArray`

Causes the query to return matching assets as arrays of data, rather than [Asset](api:craft\elements\Asset) objects.





::: code
```twig
{# Fetch assets as arrays #}
{% set assets = craft.assets()
    .asArray()
    .all() %}
```

```php
// Fetch assets as arrays
$assets = \craft\elements\Asset::find()
    ->asArray()
    ->all();
```
:::


### `dateCreated`

Narrows the query results based on the assets’ creation dates.



Possible values include:

| Value | Fetches assets…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch assets created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set assets = craft.assets()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch assets created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$assets = \craft\elements\Asset::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::


### `dateModified`

Narrows the query results based on the assets’ files’ last-modified dates.

Possible values include:

| Value | Fetches assets…
| - | -
| `'>= 2018-04-01'` | that were modified on or after 2018-04-01.
| `'< 2018-05-01'` | that were modified before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were modified between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch assets modified in the last month #}
{% set start = date('30 days ago')|atom %}

{% set assets = craft.assets()
    .dateModified(">= #{start}")
    .all() %}
```

```php
// Fetch assets modified in the last month
$start = new \DateTime('30 days ago')->format(\DateTime::ATOM);

$assets = \craft\elements\Asset::find()
    ->dateModified(">= {$start}")
    ->all();
```
:::


### `dateUpdated`

Narrows the query results based on the assets’ last-updated dates.



Possible values include:

| Value | Fetches assets…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch assets updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set assets = craft.assets()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch assets updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$assets = \craft\elements\Asset::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::


### `filename`

Narrows the query results based on the assets’ filenames.

Possible values include:

| Value | Fetches assets…
| - | -
| `'foo.jpg'` | with a filename of `foo.jpg`.
| `'foo*'` | with a filename that begins with `foo`.
| `'*.jpg'` | with a filename that ends with `.jpg`.
| `'*foo*'` | with a filename that contains `foo`.
| `'not *foo*'` | with a filename that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a filename that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a filename that doesn’t contain `foo` or `bar`.



::: code
```twig
{# Fetch all the hi-res images #}
{% set assets = craft.assets()
    .filename('*@2x*')
    .all() %}
```

```php
// Fetch all the hi-res images
$assets = \craft\elements\Asset::find()
    ->filename('*@2x*')
    ->all();
```
:::


### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).





::: code
```twig
{# Fetch assets in a specific order #}
{% set assets = craft.assets()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch assets in a specific order
$assets = \craft\elements\Asset::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::


### `folderId`

Narrows the query results based on the folders the assets belong to, per the folders’ IDs.

Possible values include:

| Value | Fetches categories…
| - | -
| `1` | in a folder with an ID of 1.
| `'not 1'` | not in a folder with an ID of 1.
| `[1, 2]` | in a folder with an ID of 1 or 2.
| `['not', 1, 2]` | not in a folder with an ID of 1 or 2.



::: code
```twig
{# Fetch assets in the folder with an ID of 1 #}
{% set assets = craft.assets()
    .folderId(1)
    .all() %}
```

```php
// Fetch categories in the folder with an ID of 1
$assets = \craft\elements\Asset::find()
    ->folderId(1)
    ->all();
```
:::



::: tip
This can be combined with [includeSubfolders](#includesubfolders) if you want to include assets in all the subfolders of a certain folder.
:::
### `height`

Narrows the query results based on the assets’ image heights.

Possible values include:

| Value | Fetches assets…
| - | -
| `100` | with a height of 100.
| `'>= 100'` | with a height of at least 100.
| `['>= 100', '<= 1000']` | with a height between 100 and 1,000.



::: code
```twig
{# Fetch XL images #}
{% set assets = craft.assets()
    .kind('image')
    .height('>= 1000')
    .all() %}
```

```php
// Fetch XL images
$assets = \craft\elements\Asset::find()
    ->kind('image')
    ->height('>= 1000')
    ->all();
```
:::


### `id`

Narrows the query results based on the assets’ IDs.



Possible values include:

| Value | Fetches assets…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.



::: code
```twig
{# Fetch the asset by its ID #}
{% set asset = craft.assets()
    .id(1)
    .one() %}
```

```php
// Fetch the asset by its ID
$asset = \craft\elements\Asset::find()
    ->id(1)
    ->one();
```
:::



::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::


### `inReverse`

Causes the query results to be returned in reverse order.





::: code
```twig
{# Fetch assets in reverse #}
{% set assets = craft.assets()
    .inReverse()
    .all() %}
```

```php
// Fetch assets in reverse
$assets = \craft\elements\Asset::find()
    ->inReverse()
    ->all();
```
:::


### `includeSubfolders`

Broadens the query results to include assets from any of the subfolders of the folder specified by [folderId](#folderid).



::: code
```twig
{# Fetch assets in the folder with an ID of 1 (including its subfolders) #}
{% set assets = craft.assets()
    .folderId(1)
    .includeSubfolders()
    .all() %}
```

```php
// Fetch categories in the folder with an ID of 1 (including its subfolders)
$assets = \craft\elements\Asset::find()
    ->folderId(1)
    ->includeSubfolders()
    ->all();
```
:::



::: warning
This will only work if [folderId](#folderid) was set to a single folder ID.
:::
### `kind`

Narrows the query results based on the assets’ file kinds.

Supported file kinds:
- `access`
- `audio`
- `compressed`
- `excel`
- `flash`
- `html`
- `illustrator`
- `image`
- `javascript`
- `json`
- `pdf`
- `photoshop`
- `php`
- `powerpoint`
- `text`
- `video`
- `word`
- `xml`
- `unknown`

Possible values include:

| Value | Fetches assets…
| - | -
| `'image'` | with a file kind of `image`.
| `'not image'` | not with a file kind of `image`..
| `['image', 'pdf']` | with a file kind of `image` or `pdf`.
| `['not', 'image', 'pdf']` | not with a file kind of `image` or `pdf`.



::: code
```twig
{# Fetch all the images #}
{% set assets = craft.assets()
    .kind('image')
    .all() %}
```

```php
// Fetch all the images
$assets = \craft\elements\Asset::find()
    ->kind('image')
    ->all();
```
:::


### `limit`

Determines the number of assets that should be returned.



::: code
```twig
{# Fetch up to 10 assets  #}
{% set assets = craft.assets()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 assets
$assets = \craft\elements\Asset::find()
    ->limit(10)
    ->all();
```
:::


### `offset`

Determines how many assets should be skipped in the results.



::: code
```twig
{# Fetch all assets except for the first 3 #}
{% set assets = craft.assets()
    .offset(3)
    .all() %}
```

```php
// Fetch all assets except for the first 3
$assets = \craft\elements\Asset::find()
    ->offset(3)
    ->all();
```
:::


### `orderBy`

Determines the order that the assets should be returned in.



::: code
```twig
{# Fetch all assets in order of date created #}
{% set assets = craft.assets()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php
// Fetch all assets in order of date created
$assets = \craft\elements\Asset::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::


### `relatedTo`

Narrows the query results to only assets that are related to certain other elements.



See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch all assets that are related to myCategory #}
{% set assets = craft.assets()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all assets that are related to $myCategory
$assets = \craft\elements\Asset::find()
    ->relatedTo($myCategory)
    ->all();
```
:::


### `search`

Narrows the query results to only assets that match a search query.



See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all assets that match the search query #}
{% set assets = craft.assets()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all assets that match the search query
$assets = \craft\elements\Asset::find()
    ->search($searchQuery)
    ->all();
```
:::


### `site`

Determines which site the assets should be queried in.



The current site will be used by default.

Possible values include:

| Value | Fetches assets…
| - | -
| `'foo'` | from the site with a handle of `foo`.
| a `\craft\elements\db\Site` object | from the site represented by the object.



::: code
```twig
{# Fetch assets from the Foo site #}
{% set assets = craft.assets()
    .site('foo')
    .all() %}
```

```php
// Fetch assets from the Foo site
$assets = \craft\elements\Asset::find()
    ->site('foo')
    ->all();
```
:::


### `siteId`

Determines which site the assets should be queried in, per the site’s ID.



The current site will be used by default.



::: code
```twig
{# Fetch assets from the site with an ID of 1 #}
{% set assets = craft.assets()
    .siteId(1)
    .all() %}
```

```php
// Fetch assets from the site with an ID of 1
$assets = \craft\elements\Asset::find()
    ->siteId(1)
    ->all();
```
:::


### `size`

Narrows the query results based on the assets’ file sizes (in bytes).

Possible values include:

| Value | Fetches assets…
| - | -
| `1000` | with a size of 1,000 bytes (1KB).
| `'< 1000000'` | with a size of less than 1,000,000 bytes (1MB).
| `['>= 1000', '< 1000000']` | with a size between 1KB and 1MB.



::: code
```twig
{# Fetch assets that are smaller than 1KB #}
{% set assets = craft.assets()
    .size('< 1000')
    .all() %}
```

```php
// Fetch assets that are smaller than 1KB
$assets = \craft\elements\Asset::find()
    ->size('< 1000')
    ->all();
```
:::


### `title`

Narrows the query results based on the assets’ titles.



Possible values include:

| Value | Fetches assets…
| - | -
| `'Foo'` | with a title of `Foo`.
| `'Foo*'` | with a title that begins with `Foo`.
| `'*Foo'` | with a title that ends with `Foo`.
| `'*Foo*'` | with a title that contains `Foo`.
| `'not *Foo*'` | with a title that doesn’t contain `Foo`.
| `['*Foo*', '*Bar*'` | with a title that contains `Foo` or `Bar`.
| `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.



::: code
```twig
{# Fetch assets with a title that contains "Foo" #}
{% set assets = craft.assets()
    .title('*Foo*')
    .all() %}
```

```php
// Fetch assets with a title that contains "Foo"
$assets = \craft\elements\Asset::find()
    ->title('*Foo*')
    ->all();
```
:::


### `uid`

Narrows the query results based on the assets’ UIDs.





::: code
```twig
{# Fetch the asset by its UID #}
{% set asset = craft.assets()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the asset by its UID
$asset = \craft\elements\Asset::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::


### `volume`

Narrows the query results based on the volume the assets belong to.

Possible values include:

| Value | Fetches categories…
| - | -
| `'foo'` | in a volume with a handle of `foo`.
| `'not foo'` | not in a volume with a handle of `foo`.
| `['foo', 'bar']` | in a volume with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not in a volume with a handle of `foo` or `bar`.
| a [Volume](api:craft\base\Volume) object | in a volume represented by the object.



::: code
```twig
{# Fetch assets in the Foo volume #}
{% set assets = craft.assets()
    .volume('foo')
    .all() %}
```

```php
// Fetch assets in the Foo group
$assets = \craft\elements\Asset::find()
    ->volume('foo')
    ->all();
```
:::


### `volumeId`

Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.

Possible values include:

| Value | Fetches categories…
| - | -
| `1` | in a volume with an ID of 1.
| `'not 1'` | not in a volume with an ID of 1.
| `[1, 2]` | in a volume with an ID of 1 or 2.
| `['not', 1, 2]` | not in a volume with an ID of 1 or 2.



::: code
```twig
{# Fetch assets in the volume with an ID of 1 #}
{% set assets = craft.assets()
    .volumeId(1)
    .all() %}
```

```php
// Fetch categories in the volume with an ID of 1
$assets = \craft\elements\Asset::find()
    ->volumeId(1)
    ->all();
```
:::


### `width`

Narrows the query results based on the assets’ image widths.

Possible values include:

| Value | Fetches assets…
| - | -
| `100` | with a width of 100.
| `'>= 100'` | with a width of at least 100.
| `['>= 100', '<= 1000']` | with a width between 100 and 1,000.



::: code
```twig
{# Fetch XL images #}
{% set assets = craft.assets()
    .kind('image')
    .width('>= 1000')
    .all() %}
```

```php
// Fetch XL images
$assets = \craft\elements\Asset::find()
    ->kind('image')
    ->width('>= 1000')
    ->all();
```
:::


### `with`

Causes the query to return matching assets eager-loaded with related elements.



See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch assets eager-loaded with the "Related" field’s relations #}
{% set assets = craft.assets()
    .with(['related'])
    .all() %}
```

```php
// Fetch assets eager-loaded with the "Related" field’s relations
$assets = \craft\elements\Asset::find()
    ->with(['related'])
    ->all();
```
:::


### `withTransforms`

Causes the query to return matching assets eager-loaded with image transform indexes.

This can improve performance when displaying several image transforms at once, if the transforms
have already been generated.



::: code
```twig
{# Fetch assets with the 'thumbnail' and 'hiResThumbnail' transform data preloaded #}
{% set assets = craft.assets()
    .kind('image')
    .withTransforms(['thumbnail', 'hiResThumbnail'])
    .all() %}
```

```php
// Fetch assets with the 'thumbnail' and 'hiResThumbnail' transform data preloaded
$assets = \craft\elements\Asset::find()
    ->kind('image')
    ->withTransforms(['thumbnail', 'hiResThumbnail'])
    ->all();
```
:::



<!-- END PARAMS -->
