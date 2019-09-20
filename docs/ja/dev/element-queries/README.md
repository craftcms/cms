# エレメントクエリについて

**エレメントクエリ**を使用して、テンプレートや PHP コード内でエレメント（エントリ、カテゴリ、アセットなど）を取得できます。

エレメントクエリの働きは、3つのステップで成り立ちます。

1. **エレメントクエリの作成。** 取得しようとしているエレメントタイプに基づく「ファクトリファンクション」を呼び出すことによって、これを行います。例えば、エントリを取得したい場合、新しい[エントリクエリ](entry-queries.md)を返す `craft.entries()` を呼び出します。
2. **いくつかのパラメータをセット。** デフォルトでは、エレメントクエリは指定されたタイプのすべてのエレメントを返すように設定されています。クエリのパラメータを設定することで、エレメントを絞り込むことができます。
3. **クエリの実行。** クエリパラメータを指定したら、Craft がエレメントを取得して結果を得る準備ができています。複数のエレメント、または、1つだけ必要なのかに応じて `.all()` か `.one()` を呼び出すことで、それを実行できます。

典型的なエレメントクエリは、次のようになります。

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

それぞれのタイプのエレメントは、エレメントクエリを作成するための独自のファンクションを持っていて、それぞれ独自のパラメータをセットできます。それらがどんな働きをするかの詳細については、個々のエレメントクエリのページを参照してください。

- [アセットクエリ](asset-queries.md)
- [カテゴリクエリ](category-queries.md)
- [エントリクエリ](entry-queries.md)
- [グローバル設定クエリ](global-set-queries.md)
- [行列ブロッククエリ](matrix-block-queries.md)
- [タグクエリ](tag-queries.md)
- [ユーザークエリ](user-queries.md)

::: tip
ほとんどのカスタムフィールドは、フィールドハンドルの名前に基づいて、エレメントクエリのパラメータもサポートしています。
:::

## エレメントクエリの実行

クエリのパラメータを定義したら、必要とするものに応じて、それを実行するために利用可能な複数のファンクションがあります。

### `all()`

ほとんどの場合、照会しているエレメントの取得だけを望んでいます。`all()` ファンクションでそれを実行します。

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

単一のエレメントだけを取得したい場合、`all()` の代わりに `one()` を呼び出します。エレメント、または、マッチするエレメントが存在しない場合は `null` のいずれかを返します。

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

エレメントクエリにマッチするいずれかのエレメントが存在するかを確認したい場合、`true` または `false` を返す、`exists()` を呼び出します。

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

エレメントクエリにマッチするエレメントの数を知りたい場合、`count()` を呼び出します。

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
`count()` を呼び出す際、`limit` および `offset` パラメータは無視されます。
:::

### `ids()`

マッチするエレメント ID のリストが必要な場合、`ids()` を呼び出します。

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

## 高度なエレメントクエリ

エレメントクエリは、専門的な[クエリビルダー](https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder)です。そのため、<api:craft\db\Query> によって提供されるものとほとんど同じメソッドをサポートしています。

### 選択

- [select()](api:yii\db\Query::select())
- [addSelect()](api:yii\db\Query::addSelect())
- [distinct()](api:yii\db\Query::distinct())
- [groupBy()](api:yii\db\Query::groupBy())

### 結合

- [innerJoin()](api:yii\db\Query::innerJoin())
- [leftJoin()](api:yii\db\Query::leftJoin())
- [rightJoin()](api:yii\db\Query::rightJoin())

### 条件

- [where()](api:yii\db\QueryTrait::where())
- [andWhere()](api:yii\db\QueryTrait::andWhere())
- [orWhere()](api:yii\db\QueryTrait::orWhere())
- [filterWhere()](api:yii\db\QueryTrait::filterWhere())
- [andFilterWhere()](api:yii\db\QueryTrait::andFilterWhere())
- [orFilterWhere()](api:yii\db\QueryTrait::orFilterWhere())

### クエリの実行

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
エレメントクエリをカスタマイズする際、[getRawSql()](api:craft\db\Query::getRawSql()) を呼び出すことで、クエリによって実行される完全な SQL を取得できます。そのため、何を修正すればよいかの良いアイデアを得られるでしょう。

```twig
{{ dump(query.getRawSql()) }}
```

