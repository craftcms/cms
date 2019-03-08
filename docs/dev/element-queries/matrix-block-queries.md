# Matrix Block Queries

You can fetch Matrix blocks in your templates or PHP code using **Matrix block queries**.

::: code
```twig
{# Create a new Matrix block query #}
{% set myMatrixBlockQuery = craft.matrixBlocks() %}
```
```php
// Create a new Matrix block query
$myMatrixBlockQuery = \craft\elements\MatrixBlock::find();
```
:::

Once you’ve created a Matrix block query, you can set [parameters](#parameters) on it to narrow down the results, and then [execute it](README.md#executing-element-queries) by calling `.all()`. An array of [MatrixBlock](api:craft\elements\MatrixBlock) objects will be returned.

::: tip
See [Introduction to Element Queries](README.md) to learn about how element queries work.
:::

## Example

We can display content from all the Matrix blocks of an element by doing the following:

1. Create a Matrix block query with `craft.matrixBlocks()`.
2. Set the [owner](#owner), [fieldId](#fieldid), and [type](#type) parameters on it.
3. Fetch the Matrix blocks with `.all()`.
4. Loop through the Matrix blocks using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a Matrix block query with the 'owner', 'fieldId', and 'type' parameters #}
{% set myMatrixBlockQuery = craft.matrixBlocks()
    .owner(myEntry)
    .fieldId(10)
    .type('text') %}

{# Fetch the Matrix blocks #}
{% set matrixBlocks = myMatrixBlockQuery.all() %}

{# Display their contents #}
{% for block in blocks %}
    <p>{{ block.text }}</p>
{% endfor %}
```

::: warning
In order for the returned Matrix block(s) to be populated with their custom field content, you will need to either set the [fieldId](#fieldid) or [id](#id) parameter.
:::

## Parameters

Matrix block queries support the following parameters:

<!-- BEGIN PARAMS -->

### `anyStatus`

Clears out the [status](#status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.





::: code
```twig
{# Fetch all Matrix blocks, regardless of status #}
{% set MatrixBlocks = craft.matrixBlocks()
    .anyStatus()
    .all() %}
```

```php
// Fetch all Matrix blocks, regardless of status
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->anyStatus()
    ->all();
```
:::


### `asArray`

Causes the query to return matching Matrix blocks as arrays of data, rather than [MatrixBlock](api:craft\elements\MatrixBlock) objects.





::: code
```twig
{# Fetch Matrix blocks as arrays #}
{% set MatrixBlocks = craft.matrixBlocks()
    .asArray()
    .all() %}
```

```php
// Fetch Matrix blocks as arrays
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->asArray()
    ->all();
```
:::


### `dateCreated`

Narrows the query results based on the Matrix blocks’ creation dates.



Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch Matrix blocks created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set MatrixBlocks = craft.matrixBlocks()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch Matrix blocks created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::


### `dateUpdated`

Narrows the query results based on the Matrix blocks’ last-updated dates.



Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch Matrix blocks updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set MatrixBlocks = craft.matrixBlocks()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch Matrix blocks updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::


### `fieldId`

Narrows the query results based on the field the Matrix blocks belong to, per the fields’ IDs.

Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `1` | in a field with an ID of 1.
| `'not 1'` | not in a field with an ID of 1.
| `[1, 2]` | in a field with an ID of 1 or 2.
| `['not', 1, 2]` | not in a field with an ID of 1 or 2.



::: code
```twig
{# Fetch Matrix blocks in the field with an ID of 1 #}
{% set MatrixBlocks = craft.matrixBlocks()
    .fieldId(1)
    .all() %}
```

```php
// Fetch Matrix blocks in the field with an ID of 1
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->fieldId(1)
    ->all();
```
:::


### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).





::: code
```twig
{# Fetch Matrix blocks in a specific order #}
{% set MatrixBlocks = craft.matrixBlocks()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch Matrix blocks in a specific order
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::


### `id`

Narrows the query results based on the Matrix blocks’ IDs.



Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.



::: code
```twig
{# Fetch the Matrix block by its ID #}
{% set MatrixBlock = craft.matrixBlocks()
    .id(1)
    .one() %}
```

```php
// Fetch the Matrix block by its ID
$MatrixBlock = \craft\elements\MatrixBlock::find()
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
{# Fetch Matrix blocks in reverse #}
{% set MatrixBlocks = craft.matrixBlocks()
    .inReverse()
    .all() %}
```

```php
// Fetch Matrix blocks in reverse
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->inReverse()
    ->all();
```
:::


### `limit`

Determines the number of Matrix blocks that should be returned.



::: code
```twig
{# Fetch up to 10 Matrix blocks  #}
{% set MatrixBlocks = craft.matrixBlocks()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 Matrix blocks
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->limit(10)
    ->all();
```
:::


### `offset`

Determines how many Matrix blocks should be skipped in the results.



::: code
```twig
{# Fetch all Matrix blocks except for the first 3 #}
{% set MatrixBlocks = craft.matrixBlocks()
    .offset(3)
    .all() %}
```

```php
// Fetch all Matrix blocks except for the first 3
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->offset(3)
    ->all();
```
:::


### `orderBy`

Determines the order that the Matrix blocks should be returned in.



::: code
```twig
{# Fetch all Matrix blocks in order of date created #}
{% set MatrixBlocks = craft.matrixBlocks()
    .orderBy('dateCreated asc')
    .all() %}
```

```php
// Fetch all Matrix blocks in order of date created
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->orderBy('dateCreated asc')
    ->all();
```
:::


### `owner`

Sets the [ownerId](#ownerid) and [siteId](#siteid) parameters based on a given element.



::: code
```twig
{# Fetch Matrix blocks created for this entry #}
{% set MatrixBlocks = craft.matrixBlocks()
    .owner(myEntry)
    .all() %}
```

```php
// Fetch Matrix blocks created for this entry
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->owner($myEntry)
    ->all();
```
:::


### `ownerId`

Narrows the query results based on the owner element of the Matrix blocks, per the owners’ IDs.

Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `1` | created for an element with an ID of 1.
| `'not 1'` | not created for an element with an ID of 1.
| `[1, 2]` | created for an element with an ID of 1 or 2.
| `['not', 1, 2]` | not created for an element with an ID of 1 or 2.



::: code
```twig
{# Fetch Matrix blocks created for an element with an ID of 1 #}
{% set MatrixBlocks = craft.matrixBlocks()
    .ownerId(1)
    .all() %}
```

```php
// Fetch Matrix blocks created for an element with an ID of 1
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->ownerId(1)
    ->all();
```
:::


### `ownerSite`

Narrows the query results based on the site the owner element was saved for.

This parameter is only relevant for Matrix fields that are set to manage blocks on a per-site basis.

Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `'foo'` | created for an element in a site with a handle of `foo`.
| `a [Site](api:craft\models\Site)` object | created for an element in the site represented by the object.



::: code
```twig
{# Fetch Matrix blocks created for an element with an ID of 1,
   for a site with a handle of 'foo' #}
{% set MatrixBlocks = craft.matrixBlocks()
    .ownerId(1)
    .ownerSite('foo')
    .all() %}
```

```php
// Fetch Matrix blocks created for an element with an ID of 1,
// for a site with a handle of 'foo'
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->ownerId(1)
    .ownerSite('foo')
    ->all();
```
:::


### `ownerSiteId`

Narrows the query results based on the site the owner element was saved for, per the site’s ID.

This parameter is only relevant for Matrix fields that are set to manage blocks on a per-site basis.

Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `1` | created for an element in a site with an ID of 1.
| `':empty:'` | created in a field that isn’t set to manage blocks on a per-site basis.



::: code
```twig
{# Fetch Matrix blocks created for an element with an ID of 1,
   for a site with an ID of 2 #}
{% set MatrixBlocks = craft.matrixBlocks()
    .ownerId(1)
    .ownerSiteId(2)
    .all() %}
```

```php
// Fetch Matrix blocks created for an element with an ID of 1,
// for a site with an ID of 2
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->ownerId(1)
    .ownerSiteId(2)
    ->all();
```
:::


### `relatedTo`

Narrows the query results to only Matrix blocks that are related to certain other elements.



See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch all Matrix blocks that are related to myCategory #}
{% set MatrixBlocks = craft.matrixBlocks()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all Matrix blocks that are related to $myCategory
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->relatedTo($myCategory)
    ->all();
```
:::


### `search`

Narrows the query results to only Matrix blocks that match a search query.



See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{# Fetch all Matrix blocks that match the search query #}
{% set MatrixBlocks = craft.matrixBlocks()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all Matrix blocks that match the search query
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->search($searchQuery)
    ->all();
```
:::


### `site`

Determines which site the Matrix blocks should be queried in.



The current site will be used by default.

Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `'foo'` | from the site with a handle of `foo`.
| a [craft\models\Site](api:craft\models\Site) object | from the site represented by the object.



::: code
```twig
{# Fetch Matrix blocks from the Foo site #}
{% set MatrixBlocks = craft.matrixBlocks()
    .site('foo')
    .all() %}
```

```php
// Fetch Matrix blocks from the Foo site
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->site('foo')
    ->all();
```
:::


### `siteId`

Determines which site the Matrix blocks should be queried in, per the site’s ID.



The current site will be used by default.



::: code
```twig
{# Fetch Matrix blocks from the site with an ID of 1 #}
{% set MatrixBlocks = craft.matrixBlocks()
    .siteId(1)
    .all() %}
```

```php
// Fetch Matrix blocks from the site with an ID of 1
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->siteId(1)
    ->all();
```
:::


### `status`

Narrows the query results based on the Matrix blocks’ statuses.



Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `'enabled'`  _(default)_ | that are enabled.
| `'disabled'` | that are disabled.



::: code
```twig
{# Fetch disabled Matrix blocks #}
{% set MatrixBlocks = craft.matrixBlocks()
    .status('disabled')
    .all() %}
```

```php
// Fetch disabled Matrix blocks
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->status('disabled')
    ->all();
```
:::


### `trashed`

Narrows the query results to only Matrix blocks that have been soft-deleted.





::: code
```twig
{# Fetch trashed Matrix blocks #}
{% set MatrixBlocks = {twig-function}
    .trashed()
    .all() %}
```

```php
// Fetch trashed Matrix blocks
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->trashed()
    ->all();
```
:::


### `type`

Narrows the query results based on the Matrix blocks’ block types.

Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `'foo'` | of a type with a handle of `foo`.
| `'not foo'` | not of a type with a handle of `foo`.
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
| an [MatrixBlockType](api:craft\models\MatrixBlockType) object | of a type represented by the object.



::: code
```twig
{# Fetch Matrix blocks with a Foo block type #}
{% set MatrixBlocks = myEntry.myMatrixField
    .type('foo')
    .all() %}
```

```php
// Fetch Matrix blocks with a Foo block type
$MatrixBlocks = $myEntry->myMatrixField
    ->type('foo')
    ->all();
```
:::


### `typeId`

Narrows the query results based on the Matrix blocks’ block types, per the types’ IDs.

Possible values include:

| Value | Fetches Matrix blocks…
| - | -
| `1` | of a type with an ID of 1.
| `'not 1'` | not of a type with an ID of 1.
| `[1, 2]` | of a type with an ID of 1 or 2.
| `['not', 1, 2]` | not of a type with an ID of 1 or 2.



::: code
```twig
{# Fetch Matrix blocks of the block type with an ID of 1 #}
{% set MatrixBlocks = myEntry.myMatrixField
    .typeId(1)
    .all() %}
```

```php
// Fetch Matrix blocks of the block type with an ID of 1
$MatrixBlocks = $myEntry->myMatrixField
    ->typeId(1)
    ->all();
```
:::


### `uid`

Narrows the query results based on the Matrix blocks’ UIDs.





::: code
```twig
{# Fetch the Matrix block by its UID #}
{% set MatrixBlock = craft.matrixBlocks()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the Matrix block by its UID
$MatrixBlock = \craft\elements\MatrixBlock::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::


### `with`

Causes the query to return matching Matrix blocks eager-loaded with related elements.



See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch Matrix blocks eager-loaded with the "Related" field’s relations #}
{% set MatrixBlocks = craft.matrixBlocks()
    .with(['related'])
    .all() %}
```

```php
// Fetch Matrix blocks eager-loaded with the "Related" field’s relations
$MatrixBlocks = \craft\elements\MatrixBlock::find()
    ->with(['related'])
    ->all();
```
:::



<!-- END PARAMS -->
