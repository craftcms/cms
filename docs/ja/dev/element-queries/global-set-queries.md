# グローバル設定クエリ

**グローバル設定クエリ**を使用して、テンプレートや PHP コード内でグローバル設定を取得できます。

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

グローバル設定クエリを作成すると、結果を絞り込むための[パラメータ](#parameters)をセットできます。 さらに、`.all()` を呼び出して[実行](README.md#executing-element-queries)できます。[GlobalSet](api:craft\elements\GlobalSet) オブジェクトの配列が返されます。

::: tip
エレメントクエリがどのように機能するかについては、[エレメントクエリについて](README.md)を参照してください。
:::

## 実例

次の操作をすることで、プライマリサイトからグローバル設置をロードし、コンテンツを表示できます。

1. `craft.globalSets()` でグローバル設定クエリを作成します。
2. [handle](#handle) および [siteId](#siteid) パラメータをセットします。
3. `.one()` でグローバル設定を取得します。
4. コンテンツを出力します。

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
すべてのグローバル設定は、Twig テンプレートのグローバル変数としてすでに利用可能です。そのため、現在のサイトとは異なるサイトのコンテンツにアクセスする場合、`craft.globalSets()` を通して取得する必要があります。
:::

## パラメータ

グローバル設定クエリは、次のパラメータをサポートしています。

<!-- BEGIN PARAMS -->

### `anyStatus`

[status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) および [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) パラメータをクリアします。

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

[GlobalSet](api:craft\elements\GlobalSet) オブジェクトではなく、データの配列として、マッチしたグローバル設定をクエリが返します。

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

グローバル設定の作成日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するグローバル設定
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に作成されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に作成されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に作成されたもの。

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

グローバル設定の最終アップデート日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するグローバル設定
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降にアップデートされたもの。
| `'< 2018-05-01'` | 2018-05-01 より前にアップデートされたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間にアップデートされたもの。

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

クエリの結果を [id](#id) で指定された順序で返します。

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

グローバル設定のハンドルに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するグローバル設定
| - | -
| `'foo'` | ハンドルが `foo`。
| `'not foo'` | ハンドルが `foo` ではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar`。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` ではない。

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

グローバル設定の ID に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するグローバル設定
| - | -
| `1` | ID が 1。
| `'not 1'` | ID が 1ではない。
| `[1, 2]` | ID が 1 または 2。
| `['not', 1, 2]` | ID が 1 または 2 ではない。

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
特定の順序で結果を返したい場合、[fixedOrder](#fixedorder) と組み合わせることができます。
:::

### `inReverse`

クエリの結果を逆順で返します。

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

返されるグローバル設定の数を決定します。

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

結果からスキップされるグローバル設定の数を決定します。

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

返されるグローバル設定の順序を決定します。

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

特定の他のエレメントと関連付けられたグローバル設定だけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[リレーション](https://docs.craftcms.com/v3/relations.html)を参照してください。

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

検索クエリにマッチするグローバル設定だけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[検索](https://docs.craftcms.com/v3/searching.html)を参照してください。

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

グローバル設定を照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するグローバル設定
| - | -
| `'foo'` | ハンドルが `foo` のサイトから。
| `\craft\elements\db\Site` オブジェクト | オブジェクトで表されるサイトから。

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

サイトの ID ごとに、グローバル設定を照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

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

ソフトデリートされたグローバル設定だけに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch trashed global sets #}
{% set globalSets = {twig-function}
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

グローバル設定の UID に基づいて、クエリの結果を絞り込みます。

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

関連付けられたエレメントを eager-loaded した状態で、マッチしたグローバル設定をクエリが返します。

このパラメーターがどのように機能するかの詳細については、[エレメントのEager-Loading](https://docs.craftcms.com/v3/dev/eager-loading-elements.html)を参照してください。

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

