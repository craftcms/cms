# エントリクエリ

**エントリクエリ**を使用して、テンプレートや PHP コード内でエントリを取得できます。

::: code

```twig
{# Create a new entry query #}
{% set myEntryQuery = craft.entries() %}
```

```php
// Create a new entry query
$myEntryQuery = \craft\elements\Entry::find();
```

:::

エレメントクエリを作成すると、結果を絞り込むための[パラメータ](#parameters)をセットできます。さらに、 `.all()` を呼び出して[実行](README.md#executing-element-queries)できます。[Entry](api:craft\elements\Entry) オブジェクトの配列が返されます。

::: tip
エレメントクエリがどのように機能するかについては、[エレメントクエリについて](README.md)を参照してください。
:::

## 実例

次の操作を行うことで、「Blog」セクションに含まれる最新10件のエントリを表示できます。

1. `craft.entries()` でエントリクエリを作成します。
2. [section](#section) および [limit](#limit) パラメータをセットします。
3. `.all()` でエントリを取得します。
4. [for](https://twig.symfony.com/doc/2.x/tags/for.html) タグを使用してエントリをループ処理し、ブログ投稿の HTML を出力します。

```twig
{# Create an entry query with the 'section' and 'limit' parameters #}
{% set myEntryQuery = craft.entries()
    .section('blog')
    .limit(10) %}

{# Fetch the entries #}
{% set entries = myEntryQuery.all() %}

{# Display the entries #}
{% for entry in entries %}
    <article>
        <h1><a href="{{ entry.url }}">{{ entry.title }}</a></h1>
        {{ entry.summary }}
        <a href="{{ entry.url }}">Continue reading</a>
    </article>
{% endfor %}
```

## パラメータ

エントリクエリは、次のパラメータをサポートしています。

<!-- BEGIN PARAMS -->

### `after`

特定の日付以降に投稿されたエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'2018-04-01'` | 2018-04-01 以降に投稿されたもの。
| [DateTime](http://php.net/class.datetime) オブジェクト | オブジェクトとして表される日付以降に投稿されたもの。

::: code

```twig
{# Fetch entries posted this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set entries = craft.entries()
    .after(firstDayOfMonth)
    .all() %}
```

```php
// Fetch entries posted this month
$firstDayOfMonth = new \DateTime('first day of this month');

$entries = \craft\elements\Entry::find()
    ->after($firstDayOfMonth)
    ->all();
```

:::

### `ancestorDist`

[ancestorOf](#ancestorof) で指定されたエントリから特定の距離だけ離れているエントリのみに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch entries above this one #}
{% set entries = craft.entries()
    .ancestorOf(myEntry)
    .ancestorDist(3)
    .all() %}
```

```php
// Fetch entries above this one
$entries = \craft\elements\Entry::find()
    ->ancestorOf($myEntry)
    ->ancestorDist(3)
    ->all();
```

:::

### `ancestorOf`

指定したエントリの先祖であるエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のエントリの上層。
| [Entry](api:craft\elements\Entry) オブジェクト | オブジェクトで表されるエントリの上層。

::: code

```twig
{# Fetch entries above this one #}
{% set entries = craft.entries()
    .ancestorOf(myEntry)
    .all() %}
```

```php
// Fetch entries above this one
$entries = \craft\elements\Entry::find()
    ->ancestorOf($myEntry)
    ->all();
```

:::

::: tip
どれだけ離れた先祖エントリを対象にするか制限したい場合、[ancestorDist](#ancestordist) と組み合わせることができます。
:::

### `anyStatus`

[status](#status) および [enabledForSite](#enabledforsite) パラメータをクリアします。

::: code

```twig
{# Fetch all entries, regardless of status #}
{% set entries = craft.entries()
    .anyStatus()
    .all() %}
```

```php
// Fetch all entries, regardless of status
$entries = \craft\elements\Entry::find()
    ->anyStatus()
    ->all();
```

:::

### `asArray`

[Entry](api:craft\elements\Entry) オブジェクトではなく、データの配列として、マッチしたエントリをクエリが返します。

::: code

```twig
{# Fetch entries as arrays #}
{% set entries = craft.entries()
    .asArray()
    .all() %}
```

```php
// Fetch entries as arrays
$entries = \craft\elements\Entry::find()
    ->asArray()
    ->all();
```

:::

### `authorGroup`

エントリの投稿者が属するユーザーグループに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'foo'` | ハンドルが `foo` のグループ内の投稿者。
| `'not foo'` | ハンドルが `foo` のグループ内の投稿者ではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar` のグループ内の投稿者。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` のグループ内の投稿者ではない。
| [UserGroup](api:craft\models\UserGroup) オブジェクト | オブジェクトで表されるグループ内の投稿者。

::: code

```twig
{# Fetch entries with an author in the Foo user group #}
{% set entries = craft.entries()
    .authorGroup('foo')
    .all() %}
```

```php
// Fetch entries with an author in the Foo user group
$entries = \craft\elements\Entry::find()
    ->authorGroup('foo')
    ->all();
```

:::

### `authorGroupId`

グループの ID ごとに、エントリの投稿者が属するユーザーグループに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のグループ内の投稿者。
| `'not 1'` | ID が 1 のグループ内の投稿者ではない。
| `[1, 2]` | ID が 1 または 2 のグループ内の投稿者。
| `['not', 1, 2]` | ID が 1 または 2 のグループ内の投稿者ではない。

::: code

```twig
{# Fetch entries with an author in a group with an ID of 1 #}
{% set entries = craft.entries()
    .authorGroupId(1)
    .all() %}
```

```php
// Fetch entries with an author in a group with an ID of 1
$entries = \craft\elements\Entry::find()
    ->authorGroupId(1)
    ->all();
```

:::

### `authorId`

エントリの投稿者に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 の投稿者。
| `'not 1'` | ID が 1 の投稿者ではない。
| `[1, 2]` | ID が 1 または 2 の投稿者。
| `['not', 1, 2]` | ID が 1 または 2 の投稿者ではない。

::: code

```twig
{# Fetch entries with an author with an ID of 1 #}
{% set entries = craft.entries()
    .authorId(1)
    .all() %}
```

```php
// Fetch entries with an author with an ID of 1
$entries = \craft\elements\Entry::find()
    ->authorId(1)
    ->all();
```

:::

### `before`

特定の日付より前に投稿されたエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'2018-04-01'` | 2018-04-01 より前に投稿されたもの。
| [DateTime](http://php.net/class.datetime) オブジェクト | オブジェクトで表される日付より前に投稿されたもの。

::: code

```twig
{# Fetch entries posted before this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set entries = craft.entries()
    .before(firstDayOfMonth)
    .all() %}
```

```php
// Fetch entries posted before this month
$firstDayOfMonth = new \DateTime('first day of this month');

$entries = \craft\elements\Entry::find()
    ->before($firstDayOfMonth)
    ->all();
```

:::

### `dateCreated`

エントリの作成日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に作成されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に作成されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に作成されたもの。

::: code

```twig
{# Fetch entries created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set entries = craft.entries()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch entries created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$entries = \craft\elements\Entry::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```

:::

### `dateUpdated`

エントリの最終アップデート日に基づいて、クエリの結果が絞り込まれます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降にアップデートされたもの。
| `'< 2018-05-01'` | 2018-05-01 より前にアップデートされたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間にアップデートされたもの。

::: code

```twig
{# Fetch entries updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set entries = craft.entries()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch entries updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$entries = \craft\elements\Entry::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```

:::

### `descendantDist`

[descendantOf](#descendantof) で指定されたエントリから特定の距離だけ離れているエントリのみに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch entries below this one #}
{% set entries = craft.entries()
    .descendantOf(myEntry)
    .descendantDist(3)
    .all() %}
```

```php
// Fetch entries below this one
$entries = \craft\elements\Entry::find()
    ->descendantOf($myEntry)
    ->descendantDist(3)
    ->all();
```

:::

### `descendantOf`

指定したエントリの子孫であるエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のカテゴリの下層。
| [Entry](api:craft\elements\Entry) オブジェクト | オブジェクトで表されるエントリの下層。

::: code

```twig
{# Fetch entries below this one #}
{% set entries = craft.entries()
    .descendantOf(myEntry)
    .all() %}
```

```php
// Fetch entries below this one
$entries = \craft\elements\Entry::find()
    ->descendantOf($myEntry)
    ->all();
```

:::

::: tip
どれだけ離れた子孫エントリを対象にするか制限したい場合、[descendantDist](#descendantdist) と組み合わせることができます。
:::

### `enabledForSite`

[site](#site) パラメータごとに、照会されているサイトでエントリが有効になっているかどうかに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `true` _（デフォルト）_ | サイト内で有効になっているもの。
| `false` | サイト内で有効かどうかに関係なく。

::: code

```twig
{# Fetch all entries, including ones disabled for this site #}
{% set entries = craft.entries()
    .enabledForSite(false)
    .all() %}
```

```php
// Fetch all entries, including ones disabled for this site
$entries = \craft\elements\Entry::find()
    ->enabledForSite(false)
    ->all();
```

:::

### `expiryDate`

エントリの有効期限日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `':empty:'` | 有効期限日を持たない。
| `':notempty:'` | 有効期限日を持つ。
| `'>= 2020-04-01'` | 2020-04-01 以降に有効期限が切れるもの。
| `'< 2020-05-01'` | 2020-05-01 より前に有効期限が切れるもの。
| `['and', '>= 2020-04-04', '< 2020-05-01']` | 2020-04-01 から 2020-05-01 の間に有効期限が切れるもの。

::: code

```twig
{# Fetch entries expiring this month #}
{% set nextMonth = date('first day of next month')|atom %}

{% set entries = craft.entries()
    .expiryDate("< #{nextMonth}")
    .all() %}
```

```php
// Fetch entries expiring this month
$nextMonth = (new \DateTime('first day of next month'))->format(\DateTime::ATOM);

$entries = \craft\elements\Entry::find()
    ->expiryDate("< {$nextMonth}")
    ->all();
```

:::

### `fixedOrder`

クエリの結果を [id](#id) で指定された順序で返します。

::: code

```twig
{# Fetch entries in a specific order #}
{% set entries = craft.entries()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch entries in a specific order
$entries = \craft\elements\Entry::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```

:::

### `hasDescendants`

エントリが子孫を持つかどうかに基づいて、クエリの結果を絞り込みます。

（これは [leaves](#leaves) の呼び出しと反対の効果を持っています。）

::: code

```twig
{# Fetch entries that have descendants #}
{% set entries = craft.entries()
    .hasDescendants()
    .all() %}
```

```php
// Fetch entries that have descendants
$entries = \craft\elements\Entry::find()
    ->hasDescendants()
    ->all();
```

:::

### `id`

エントリの ID に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1。
| `'not 1'` | ID が 1ではない。
| `[1, 2]` | ID が 1 または 2。
| `['not', 1, 2]` | ID が 1 または 2 ではない。

::: code

```twig
{# Fetch the entry by its ID #}
{% set entry = craft.entries()
    .id(1)
    .one() %}
```

```php
// Fetch the entry by its ID
$entry = \craft\elements\Entry::find()
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
{# Fetch entries in reverse #}
{% set entries = craft.entries()
    .inReverse()
    .all() %}
```

```php
// Fetch entries in reverse
$entries = \craft\elements\Entry::find()
    ->inReverse()
    ->all();
```

:::

### `leaves`

エントリが「leaves」（子孫のないエントリ）であるかどうかに基づいて、クエリの結果を絞り込みます。

（これは [hasDescendants](#hasdescendants) の呼び出しと反対の効果を持っています。）

::: code

```twig
{# Fetch entries that have no descendants #}
{% set entries = craft.entries()
    .leaves()
    .all() %}
```

```php
// Fetch entries that have no descendants
$entries = \craft\elements\Entry::find()
    ->leaves()
    ->all();
```

:::

### `level`

構造内のエントリのレベルに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | レベルが 1。
| `'not 1'` | レベルが 1 ではない。
| `'>= 3'` | レベルが 3 以上。
| `[1, 2]` | レベルが 1 または 2。
| `['not', 1, 2]` | レベルが 1 または 2 ではない。

::: code

```twig
{# Fetch entries positioned at level 3 or above #}
{% set entries = craft.entries()
    .level('>= 3')
    .all() %}
```

```php
// Fetch entries positioned at level 3 or above
$entries = \craft\elements\Entry::find()
    ->level('>= 3')
    ->all();
```

:::

### `limit`

返されるエントリの数を決定します。

::: code

```twig
{# Fetch up to 10 entries  #}
{% set entries = craft.entries()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 entries
$entries = \craft\elements\Entry::find()
    ->limit(10)
    ->all();
```

:::

### `nextSiblingOf`

指定したエントリの直後にあるエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のエントリの後。
| [Entry](api:craft\elements\Entry) オブジェクト | オブジェクトで表されるエントリの後。

::: code

```twig
{# Fetch the next entry #}
{% set entry = craft.entries()
    .nextSiblingOf(myEntry)
    .one() %}
```

```php
// Fetch the next entry
$entry = \craft\elements\Entry::find()
    ->nextSiblingOf($myEntry)
    ->one();
```

:::

### `offset`

結果からスキップされるエントリの数を決定します。

::: code

```twig
{# Fetch all entries except for the first 3 #}
{% set entries = craft.entries()
    .offset(3)
    .all() %}
```

```php
// Fetch all entries except for the first 3
$entries = \craft\elements\Entry::find()
    ->offset(3)
    ->all();
```

:::

### `orderBy`

返されるエントリの順序を決定します。

::: code

```twig
{# Fetch all entries in order of date created #}
{% set entries = craft.entries()
    .orderBy('dateCreated asc')
    .all() %}
```

```php
// Fetch all entries in order of date created
$entries = \craft\elements\Entry::find()
    ->orderBy('dateCreated asc')
    ->all();
```

:::

### `positionedAfter`

指定したエントリの後に位置するエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のエントリの後。
| [Entry](api:craft\elements\Entry) オブジェクト | オブジェクトで表されるエントリの後。

::: code

```twig
{# Fetch entries after this one #}
{% set entries = craft.entries()
    .positionedAfter(myEntry)
    .all() %}
```

```php
// Fetch entries after this one
$entries = \craft\elements\Entry::find()
    ->positionedAfter($myEntry)
    ->all();
```

:::

### `positionedBefore`

指定したエントリの前に位置するエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のエントリの前。
| [Entry](api:craft\elements\Entry) オブジェクト | オブジェクトで表されるエントリの前。

::: code

```twig
{# Fetch entries before this one #}
{% set entries = craft.entries()
    .positionedBefore(myEntry)
    .all() %}
```

```php
// Fetch entries before this one
$entries = \craft\elements\Entry::find()
    ->positionedBefore($myEntry)
    ->all();
```

:::

### `postDate`

エントリの投稿日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に投稿されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に投稿されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 と 2018-05-01 の間に投稿されたもの。

::: code

```twig
{# Fetch entries posted last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set entries = craft.entries()
    .postDate(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch entries posted last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$entries = \craft\elements\Entry::find()
    ->postDate(['and', ">= {$start}", "< {$end}"])
    ->all();
```

:::

### `prevSiblingOf`

指定したエントリの直前にあるエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のエントリの前。
| [Entry](api:craft\elements\Entry) オブジェクト | オブジェクトで表されるエントリの前。

::: code

```twig
{# Fetch the previous entry #}
{% set entry = craft.entries()
    .prevSiblingOf(myEntry)
    .one() %}
```

```php
// Fetch the previous entry
$entry = \craft\elements\Entry::find()
    ->prevSiblingOf($myEntry)
    ->one();
```

:::

### `relatedTo`

特定の他のエレメントと関連付けられたエントリだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[リレーション](https://docs.craftcms.com/v3/relations.html)を参照してください。

::: code

```twig
{# Fetch all entries that are related to myCategory #}
{% set entries = craft.entries()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all entries that are related to $myCategory
$entries = \craft\elements\Entry::find()
    ->relatedTo($myCategory)
    ->all();
```

:::

### `search`

検索クエリにマッチするエントリだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[検索](https://docs.craftcms.com/v3/searching.html)を参照してください。

::: code

```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{# Fetch all entries that match the search query #}
{% set entries = craft.entries()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all entries that match the search query
$entries = \craft\elements\Entry::find()
    ->search($searchQuery)
    ->all();
```

:::

### `section`

エントリが属するセクションに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'foo'` | ハンドルが `foo` のセクション内。
| `'not foo'` | ハンドルが `foo` のセクション内ではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar` のセクション内。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` のセクション内ではない。
| [Section](api:craft\models\Section) オブジェクト | オブジェクトで表されるセクション内。

::: code

```twig
{# Fetch entries in the Foo section #}
{% set entries = craft.entries()
    .section('foo')
    .all() %}
```

```php
// Fetch entries in the Foo section
$entries = \craft\elements\Entry::find()
    ->section('foo')
    ->all();
```

:::

### `sectionId`

セクションの ID ごとに、エントリが属するセクションに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のセクション内。
| `'not 1'` | ID が 1 のセクション内ではない。
| `[1, 2]` | ID が 1 または 2 のセクション内。
| `['not', 1, 2]` | ID が 1 または 2 のセクション内ではない。

::: code

```twig
{# Fetch entries in the section with an ID of 1 #}
{% set entries = craft.entries()
    .sectionId(1)
    .all() %}
```

```php
// Fetch entries in the section with an ID of 1
$entries = \craft\elements\Entry::find()
    ->sectionId(1)
    ->all();
```

:::

### `siblingOf`

指定したエントリの兄弟であるエントリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のエントリの横。
| [Entry](api:craft\elements\Entry) オブジェクト | オブジェクトで表されるエントリの横。

::: code

```twig
{# Fetch entries beside this one #}
{% set entries = craft.entries()
    .siblingOf(myEntry)
    .all() %}
```

```php
// Fetch entries beside this one
$entries = \craft\elements\Entry::find()
    ->siblingOf($myEntry)
    ->all();
```

:::

### `site`

エントリを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'foo'` | ハンドルが `foo` のサイトから。
| `\craft\elements\db\Site` オブジェクト | オブジェクトで表されるサイトから。

::: code

```twig
{# Fetch entries from the Foo site #}
{% set entries = craft.entries()
    .site('foo')
    .all() %}
```

```php
// Fetch entries from the Foo site
$entries = \craft\elements\Entry::find()
    ->site('foo')
    ->all();
```

:::

### `siteId`

サイトの ID ごとに、エントリを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

::: code

```twig
{# Fetch entries from the site with an ID of 1 #}
{% set entries = craft.entries()
    .siteId(1)
    .all() %}
```

```php
// Fetch entries from the site with an ID of 1
$entries = \craft\elements\Entry::find()
    ->siteId(1)
    ->all();
```

:::

### `slug`

エントリのスラグに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'foo'` | スラグが `foo`。
| `'foo*'` | スラグが `foo` ではじまる。
| `'*foo'` | スラグが `foo` で終わる。
| `'*foo*'` | スラグが `foo` を含む。
| `'not *foo*'` | スラグが `foo` を含まない。
| `['*foo*', '*bar*']` | スラグが `foo` または `bar` を含む。
| `['not', '*foo*', '*bar*']` | スラグが `foo` または `bar` を含まない。

::: code

```twig
{# Get the requested entry slug from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the entry with that slug #}
{% set entry = craft.entries()
    .slug(requestedSlug|literal)
    .one() %}
```

```php
// Get the requested entry slug from the URL
$requestedSlug = \Craft::$app->request->getSegment(3);

// Fetch the entry with that slug
$entry = \craft\elements\Entry::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```

:::

### `status`

エントリのステータスに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'live'` _（デフォルト）_ | 公開しているもの。
| `'pending'` | 保留しているもの（未来の投稿日がセットされた有効なもの）。
| `'expired'` | 期限切れのもの（過去の有効期限日がセットされた有効なもの）。
| `'disabled'` | 無効なもの。
| `['live', 'pending']` | live または pending のもの。

::: code

```twig
{# Fetch disabled entries #}
{% set entries = {twig-function}
    .status('disabled')
    .all() %}
```

```php
// Fetch disabled entries
$entries = \craft\elements\Entry::find()
    ->status('disabled')
    ->all();
```

:::

### `title`

エントリのタイトルに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
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
{# Fetch entries with a title that contains "Foo" #}
{% set entries = craft.entries()
    .title('*Foo*')
    .all() %}
```

```php
// Fetch entries with a title that contains "Foo"
$entries = \craft\elements\Entry::find()
    ->title('*Foo*')
    ->all();
```

:::

### `trashed`

ソフトデリートされたエントリだけに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch trashed entries #}
{% set entries = {twig-function}
    .trashed()
    .all() %}
```

```php
// Fetch trashed entries
$entries = \craft\elements\Entry::find()
    ->trashed()
    ->all();
```

:::

### `type`

エントリの入力タイプに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'foo'` | ハンドルが `foo` のタイプ。
| `'not foo'` | ハンドルが `foo` のタイプではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar` のタイプ。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` のタイプではない。
| [EntryType](api:craft\models\EntryType) オブジェクト | オブジェクトで表されるタイプ。

::: code

```twig
{# Fetch entries in the Foo section with a Bar entry type #}
{% set entries = craft.entries()
    .section('foo')
    .type('bar')
    .all() %}
```

```php
// Fetch entries in the Foo section with a Bar entry type
$entries = \craft\elements\Entry::find()
    ->section('foo')
    ->type('bar')
    ->all();
```

:::

### `typeId`

タイプの ID ごとに、エントリの入力タイプに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `1` | ID が 1 のタイプ。
| `'not 1'` | ID が 1 のタイプではない。
| `[1, 2]` | ID が 1 または 2 のタイプ。
| `['not', 1, 2]` | ID が 1 または 2 のタイプではない。

::: code

```twig
{# Fetch entries of the entry type with an ID of 1 #}
{% set entries = craft.entries()
    .typeId(1)
    .all() %}
```

```php
// Fetch entries of the entry type with an ID of 1
$entries = \craft\elements\Entry::find()
    ->typeId(1)
    ->all();
```

:::

### `uid`

エントリの UID に基づいて、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch the entry by its UID #}
{% set entry = craft.entries()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the entry by its UID
$entry = \craft\elements\Entry::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```

:::

### `uri`

エントリの URI に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエントリ
| - | -
| `'foo'` | URI が `foo`。
| `'foo*'` | URI が `foo` ではじまる。
| `'*foo'` | URI が `foo` で終わる。
| `'*foo*'` | URI が `foo` を含む。
| `'not *foo*'` | URI が `foo` を含まない。
| `['*foo*', '*bar*']` | URI が `foo` または `bar` を含む。
| `['not', '*foo*', '*bar*']` | URI が `foo` または `bar` を含まない。

::: code

```twig
{# Get the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{# Fetch the entry with that URI #}
{% set entry = craft.entries()
    .uri(requestedUri|literal)
    .one() %}
```

```php
// Get the requested URI
$requestedUri = \Craft::$app->request->getPathInfo();

// Fetch the entry with that URI
$entry = \craft\elements\Entry::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```

:::

### `with`

関連付けられたエレメントを eager-loaded した状態で、マッチしたエントリをクエリが返します。

このパラメーターがどのように機能するかの詳細については、[エレメントのEager-Loading](https://docs.craftcms.com/v3/dev/eager-loading-elements.html)を参照してください。

::: code

```twig
{# Fetch entries eager-loaded with the "Related" field’s relations #}
{% set entries = craft.entries()
    .with(['related'])
    .all() %}
```

```php
// Fetch entries eager-loaded with the "Related" field’s relations
$entries = \craft\elements\Entry::find()
    ->with(['related'])
    ->all();
```

:::

<!-- END PARAMS -->

