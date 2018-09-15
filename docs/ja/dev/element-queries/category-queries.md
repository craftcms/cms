# カテゴリクエリ

**カテゴリクエリ**を使用して、テンプレートや PHP コード内でカテゴリを取得できます。

::: code

```twig
{# Create a new category query #}
{% set myCategoryQuery = craft.categories() %}
```

```php
// Create a new category query
$myCategoryQuery = \craft\elements\Category::find();
```

:::

カテゴリクエリを作成すると、結果を絞り込むための[パラメータ](#parameters)をセットできます。さらに、`.all()` を呼び出して[実行](README.md#executing-element-queries)できます。[Category](api:craft\elements\Category) オブジェクトの配列が返されます。

::: tip
エレメントクエリがどのように機能するかについては、[エレメントクエリについて](README.md)を参照してください。
:::

## 実例

次の操作を行うことで、「Topics」カテゴリグループに含まれるすべてのカテゴリのナビゲーションを表示できます。

1. `craft.categories()` でカテゴリクエリを作成します。
2. [group](#group) パラメータをセットします。
3. `.all()` でカテゴリを取得します。
4. [nav](../tags/nav.md) タグを使用してカテゴリをループ処理し、ナビゲーションの HTML を作成します。

```twig
{# Create a category query with the 'group' parameter #}
{% set myCategoryQuery = craft.categories()
    .group('topics') %}

{# Fetch the categories #}
{% set categories = myCategoryQuery.all() %}

{# Display the navigation #}
<ul>
    {% nav category in categories %}
        <li>
            <a href="{{ category.url }}">{{ category.title }}</a>
            {% ifchildren %}
                <ul>
                    {% children %}
                </ul>
            {% endifchildren %}
        </li>
    {% endnav %}
</ul>
```

## パラメータ

カテゴリクエリは、次のパラメータをサポートしています。

<!-- BEGIN PARAMS -->

### `ancestorDist`

[ancestorOf](#ancestorof) で指定されたカテゴリから特定の距離だけ離れているカテゴリのみに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch categories above this one #}
{% set categories = craft.categories()
    .ancestorOf(myCategory)
    .ancestorDist(3)
    .all() %}
```

```php
// Fetch categories above this one
$categories = \craft\elements\Category::find()
    ->ancestorOf($myCategory)
    ->ancestorDist(3)
    ->all();
```

:::

### `ancestorOf`

指定したカテゴリの先祖であるカテゴリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1 のカテゴリの上層。
| [Category](api:craft\elements\Category) オブジェクト | オブジェクトで表されるカテゴリの上層。

::: code

```twig
{# Fetch categories above this one #}
{% set categories = craft.categories()
    .ancestorOf(myCategory)
    .all() %}
```

```php
// Fetch categories above this one
$categories = \craft\elements\Category::find()
    ->ancestorOf($myCategory)
    ->all();
```

:::

::: tip
どれだけ離れた先祖カテゴリを対象にするか制限したい場合、[ancestorDist](#ancestordist) と組み合わせることができます。
:::

### `anyStatus`

[status](#status) および [enabledForSite](#enabledforsite) パラメータをクリアします。

::: code

```twig
{# Fetch all categories, regardless of status #}
{% set categories = craft.categories()
    .anyStatus()
    .all() %}
```

```php
// Fetch all categories, regardless of status
$categories = \craft\elements\Category::find()
    ->anyStatus()
    ->all();
```

:::

### `asArray`

[Category](api:craft\elements\Category) オブジェクトではなく、データの配列として、マッチしたカテゴリをクエリが返します。

::: code

```twig
{# Fetch categories as arrays #}
{% set categories = craft.categories()
    .asArray()
    .all() %}
```

```php
// Fetch categories as arrays
$categories = \craft\elements\Category::find()
    ->asArray()
    ->all();
```

:::

### `dateCreated`

カテゴリの作成日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に作成されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に作成されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に作成されたもの。

::: code

```twig
{# Fetch categories created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set categories = craft.categories()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch categories created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$categories = \craft\elements\Category::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```

:::

### `dateUpdated`

カテゴリの最終アップデート日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降にアップデートされたもの。
| `'< 2018-05-01'` | 2018-05-01 より前にアップデートされたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間にアップデートされたもの。

::: code

```twig
{# Fetch categories updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set categories = craft.categories()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch categories updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$categories = \craft\elements\Category::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```

:::

### `descendantDist`

[descendantOf](#descendantof) で指定されたカテゴリから特定の距離だけ離れているカテゴリのみに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch categories below this one #}
{% set categories = craft.categories()
    .descendantOf(myCategory)
    .descendantDist(3)
    .all() %}
```

```php
// Fetch categories below this one
$categories = \craft\elements\Category::find()
    ->descendantOf($myCategory)
    ->descendantDist(3)
    ->all();
```

:::

### `descendantOf`

指定したカテゴリの子孫であるカテゴリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1 のカテゴリの下層。
| [Category](api:craft\elements\Category) オブジェクト | オブジェクトで表されるカテゴリの下層。

::: code

```twig
{# Fetch categories below this one #}
{% set categories = craft.categories()
    .descendantOf(myCategory)
    .all() %}
```

```php
// Fetch categories below this one
$categories = \craft\elements\Category::find()
    ->descendantOf($myCategory)
    ->all();
```

:::

::: tip
どれだけ離れた子孫カテゴリを対象にするか制限したい場合、[descendantDist](#descendantdist) と組み合わせることができます。
:::

### `enabledForSite`

[site](#site) パラメータごとに、照会されているサイトでカテゴリが有効になっているかどうかに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `true` _（デフォルト）_ | サイト内で有効になっているもの。
| `false` | サイト内で有効かどうかに関係なく。

::: code

```twig
{# Fetch all categories, including ones disabled for this site #}
{% set categories = craft.categories()
    .enabledForSite(false)
    .all() %}
```

```php
// Fetch all categories, including ones disabled for this site
$categories = \craft\elements\Category::find()
    ->enabledForSite(false)
    ->all();
```

:::

### `fixedOrder`

クエリの結果を [id](#id) で指定された順序で返します。

::: code

```twig
{# Fetch categories in a specific order #}
{% set categories = craft.categories()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch categories in a specific order
$categories = \craft\elements\Category::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```

:::

### `group`

カテゴリが属するカテゴリグループに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'foo'` | ハンドルが `foo` のグループ内。
| `'not foo'` | ハンドルが `foo` のグループ内ではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar` のグループ内。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` のグループ内ではない。
| [CategoryGroup](api:craft\models\CategoryGroup) オブジェクト | オブジェクトで表されるグループ内。

::: code

```twig
{# Fetch categories in the Foo group #}
{% set categories = craft.categories()
    .group('foo')
    .all() %}
```

```php
// Fetch categories in the Foo group
$categories = \craft\elements\Category::find()
    ->group('foo')
    ->all();
```

:::

### `groupId`

グループの ID ごとに、カテゴリが属するカテゴリグループに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1 のグループ内。
| `'not 1'` | ID が 1 のグループ内ではない。
| `[1, 2]` | ID が 1 または 2 のグループ内。
| `['not', 1, 2]` | ID が 1 または 2 のグループ内ではない。

::: code

```twig
{# Fetch categories in the group with an ID of 1 #}
{% set categories = craft.categories()
    .groupId(1)
    .all() %}
```

```php
// Fetch categories in the group with an ID of 1
$categories = \craft\elements\Category::find()
    ->groupId(1)
    ->all();
```

:::

### `hasDescendants`

カテゴリが子孫を持つかどうかに基づいて、クエリの結果を絞り込みます。

（これは [leaves](#leaves) の呼び出しと反対の効果を持っています。）

::: code

```twig
{# Fetch categories that have descendants #}
{% set categories = craft.categories()
    .hasDescendants()
    .all() %}
```

```php
// Fetch categories that have descendants
$categories = \craft\elements\Category::find()
    ->hasDescendants()
    ->all();
```

:::

### `id`

カテゴリの ID に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1。
| `'not 1'` | ID が 1ではない。
| `[1, 2]` | ID が 1 または 2。
| `['not', 1, 2]` | ID が 1 または 2 ではない。

::: code

```twig
{# Fetch the category by its ID #}
{% set category = craft.categories()
    .id(1)
    .one() %}
```

```php
// Fetch the category by its ID
$category = \craft\elements\Category::find()
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
{# Fetch categories in reverse #}
{% set categories = craft.categories()
    .inReverse()
    .all() %}
```

```php
// Fetch categories in reverse
$categories = \craft\elements\Category::find()
    ->inReverse()
    ->all();
```

:::

### `leaves`

カテゴリが「leaves」（子孫のないカテゴリ）であるかどうかに基づいて、クエリの結果を絞り込みます。

（これは [hasDescendants](#hasdescendants) の呼び出しと反対の効果を持っています。）

::: code

```twig
{# Fetch categories that have no descendants #}
{% set categories = craft.categories()
    .leaves()
    .all() %}
```

```php
// Fetch categories that have no descendants
$categories = \craft\elements\Category::find()
    ->leaves()
    ->all();
```

:::

### `level`

構造内のカテゴリのレベルに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | レベルが 1。
| `'not 1'` | レベルが 1 ではない。
| `'>= 3'` | レベルが 3 以上。
| `[1, 2]` | レベルが 1 または 2。
| `['not', 1, 2]` | レベルが 1 または 2 ではない。

::: code

```twig
{# Fetch categories positioned at level 3 or above #}
{% set categories = craft.categories()
    .level('>= 3')
    .all() %}
```

```php
// Fetch categories positioned at level 3 or above
$categories = \craft\elements\Category::find()
    ->level('>= 3')
    ->all();
```

:::

### `limit`

返されるカテゴリの数を決定します。

::: code

```twig
{# Fetch up to 10 categories  #}
{% set categories = craft.categories()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 categories
$categories = \craft\elements\Category::find()
    ->limit(10)
    ->all();
```

:::

### `nextSiblingOf`

指定したカテゴリの直後にあるカテゴリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1 のカテゴリの後。
| [Category](api:craft\elements\Category) オブジェクト | オブジェクトで表されるカテゴリの後。

::: code

```twig
{# Fetch the next category #}
{% set category = craft.categories()
    .nextSiblingOf(myCategory)
    .one() %}
```

```php
// Fetch the next category
$category = \craft\elements\Category::find()
    ->nextSiblingOf($myCategory)
    ->one();
```

:::

### `offset`

結果からスキップされるカテゴリの数を決定します。

::: code

```twig
{# Fetch all categories except for the first 3 #}
{% set categories = craft.categories()
    .offset(3)
    .all() %}
```

```php
// Fetch all categories except for the first 3
$categories = \craft\elements\Category::find()
    ->offset(3)
    ->all();
```

:::

### `orderBy`

返されるカテゴリの順序を決定します。

::: code

```twig
{# Fetch all categories in order of date created #}
{% set categories = craft.categories()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php
// Fetch all categories in order of date created
$categories = \craft\elements\Category::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```

:::

### `positionedAfter`

指定したカテゴリの後に位置するカテゴリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1 のカテゴリの後。
| [Category](api:craft\elements\Category) オブジェクト | オブジェクトで表されるカテゴリの後。

::: code

```twig
{# Fetch categories after this one #}
{% set categories = craft.categories()
    .positionedAfter(myCategory)
    .all() %}
```

```php
// Fetch categories after this one
$categories = \craft\elements\Category::find()
    ->positionedAfter($myCategory)
    ->all();
```

:::

### `positionedBefore`

指定したカテゴリの前に位置するカテゴリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| Value | Fetches categories…
| - | -
| `1` | before the category with an ID of 1.
| [Category](api:craft\elements\Category) オブジェクト | オブジェクトで表されるカテゴリの前。

::: code

```twig
{# Fetch categories before this one #}
{% set categories = craft.categories()
    .positionedBefore(myCategory)
    .all() %}
```

```php
// Fetch categories before this one
$categories = \craft\elements\Category::find()
    ->positionedBefore($myCategory)
    ->all();
```

:::

### `prevSiblingOf`

指定したカテゴリの直前にあるカテゴリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1 のカテゴリの前。
| [Category](api:craft\elements\Category) オブジェクト | オブジェクトで表されるカテゴリの前。

::: code

```twig
{# Fetch the previous category #}
{% set category = craft.categories()
    .prevSiblingOf(myCategory)
    .one() %}
```

```php
// Fetch the previous category
$category = \craft\elements\Category::find()
    ->prevSiblingOf($myCategory)
    ->one();
```

:::

### `relatedTo`

特定の他のエレメントと関連付けられたカテゴリだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[リレーション](../../relations.html)を参照してください。

::: code

```twig
{# Fetch all categories that are related to myCategory #}
{% set categories = craft.categories()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all categories that are related to $myCategory
$categories = \craft\elements\Category::find()
    ->relatedTo($myCategory)
    ->all();
```

:::

### `search`

検索クエリにマッチするカテゴリだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[検索](../../searching.html)を参照してください。

::: code

```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all categories that match the search query #}
{% set categories = craft.categories()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all categories that match the search query
$categories = \craft\elements\Category::find()
    ->search($searchQuery)
    ->all();
```

:::

### `siblingOf`

指定したカテゴリの兄弟であるカテゴリだけに、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `1` | ID が 1 のカテゴリの横。
| [Category](api:craft\elements\Category) オブジェクト | オブジェクトで表されるカテゴリの横。

::: code

```twig
{# Fetch categories beside this one #}
{% set categories = craft.categories()
    .siblingOf(myCategory)
    .all() %}
```

```php
// Fetch categories beside this one
$categories = \craft\elements\Category::find()
    ->siblingOf($myCategory)
    ->all();
```

:::

### `site`

カテゴリを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'foo'` | ハンドルが `foo` のサイトから。
| `\craft\elements\db\Site` オブジェクト | オブジェクトで表されるサイトから。

::: code

```twig
{# Fetch categories from the Foo site #}
{% set categories = craft.categories()
    .site('foo')
    .all() %}
```

```php
// Fetch categories from the Foo site
$categories = \craft\elements\Category::find()
    ->site('foo')
    ->all();
```

:::

### `siteId`

サイトの ID ごとに、カテゴリを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

::: code

```twig
{# Fetch categories from the site with an ID of 1 #}
{% set categories = craft.categories()
    .siteId(1)
    .all() %}
```

```php
// Fetch categories from the site with an ID of 1
$categories = \craft\elements\Category::find()
    ->siteId(1)
    ->all();
```

:::

### `slug`

カテゴリのスラグに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'foo'` | スラグが `foo`。
| `'foo*'` | スラグが `foo` ではじまる。
| `'*foo'` | スラグが `foo` で終わる。
| `'*foo*'` | スラグが `foo` を含む。
| `'not *foo*'` | スラグが `foo` を含まない。
| `['*foo*', '*bar*'` | スラグが `foo` または `bar` を含む。
| `['not', '*foo*', '*bar*']` | スラグが `foo` または `bar` を含まない。

::: code

```twig
{# Get the requested category slug from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the category with that slug #}
{% set category = craft.categories()
    .slug(requestedSlug|literal)
    .one() %}
```

```php
// Get the requested category slug from the URL
$requestedSlug = \Craft::$app->request->getSegment(3);

// Fetch the category with that slug
$category = \craft\elements\Category::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```

:::

### `status`

カテゴリのステータスに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'enabled'`  _（デフォルト）_ | 有効になっているもの。
| `'disabled'` | 無効になっているもの。

::: code

```twig
{# Fetch disabled categories #}
{% set categories = craft.categories()
    .status('disabled')
    .all() %}
```

```php
// Fetch disabled categories
$categories = \craft\elements\Category::find()
    ->status('disabled')
    ->all();
```

:::

### `title`

カテゴリのタイトルに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'Foo'` | タイトルが `Foo`。
| `'Foo*'` | タイトルが `Foo` ではじまる。
| `'*Foo'` | タイトルが `Foo` で終わる。
| `'*Foo*'` | タイトルが `Foo` を含む。
| `'not *Foo*'` | タイトルが `Foo` を含まない。
| `['*Foo*', '*Bar*'` | タイトルが `Foo` または `Bar` を含む。
| `['not', '*Foo*', '*Bar*']` | タイトルが `Foo` または `Bar` を含まない。

::: code

```twig
{# Fetch categories with a title that contains "Foo" #}
{% set categories = craft.categories()
    .title('*Foo*')
    .all() %}
```

```php
// Fetch categories with a title that contains "Foo"
$categories = \craft\elements\Category::find()
    ->title('*Foo*')
    ->all();
```

:::

### `uid`

カテゴリの UID に基づいて、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch the category by its UID #}
{% set category = craft.categories()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the category by its UID
$category = \craft\elements\Category::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```

:::

### `uri`

カテゴリの URI に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するカテゴリ
| - | -
| `'foo'` | URI が `foo`。
| `'foo*'` | URI が `foo` ではじまる。
| `'*foo'` | URI が `foo` で終わる。
| `'*foo*'` | URI が `foo` を含む。
| `'not *foo*'` | URI が `foo` を含まない。
| `['*foo*', '*bar*'` | URI が `foo` または `bar` を含む。
| `['not', '*foo*', '*bar*']` | URI が `foo` または `bar` を含まない。

::: code

```twig
{# Get the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{# Fetch the category with that URI #}
{% set category = craft.categories()
    .uri(requestedUri|literal)
    .one() %}
```

```php
// Get the requested URI
$requestedUri = \Craft::$app->request->getPathInfo();

// Fetch the category with that URI
$category = \craft\elements\Category::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```

:::

### `with`

関連付けられたエレメントを eager-loaded した状態で、マッチしたカテゴリをクエリが返します。

このパラメーターがどのように機能するかの詳細については、[エレメントのEager-Loading](../eager-loading-elements.html)を参照してください。

::: code

```twig
{# Fetch categories eager-loaded with the "Related" field’s relations #}
{% set categories = craft.categories()
    .with(['related'])
    .all() %}
```

```php
// Fetch categories eager-loaded with the "Related" field’s relations
$categories = \craft\elements\Category::find()
    ->with(['related'])
    ->all();
```

:::

<!-- END PARAMS -->

