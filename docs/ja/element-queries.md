# エレメントクエリ

エレメントクエリは、Craft でエレメントを取得するためにチューニングされた[クエリビルダー](https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder)です。いくつのカスタムパラメータがあり、エレメントを取得するのに必要な実際の SQL クエリの複雑さをすべて抽象化します。生データでなく、エレメントモデルを返します。

## エレメントクエリの作成

PHP と Twig 両方のコードで、エレメントクエリを作成できます。次の通りです。

| エレメントタイプ | PHP | Twig |
| ------------- | ------------------------------------- | ---------------------- |
| アセット | `\craft\elements\Asset::find()` | `craft.assets()` |
| カテゴリ | `\craft\elements\Category::find()` | `craft.categories()` |
| エントリ | `\craft\elements\Entry::find()` | `craft.entries()` |
| 行列ブロック | `\craft\elements\MatrixBlock::find()` | `craft.matrixBlocks()` |
| タグ | `\craft\elements\Tag::find()` | `craft.tags()` |
| ユーザー | `\craft\elements\User::find()` | `craft.users()` |

## パラメータの設定

作成したエレメントクエリには、パラメータをセットすることができます。

利用可能なパラメータは、エレメントタイプによって異なります。Craft の組み込みエレメントタイプでサポートされているパラメータの一覧は、次の通りです。

- [アセット](element-query-params/asset-query-params.md)
- [カテゴリ](element-query-params/category-query-params.md)
- [エントリ](element-query-params/entry-query-params.md)
- [行列ブロック](element-query-params/matrix-block-query-params.md)
- [タグ](element-query-params/tag-query-params.md)
- [ユーザー](element-query-params/user-query-params.md)

パラメータは、次のように連結したメソッドコールとしてセットする必要があります。

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

### パラメータの一括設定

次のように、パラメータを一括設定することもできます。

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

### パラメータ値の構文

ほんとんどのパラメータ値は、エレメントクエリの条件として適用される前に、 <api:craft\helpers\Db::parseParam()> を通して処理されます。そのメソッドは、次のようなことを可能にします。

- `['and', 'value1', 'value2']`
- `['or', 'value1', 'value2']`
- `['value1', 'value2']` _（暗黙の `'or'`）_
- `':empty:` _（`null`、または、空の文字列かどうかのチェック）_
- `':notempty:'` _（`':empty:'` の反対）_
- `'not value'`
- `'!= value'`
- `'<= value'`
- `'>= value'`
- `'< value'`
- `'> value'`
- `'= value'`
- `'*value*'`
- `'not *value*'`

### カスタムフィールドのパラメータ

コアのパラメータに加えて、ほとんどのカスタムフィールドは独自のパラメータもサポートしています。

```twig
{% set query = craft.entries()
 .section('news')
 .myCustomFieldHandle('param-value')
 .all() %}
```

## エレメントクエリの実行

クエリにパラメータを定義したら、必要とするデータに応じて、それを実行するために利用可能な複数のメソッドがあります。

### `exists()`

クエリにマッチするエレメントがあるかどうかを返します。

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

クエリにマッチしたエレメントの総数を返します。

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

配列内のすべてのエレメントを返します。

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

最初にマッチするエレメントを返します。存在しない場合、`null` を返します。

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

マッチした `n` 番目のエレメントを返します。存在しない場合、`null` を返します。`n` は 0 からはじまるため、`nth(0)` は最初のエレメントを `nth(1)` は2番目のエレメントを取得する点に注意してください。

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

マッチしたエレメントの ID の配列を返します。

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

すべての配列の最初のカラム値を返します。デフォルトではエレメントの ID になりますが、`select()` パラメータでカスタマイズすることができます。

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

最初にマッチしたエレメントの最初のカラム値を返します。デフォルトではエレメントの ID になりますが、`select()` パラメータでカスタマイズすることができます。

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

### 集計メソッド

次のメソッドは、マッチしたエレメントの最初のカラムで集計メソッドを実行し、その結果を返します。

- `sum()` – 最初のカラムのすべての値の合計を返します
- `average()` – 最初のカラムのすべての値の平均値を返します
- `min()` – 最初のカラムのすべての値の最小値を返します
- `max()` – 最初のカラムのすべての値の最大値を返します

デフォルトでは最初のカラムがエレメントの ID になりますが、`select()` パラメータでカスタマイズすることができます。

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

