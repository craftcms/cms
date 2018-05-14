# Element Queries

Element queries are [query builders](http://www.yiiframework.com/doc-2.0/guide-db-query-builder.html) that are tuned for fetching elements in Craft. They have several custom parameters, and they abstract away all the complexities of the actual SQL query needed to fetch the elements. Rather than raw data, they return element models.

## Creating Element Queries

You can create element queries in both PHP and Twig code. Here’s how:

| Element Type  | PHP                                   | Twig
| ------------- | ------------------------------------- | ----------------------
| Assets        | `\craft\elements\Asset::find()`       | `craft.assets()`
| Categories    | `\craft\elements\Category::find()`    | `craft.categories()`
| Entries       | `\craft\elements\Entry::find()`       | `craft.entries()`
| Matrix blocks | `\craft\elements\MatrixBlock::find()` | `craft.matrixBlocks()`
| Tags          | `\craft\elements\Tag::find()`         | `craft.tags()`
| Users         | `\craft\elements\User::find()`        | `craft.users()`

## Setting Parameters

Once you’ve created an element query, you can set parameters on it.

The available parameters varies by element type. Here are the lists of parameters supported by Craft’s built-in element types:

- [Assets](element-query-params/asset-query-params.md)
- [Categories](element-query-params/category-query-params.md)
- [Entries](element-query-params/entry-query-params.md)
- [Matrix blocks](element-query-params/matrix-block-query-params.md)
- [Tags](element-query-params/tag-query-params.md)
- [Users](element-query-params/user-query-params.md)

The parameters should be set with chained method calls, like so:

::: code
```twig
{% set query = craft.entries()
    .section('news')
    .limit(10) %}
```

```php
use craft\elements\Entry;

$query = Entry::find()
    ->section('news')
    ->limit(10);
```
:::

### Batch-Setting Parameters

You can also batch-set parameters like so:

::: code
```twig
{% set query = craft.entries({
    section: 'news',
    limit: 10
}) %}
```

```php
use craft\elements\Entry;

$query = Entry::find();
\Craft::configure($query, [
    'section' => 'news',
    'limit' => 10
]);
```
:::

### Param Value Syntax

Most parameter values will get processed through [craft\helpers\Db::parseParam()](https://docs.craftcms.com/api/v3/craft-helpers-db.html#parseParam()-detail) before being applied as a condition on the element query. That method makes things like the following possible:

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

### Custom Field Parameters

In addition to the core parameters, most custom fields support their own parameters as well.

```twig
{% set query = craft.entries()
    .section('news')
    .myCustomFieldHandle('param-value')
    .all() %}
```

## Executing Element Queries

Once you’ve defined your parameters on the query, there are multiple methods available to execute it, depending on the data you need back.

### `exists()`

Returns whether any elements match the query.

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

Returns the total number of elements that are matched by the query.

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

### `all()`

Returns all of the elements in an array.

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

Returns the first matching element, or `null` if there isn’t one.

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

### `nth()`

Returns the `n`th matching element, or `null` if there isn’t one. Note that `n` is 0-indexed, so `nth(0)` will give you the first element, `nth(1)` will give you the second, etc.

::: code

```twig
{% set entry = craft.entries()
    .section('news')
    .nth(4) %}
```

```php
use craft\elements\Entry;

$entry = Entry::find()
    ->section('news')
    ->nth(4);
```
:::

### `ids()`

Returns an array of the IDs of the matching elements.

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

### `column()`

Returns an array of all the first column’s values. By default that will be the elements’ IDs, but you can customize that with the `select()` param.

::: code

```twig
{% set uris = craft.entries()
    .section('news')
    .select('uri')
    .column() %}
```

```php
use craft\elements\Entry;

$uris = Entry::find()
    ->section('news')
    ->select('uri')
    ->column();
```
:::

### `scalar()`

Returns the first column’s value of the first matching element. By default that will be the element’s ID, but you can customize that with the `select()` param.

::: code
```twig
{% set uri = craft.entries()
    .section('news')
    .slug('hello-world')
    .select('uri')
    .scalar() %}
```

```php
use craft\elements\Entry;

$uri = Entry::find()
    ->section('news')
    ->slug('hello-world')
    ->select('uri')
    ->scalar();
```
:::

### Aggregate Methods

The following methods will run an aggregate method on the first column of matching elements, and return the result:

- `sum()` – Returns the sum of all the values in the first column
- `average()` – Returns the average number of all the values in the first column
- `min()` – Returns the minimum number of all the values in the first column
- `max()` – Returns the maximum number of all the values in the first column

By default the first column will be the elements’ IDs, but you can customize that with the `select()` param.

::: code

```twig
{% set sum = craft.entries()
    .section('news')
    .select('field_someNumberField')
    .sum() %}
```

```php
use craft\elements\Entry;

$sum = Entry::find()
    ->section('news')
    ->select('field_someNumberField')
    ->sum();
```
:::
