# Element Queries

You can fetch elements in your templates or PHP code using **element queries**.

To use them, call the function that maps to the element type you want to load (e.g. `craft.entries()`), [set some parameters](#setting-parameters) on it, and then [execute it](#executing-element-queries) by calling `all()`.

::: code
```twig
{% set entries = craft.entries()
    .section('news')
    .orderBy('postDate desc')
    .limit(10)
    .all()
%}
```
```php
use craft\elements\Entry;

$entries = Entry::find()
    ->section('news')
    ->orderBy('postDate desc')
    ->limit(10)
    ->all();
```
:::

Each element type has its own element query factory functions, and its own set of parameters, which are documented on the individual element query pages:

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

## Setting Parameters

Parameters should be set with chained function calls, like so:

::: code
```twig{2-3}
{% set entries = craft.entries()
    .section('news')
    .limit(10)
    .all()
%}
```
```php{4-5}
use craft\elements\Entry;

$entries = Entry::find()
    ->section('news')
    ->limit(10)
    ->all();
```
:::

### Batch-Setting Parameters

You can also batch-set parameters like so:

::: code
```twig{2-6}
{% set entries = craft.entries(
    {
        section: 'news',
        orderBy: ['postDate' => SORT_DESC],
        limit: 10
    }
).all() %}
```
```php
use craft\elements\Entry;

$query = Entry::find();
\Craft::configure($query, [
    'section' => 'news',
    'orderBy' => ['postDate' => SORT_DESC],
    'limit' => 10
]);
$entries = $query->all();
```
:::

::: warning
If you want to set the `orderBy` parameter like this, you must use the `['columnName' => SORT_ASC]` syntax rather than `'columnName asc'`.
:::

### Param Value Syntax

Most parameter values will get processed through <api:craft\helpers\Db::parseParam()> or [parseDateParam()](api:craft\helpers\Db::parseDateParam()) before being applied as a condition on the element query. That method makes things like the following possible:

- `['and', 'value1', 'value2']`
- `['or', 'value1', 'value2']`
- `['value1', 'value2']` _(implied `'or'`)_
- `':empty:` _(checks for `null` or an empty string)_
- `':notempty:'` _opposite of `':empty:'`)_
- `'not value'`
- `'!= value'`
- `'<= value'`
- `'>= value'`
- `'< value'`
- `'> value'`
- `'= value'`
- `'*value*'`
- `'not *value*'`

For example, if you want to load entries with a custom `eventDate` field set within a date range, you can do this:

::: code
```twig{5}
{% set start = date('first day of next month')|atom %}
{% set end = date('last day of next month')|atom %}
{% set entries = craft.entries()
    .section('events')
    .eventDate(['and', ">= #{start}", "<= #{end}"])
    .all()
%}
```
```php{7}
use craft\elements\Entry;

$start = new \DateTime('first day of next month');
$end = new \DateTime('last day of next month');
$entries = Entry::find()
    ->section('events')
    ->eventDate(['and', ">= {$start}", "<= {$end}"])
    ->all();
```
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
    .all()
%}
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
    .one()
%}
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
    .exists()
%}
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
    .count()
%}
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

If you just a list of matching element IDs, you can call `ids()`.

::: code
```twig
{% set entryIds = craft.entries()
    .section('news')
    .ids()
%}
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
