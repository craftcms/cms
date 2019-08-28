# アセットクエリ

**アセットクエリ**を使用して、テンプレートや PHP コード内でアセットを取得できます。

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

アセットクエリを作成すると、結果を絞り込むための[パラメータ](#parameters)をセットできます。さらに、`.all()` を呼び出して[実行](README.md#executing-element-queries)できます。[Asset](api:craft\elements\Asset) オブジェクトの配列が返されます。

::: tip
エレメントクエリがどのように機能するかについては、[エレメントクエリについて](README.md)を参照してください。
:::

## 実例

次の操作を行うことで、「Photos」ボリュームに含まれる画像のサムネイルのリストを表示できます。

1. `craft.assets()` でアセットクエリを作成します。
2. [volume](#volume) および [kind](#kind) パラメータをセットします。
3. `.all()` でアセットを取得します。
4. [for](https://twig.symfony.com/doc/2.x/tags/for.html) タグを使用してアセットをループ処理し、サムネイルリストの HTML を作成します。

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

## パラメータ

アセットクエリは、次のパラメータをサポートしています。

<!-- BEGIN PARAMS -->

### `anyStatus`

[status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) および [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) パラメータをクリアします。

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

[Asset](api:craft\elements\Asset) オブジェクトではなく、データの配列として、マッチしたアセットをクエリが返します。

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

アセットの作成日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に作成されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に作成されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に作成されたもの。

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
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$assets = \craft\elements\Asset::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```

:::

### `dateModified`

アセットファイルの最終更新日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に更新されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に更新されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に更新されたもの。

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
$start = (new \DateTime('30 days ago'))->format(\DateTime::ATOM);

$assets = \craft\elements\Asset::find()
    ->dateModified(">= {$start}")
    ->all();
```

:::

### `dateUpdated`

アセットの最終アップデート日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降にアップデートされたもの。
| `'< 2018-05-01'` | 2018-05-01 より前にアップデートされたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間にアップデートされたもの。

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
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$assets = \craft\elements\Asset::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```

:::

### `filename`

アセットのファイル名に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'foo.jpg'` | ファイル名が `foo.jpg`。
| `'foo*'` | ファイル名が `foo` ではじまる。
| `'*.jpg'` | ファイル名が `.jpg` で終わる。
| `'*foo*'` | ファイル名に `foo` を含む。
| `'not *foo*'` | ファイル名に `foo` を含まない。
| `['*foo*', '*bar*']` | ファイル名に `foo` または `bar` を含む。
| `['not', '*foo*', '*bar*']` | ファイル名に `foo` または `bar` を含まない。

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

クエリの結果を [id](#id) で指定された順序で返します。

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

フォルダの ID ごとに、アセットが属するフォルダに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `1` | ID が 1 のフォルダ内。
| `'not 1'` | ID が 1 のフォルダ内ではない。
| `[1, 2]` | ID が 1 または 2 のフォルダ内。
| `['not', 1, 2]` | ID が 1 または 2 のフォルダ内ではない。

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
特定のフォルダのすべてのサブフォルダのアセットを含めたい場合、[includeSubfolders](#includesubfolders) と組み合わせることができます。
:::

### `height`

アセットの画像の高さに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `100` | 高さ 100px。
| `'>= 100'` | 少なくとも、高さ 100px。
| `['>= 100', '<= 1000']` | 高さ 100px から 1,000px の間。

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

アセットの ID に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `1` | ID が 1。
| `'not 1'` | ID が 1ではない。
| `[1, 2]` | ID が 1 または 2。
| `['not', 1, 2]` | ID が 1 または 2 ではない。

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
特定の順序で結果を返したい場合、[fixedOrder](#fixedorder) と組み合わせることができます。
:::

### `inReverse`

クエリの結果を逆順で返します。

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

[folderId](#folderid) で指定されたフォルダのすべてのサブフォルダにあるアセットを含むよう、クエリの結果を拡張します。

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
これは [folderId](#folderid) に単一のフォルダ ID がセットされているときだけ、動作します。
:::

### `kind`

アセットのファイルの種類に基づいて、クエリの結果を絞り込みます。

サポートされるファイルの種類：

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

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'image'` | ファイルの種類が `image`。
| `'not image'` | ファイルの種類が `image` ではない。
| `['image', 'pdf']` | ファイルの種類が `image` または `pdf`。
| `['not', 'image', 'pdf']` | ファイルの種類が `image` または `pdf` ではない。

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

返されるアセットの数を決定します。

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

結果からスキップされるアセットの数を決定します。

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

返されるアセットの順序を決定します。

::: code

```twig
{# Fetch all assets in order of date created #}
{% set assets = craft.assets()
    .orderBy('dateCreated asc')
    .all() %}
```

```php
// Fetch all assets in order of date created
$assets = \craft\elements\Asset::find()
    ->orderBy('dateCreated asc')
    ->all();
```

:::

### `relatedTo`

特定の他のエレメントと関連付けられたアセットだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[リレーション](https://docs.craftcms.com/v3/relations.html)を参照してください。

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

検索クエリにマッチするアセットだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[検索](https://docs.craftcms.com/v3/searching.html)を参照してください。

::: code

```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

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

アセットを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'foo'` | ハンドルが `foo` のサイトから。
| `\craft\elements\db\Site` オブジェクト | オブジェクトで表されるサイトから。

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

サイトの ID ごとに、アセットを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

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

アセットのファイルサイズ（バイト単位）に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `1000` | サイズが 1,000 bytes（1KB）。
| `'< 1000000'` | サイズが 1,000,000 bytes（1MB）よりも小さい。
| `['>= 1000', '< 1000000']` | サイズが 1KB から 1MB の間。

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

アセットのタイトルに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'Foo'` | タイトルが `Foo`。
| `'Foo*'` | タイトルが `Foo` ではじまる。
| `'*Foo'` | タイトルが `Foo` で終わる。
| `'*Foo*'` | タイトルが `Foo` を含む。
| `'not *Foo*'` | タイトルが `Foo` を含まない。
| `['*Foo*', '*Bar*']` | タイトルが `Foo` または `Bar` を含む。
| `['not', '*Foo*', '*Bar*']` | タイトルが `Foo` または `Bar` を含まない。

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

### `trashed`

ソフトデリートされたアセットだけに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch trashed assets #}
{% set assets = {twig-function}
    .trashed()
    .all() %}
```

```php
// Fetch trashed assets
$assets = \craft\elements\Asset::find()
    ->trashed()
    ->all();
```

:::

### `uid`

アセットの UID に基づいて、クエリの結果を絞り込みます。

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

アセットが属するボリュームに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `'foo'` | ハンドルが `foo` のボリューム内。
| `'not foo'` | ハンドルが `foo` のボリューム内ではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar` のボリューム内。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` のボリューム内ではない。
| [Volume](api:craft\base\Volume) オブジェクト | オブジェクトで表されるボリューム内。

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

ボリュームの ID ごとに、アセットが属するボリュームに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `1` | ID が 1 のボリューム内。
| `'not 1'` | ID が 1 のボリューム内ではない。
| `[1, 2]` | ID が 1 または 2 のボリューム内。
| `['not', 1, 2]` | ID が 1 または 2 のボリューム内ではない。

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

アセットの画像の幅に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するアセット
| - | -
| `100` | 幅 100px。
| `'>= 100'` | 少なくとも、幅 100px。
| `['>= 100', '<= 1000']` | 幅 100px から 1,000px の間。

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

関連付けられたエレメントを eager-loaded した状態で、マッチしたアセットをクエリが返します。

このパラメーターがどのように機能するかの詳細については、[エレメントのEager-Loading](https://docs.craftcms.com/v3/dev/eager-loading-elements.html)を参照してください。

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

イメージ変換インデックスを eager-loaded した状態で、マッチしたアセットをクエリが返します。

トランスフォームがすでに生成されている場合、一度に複数の変換された画像を表示する際のパフォーマンスが向上します。

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

