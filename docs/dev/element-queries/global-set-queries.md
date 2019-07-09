# Global Set Queries

You can fetch global sets in your templates or PHP code using **global set queries**.

::: code
```twig
{# Create a new global set query #}
{% set myGlobalSetQuery = craft.globalSets() %}
```
```php
// Create a new global set query
$myGlobalSetQuery = \craft\elements\GlobalSet::find();
```
:::

Once you’ve created a global set query, you can set [parameters](#parameters) on it to narrow down the results, and then [execute it](README.md#executing-element-queries) by calling `.all()`. An array of [GlobalSet](api:craft\elements\GlobalSet) objects will be returned.

::: tip
See [Introduction to Element Queries](README.md) to learn about how element queries work.
:::

## Example

We can load a global set from the primary site and display its content by doing the following:

1. Create a global set query with `craft.globalSets()`.
2. Set the [handle](#handle) and [siteId](#siteid) parameters on it.
3. Fetch the global set with `.one()`.
4. Output its content.

```twig
{# Create a global set query with the 'handle' and 'siteId' parameters #}
{% set myGlobalSetQuery = craft.globalSets()
    .handle('footerCopy')
    .siteId(1) %}

{# Fetch the global set #}
{% set globalSet = myGlobalSetQuery.one() %}

{# Display the content #}
<p>{{ globalSet.copyrightInfo }}</p>
```

::: tip
All global sets are already available as global variables to Twig templates. So you only need to fetch them through  `craft.globalSets()` if you need to access their content for a different site than the current site.
:::

## Parameters

Global set queries support the following parameters:

<!-- BEGIN PARAMS -->

### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.





::: code
```twig
{# Fetch all global sets, regardless of status #}
{% set globalSets = craft.globalSets()
    .anyStatus()
    .all() %}
```

```php
// Fetch all global sets, regardless of status
$globalSets = \craft\elements\GlobalSet::find()
    ->anyStatus()
    ->all();
```
:::


### `asArray`

Causes the query to return matching global sets as arrays of data, rather than [GlobalSet](api:craft\elements\GlobalSet) objects.





::: code
```twig
{# Fetch global sets as arrays #}
{% set globalSets = craft.globalSets()
    .asArray()
    .all() %}
```

```php
// Fetch global sets as arrays
$globalSets = \craft\elements\GlobalSet::find()
    ->asArray()
    ->all();
```
:::


### `dateCreated`

Narrows the query results based on the global sets’ creation dates.



Possible values include:

| Value | Fetches global sets…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch global sets created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set globalSets = craft.globalSets()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch global sets created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$globalSets = \craft\elements\GlobalSet::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::


### `dateUpdated`

Narrows the query results based on the global sets’ last-updated dates.



Possible values include:

| Value | Fetches global sets…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch global sets updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set globalSets = craft.globalSets()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch global sets updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$globalSets = \craft\elements\GlobalSet::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::


### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).





::: code
```twig
{# Fetch global sets in a specific order #}
{% set globalSets = craft.globalSets()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch global sets in a specific order
$globalSets = \craft\elements\GlobalSet::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::


### `handle`

Narrows the query results based on the global sets’ handles.

Possible values include:

| Value | Fetches global sets…
| - | -
| `'foo'` | with a handle of `foo`.
| `'not foo'` | not with a handle of `foo`.
| `['foo', 'bar']` | with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not with a handle of `foo` or `bar`.



::: code
```twig
{# Fetch the global set with a handle of 'foo' #}
{% set globalSet = craft.globalSets()
    .handle('foo')
    .one() %}
```

```php
// Fetch the global set with a handle of 'foo'
$globalSet = \craft\elements\GlobalSet::find()
    ->handle('foo')
    ->one();
```
:::


### `id`

Narrows the query results based on the global sets’ IDs.



Possible values include:

| Value | Fetches global sets…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.



::: code
```twig
{# Fetch the global set by its ID #}
{% set globalSet = craft.globalSets()
    .id(1)
    .one() %}
```

```php
// Fetch the global set by its ID
$globalSet = \craft\elements\GlobalSet::find()
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
{# Fetch global sets in reverse #}
{% set globalSets = craft.globalSets()
    .inReverse()
    .all() %}
```

```php
// Fetch global sets in reverse
$globalSets = \craft\elements\GlobalSet::find()
    ->inReverse()
    ->all();
```
:::


### `limit`

Determines the number of global sets that should be returned.



::: code
```twig
{# Fetch up to 10 global sets  #}
{% set globalSets = craft.globalSets()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 global sets
$globalSets = \craft\elements\GlobalSet::find()
    ->limit(10)
    ->all();
```
:::


### `offset`

Determines how many global sets should be skipped in the results.



::: code
```twig
{# Fetch all global sets except for the first 3 #}
{% set globalSets = craft.globalSets()
    .offset(3)
    .all() %}
```

```php
// Fetch all global sets except for the first 3
$globalSets = \craft\elements\GlobalSet::find()
    ->offset(3)
    ->all();
```
:::


### `orderBy`

Determines the order that the global sets should be returned in.



::: code
```twig
{# Fetch all global sets in order of date created #}
{% set globalSets = craft.globalSets()
    .orderBy('dateCreated asc')
    .all() %}
```

```php
// Fetch all global sets in order of date created
$globalSets = \craft\elements\GlobalSet::find()
    ->orderBy('dateCreated asc')
    ->all();
```
:::


### `relatedTo`

Narrows the query results to only global sets that are related to certain other elements.



See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch all global sets that are related to myCategory #}
{% set globalSets = craft.globalSets()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all global sets that are related to $myCategory
$globalSets = \craft\elements\GlobalSet::find()
    ->relatedTo($myCategory)
    ->all();
```
:::


### `search`

Narrows the query results to only global sets that match a search query.



See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{# Fetch all global sets that match the search query #}
{% set globalSets = craft.globalSets()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all global sets that match the search query
$globalSets = \craft\elements\GlobalSet::find()
    ->search($searchQuery)
    ->all();
```
:::


### `site`

Determines which site(s) the global sets should be queried in.



The current site will be used by default.

Possible values include:

| Value | Fetches global sets…
| - | -
| `'foo'` | from the site with a handle of `foo`.
| `['foo', 'bar']` | from a site with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not in a site with a handle of `foo` or `bar`.
| a `\craft\elements\db\Site` object | from the site represented by the object.
| `'*'` | from any site.

::: tip
If multiple sites are specified, elements that belong to multiple sites will be returned multiple times. If you
only want unique elements to be returned, use `\craft\elements\db\unique()` in conjunction with this.
:::



::: code
```twig
{# Fetch global sets from the Foo site #}
{% set globalSets = craft.globalSets()
    .site('foo')
    .all() %}
```

```php
// Fetch global sets from the Foo site
$globalSets = \craft\elements\GlobalSet::find()
    ->site('foo')
    ->all();
```
:::


### `siteId`

Determines which site(s) the global sets should be queried in, per the site’s ID.



The current site will be used by default.



::: code
```twig
{# Fetch global sets from the site with an ID of 1 #}
{% set globalSets = craft.globalSets()
    .siteId(1)
    .all() %}
```

```php
// Fetch global sets from the site with an ID of 1
$globalSets = \craft\elements\GlobalSet::find()
    ->siteId(1)
    ->all();
```
:::


### `trashed`

Narrows the query results to only global sets that have been soft-deleted.





::: code
```twig
{# Fetch trashed global sets #}
{% set globalSets = craft.globalSets()
    .trashed()
    .all() %}
```

```php
// Fetch trashed global sets
$globalSets = \craft\elements\GlobalSet::find()
    ->trashed()
    ->all();
```
:::


### `uid`

Narrows the query results based on the global sets’ UIDs.





::: code
```twig
{# Fetch the global set by its UID #}
{% set globalSet = craft.globalSets()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the global set by its UID
$globalSet = \craft\elements\GlobalSet::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::


### `with`

Causes the query to return matching global sets eager-loaded with related elements.



See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch global sets eager-loaded with the "Related" field’s relations #}
{% set globalSets = craft.globalSets()
    .with(['related'])
    .all() %}
```

```php
// Fetch global sets eager-loaded with the "Related" field’s relations
$globalSets = \craft\elements\GlobalSet::find()
    ->with(['related'])
    ->all();
```
:::



<!-- END PARAMS -->
