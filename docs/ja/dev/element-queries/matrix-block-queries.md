# 行列ブロッククエリ

**行列ブロッククエリ**を使用して、テンプレートや PHP コード内で行列ブロックを取得できます。

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

行列ブロッククエリを作成すると、結果を絞り込むための[パラメータ](#parameters)をセットできます。さらに、`.all()` を呼び出して[実行](README.md#executing-element-queries)できます。[MatrixBlock](api:craft\elements\MatrixBlock) オブジェクトの配列が返されます。

::: tip
エレメントクエリがどのように機能するかについては、[エレメントクエリについて](README.md)を参照してください。
:::

## 実例

次の操作を行うことで、エレメントのすべての行列ブロックのコンテンツを表示できます。

1. `craft.matrixBlocks()` で行列ブロッククエリを作成します。
2. [owner](#owner)、[fieldId](#fieldid)、および、[type](#type) パラメータをセットします。
3. `.all()` で行列ブロックを取得します。
4. [for](https://twig.symfony.com/doc/2.x/tags/for.html) タグを使用して行列ブロックをループ処理し、コンテンツを出力します。

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
返される行列ブロックにカスタムフィールドのコンテンツが代入されるよう、[fieldId](#fieldid) または [id](#id) パラメータを設定する必要があります。
:::

## パラメータ

行列ブロッククエリは、次のパラメータをサポートしています。

<!-- BEGIN PARAMS -->

### `anyStatus`

[status](#status) および [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) パラメータをクリアします。

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

[MatrixBlock](api:craft\elements\MatrixBlock) オブジェクトではなく、データの配列として、マッチした行列ブロックをクエリが返します。

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

行列ブロックの作成日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に作成されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に作成されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に作成されたもの。

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

行列ブロックの最終アップデート日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降にアップデートされたもの。
| `'< 2018-05-01'` | 2018-05-01 より前にアップデートされたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間にアップデートされたもの。

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

フィールドの ID ごとに、行列ブロックが属するフィールドに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `1` | ID が 1 のフィールド内。
| `'not 1'` | ID が 1 のフィールド内ではない。
| `[1, 2]` | ID が 1 または 2 のフィールド内。
| `['not', 1, 2]` | ID が 1 または 2 のフィールド内ではない。

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

クエリの結果を [id](#id) で指定された順序で返します。

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

行列ブロックの ID に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `1` | ID が 1。
| `'not 1'` | ID が 1ではない。
| `[1, 2]` | ID が 1 または 2。
| `['not', 1, 2]` | ID が 1 または 2 ではない。

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
特定の順序で結果を返したい場合、[fixedOrder](#fixedorder) と組み合わせることができます。
:::

### `inReverse`

クエリの結果を逆順で返します。

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

返される行列ブロックの数を決定します。

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

結果からスキップされる行列ブロックの数を決定します。

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

返される行列ブロックの順序を決定します。

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

指定されたエレメントに基づいて、[ownerId](#ownerid) および [siteId](#siteid) パラメータをセットします。

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

オーナーの ID ごとに、行列ブロックのオーナーエレメントに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `1` | ID が 1 のエレメントによって作成されたもの。
| `'not 1'` | ID が 1 のエレメントによって作成されたものではない。
| `[1, 2]` | ID が 1 または 2 のエレメントによって作成されたもの。
| `['not', 1, 2]` | ID が 1 または 2 のエレメントによって作成されたものではない。

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

### `relatedTo`

特定の他のエレメントと関連付けられた行列ブロックだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[リレーション](https://docs.craftcms.com/v3/relations.html)を参照してください。

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

検索クエリにマッチする行列ブロックだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[検索](https://docs.craftcms.com/v3/searching.html)を参照してください。

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

行列ブロックを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

利用可能な値には、次のものが含まれます。

| 値 | 取得される行列ブロック
| - | -
| `'foo'` | ハンドルが `foo` のサイトから。
| [craft\models\Site](api:craft\models\Site) オブジェクト | オブジェクトで表されるサイトから。

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

サイトの ID ごとに、行列ブロックを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

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

行列ブロックのステータスに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `'enabled'`  _（デフォルト）_ | 有効になっているもの。
| `'disabled'` | 無効になっているもの。

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

ソフトデリートされた行列ブロックだけに、クエリの結果を絞り込みます。

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

行列ブロックのブロックタイプに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `'foo'` | ハンドルが `foo` のタイプ。
| `'not foo'` | ハンドルが `foo` のタイプではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar` のタイプ。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` のタイプではない。
| [MatrixBlockType](api:craft\models\MatrixBlockType) オブジェクト | オブジェクトで表されるタイプ。

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

タイプの ID ごとに、行列ブロックのブロックタイプに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得する行列ブロック
| - | -
| `1` | ID が 1 のタイプ。
| `'not 1'` | ID が 1 のタイプではない。
| `[1, 2]` | ID が 1 または 2 のタイプ。
| `['not', 1, 2]` | ID が 1 または 2 のタイプではない。

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

行列ブロックの UID に基づいて、クエリの結果を絞り込みます。

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

関連付けられたエレメントを eager-loaded した状態で、マッチした行列ブロックをクエリが返します。

このパラメーターがどのように機能するかの詳細については、[エレメントのEager-Loading](https://docs.craftcms.com/v3/dev/eager-loading-elements.html)を参照してください。

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

