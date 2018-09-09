# Introduction to Element Queries

You can fetch elements (entries, categories, assets, etc.) in your templates or PHP code using **element queries**.

Working with element queries consists of three steps:

1. **Create the element query.** You do this by calling a “factory function” that is named after the element type you are going to fetch. For example, if you want to fetch entries, you’d call `craft.entries()`, which returns a new [entry query](entry-queries.md).
2. **Set some parameters.** By default, element queries will be configured to return all elements of the specified type. You can narrow that down to just the elements you care about by setting parameters on the query.
3. **Execute the query.** Once you’ve specified the query parameters, you’re ready for Craft to fetch the elements and give you the results. You do that by calling `.all()` or `.one()`, depending on whether you need multiple elements, or just one.

Here’s what a typical element query might look like:

::: code
```twig
{# Create an entry query and set some parameters on it #}
{% set entryQuery = craft.entries()
    .section('news')
    .orderBy('postDate desc')
    .limit(10) %}

{# Execute the query and get the results #}
{% set entries = entryQuery.all() %}
```
```php
use craft\elements\Entry;

// Create an entry query and set some parameters on it
$entryQuery = Entry::find()
    ->section('news')
    ->orderBy('postDate desc')
    ->limit(10);
    
// Execute the query and get the results
$entries = $entryQuery->all();
```
:::

Each type of element has its own function for creating element queries, and they each have their own parameters you can set. See the individual element query pages for more details on working with them:

- [Asset Queries](asset-queries.md)
- [Category Queries](category-queries.md)
- [Entry Queries](entry-queries.md)
- [Global Set Queries](global-set-queries.md)
- [Matrix Block Queries](matrix-block-queries.md)
- [Tag Queries](tag-queries.md)
- [User Queries](user-queries.md)

::: tip
Most custom fields support element query parameters as well, named after the field handles.
:::

## Executing Element Queries

Once you’ve defined your parameters on the query, there are multiple functions available to execute it, depending on what you need back.

### `all()`

Most of the time, you just want to get the elements that you’re querying for. You do that with the `all()` function.

::: code
```twig
{% set entries = craft.entries()
    .section('news')
    .limit(10)
    .all() %}
```
```php
use craft\elements\Entry;

$entries = Entry::find()
    ->section('news')
    ->limit(10)
    ->all();
```
:::

### `one()`

If you only need a single element, call `one()` instead of `all()`. It will either return the element or `null` if no matching element exists.

::: code
```twig
{% set entry = craft.entries()
    .section('news')
    .slug('hello-world')
    .one() %}
```
```php
use craft\elements\Entry;

$entry = Entry::find()
    ->section('news')
    ->slug('hello-world')
    ->one();
```
:::

### `exists()`

If you just need to check if any elements exist that match the element query, you can call `exists()`, which will return either `true` or `false`.

::: code
```twig
{% set exists = craft.entries()
    .section('news')
    .slug('hello-world')
    .exists() %}
```
```php
use craft\elements\Entry;

$exists = Entry::find()
    ->section('news')
    ->slug('hello-world')
    ->exists();
```
:::

### `count()`

If you want to know how many elements match your element query, you can call `count()`.

::: code
```twig
{% set count = craft.entries()
    .section('news')
    .count() %}
```
```php
use craft\elements\Entry;

$count = Entry::find()
    ->section('news')
    ->count();
```
:::

::: tip
The `limit` and `offset` parameters will be ignored when you call `count()`.
:::

### `ids()`

If you just want a list of matching element IDs, you can call `ids()`.

::: code
```twig
{% set entryIds = craft.entries()
    .section('news')
    .ids() %}
```
```php
use craft\elements\Entry;

$entryIds = Entry::find()
    ->section('news')
    ->ids();
```
:::

## Advanced Element Queries

Element queries are specialized [query builders](https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder) under the hood, so they support most of the same methods provided by <api:craft\db\Query>.

### Selections

- [select()](api:yii\db\Query::select())
- [addSelect()](api:yii\db\Query::addSelect())
- [distinct()](api:yii\db\Query::distinct())
- [groupBy()](api:yii\db\Query::groupBy())

### Joins

- [innerJoin()](api:yii\db\Query::innerJoin())
- [leftJoin()](api:yii\db\Query::leftJoin())
- [rightJoin()](api:yii\db\Query::rightJoin())

### Conditions

- [where()](api:yii\db\QueryTrait::where())
- [andWhere()](api:yii\db\QueryTrait::andWhere())
- [orWhere()](api:yii\db\QueryTrait::orWhere())
- [filterWhere()](api:yii\db\QueryTrait::filterWhere())
- [andFilterWhere()](api:yii\db\QueryTrait::andFilterWhere())
- [orFilterWhere()](api:yii\db\QueryTrait::orFilterWhere())

### Query Execution

- [all()](api:yii\db\Query::all())
- [one()](api:yii\db\Query::one())
- [nth()](api:craft\db\Query::nth())
- [exists()](api:yii\db\Query::exists())
- [count()](api:yii\db\Query::count())
- [column()](api:yii\db\Query::column())
- [scalar()](api:yii\db\Query::scalar())
- [sum()](api:yii\db\Query::sum())
- [average()](api:yii\db\Query::average())
- [min()](api:yii\db\Query::min())
- [max()](api:yii\db\Query::max())

::: tip
When customizing an element query, you can call [getRawSql()](api:craft\db\Query::getRawSql()) to get the full SQL that is going to be executed by the query, so you have a better idea of what to modify.

```twig
{{ dump(query.getRawSql()) }}
```
