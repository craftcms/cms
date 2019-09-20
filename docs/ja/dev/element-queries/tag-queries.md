# タグクエリ

**タグクエリ**を使用して、テンプレートや PHP コード内でタグを取得できます。

::: code

```twig
{# Create a new tag query #}
{% set myTagQuery = craft.tags() %}
```

```php
// Create a new tag query
$myTagQuery = \craft\elements\Tag::find();
```

:::

タグクエリを作成すると、結果を絞り込むための[パラメータ](#parameters)をセットできます。さらに、`.all()` を呼び出して[実行](README.md#executing-element-queries)できます。[Tag](api:craft\elements\Tag) オブジェクトの配列が返されます。

::: tip
エレメントクエリがどのように機能するかについては、[エレメントクエリについて](README.md)を参照してください。
:::

## 実例

次の操作を行うことで、「Blog Tags」タググループに含まれるタグのリストを表示できます。

1. `craft.tags()` でタグクエリを作成します。
2. [group](#group) パラメータをセットします。
3. `.all()` でタグを取得します。
4. [for](https://twig.symfony.com/doc/2.x/tags/for.html) タグを使用してタグをループ処理し、リストの HTML を作成します。

```twig
{# Create a tag query with the 'group' parameter #}
{% set myTagQuery = craft.tags()
    .group('blogTags') %}

{# Fetch the tags #}
{% set tags = myTagQuery.all() %}

{# Display the tag list #}
<ul>
    {% for tag in tags %}
        <li><a href="{{ url('blog/tags/'~tag.id) }}">{{ tag.title }}</a></li>
    {% endfor %}
</ul>
```

## パラメータ

タグクエリは、次のパラメータをサポートしています。

<!-- BEGIN PARAMS -->

### `anyStatus`

[status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) および [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) パラメータをクリアします。

::: code

```twig
{# Fetch all tags, regardless of status #}
{% set tags = craft.tags()
    .anyStatus()
    .all() %}
```

```php
// Fetch all tags, regardless of status
$tags = \craft\elements\Tag::find()
    ->anyStatus()
    ->all();
```

:::

### `asArray`

[Tag](api:craft\elements\Tag) オブジェクトではなく、データの配列として、マッチしたタグをクエリが返します。

::: code

```twig
{# Fetch tags as arrays #}
{% set tags = craft.tags()
    .asArray()
    .all() %}
```

```php
// Fetch tags as arrays
$tags = \craft\elements\Tag::find()
    ->asArray()
    ->all();
```

:::

### `dateCreated`

タグの作成日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降に作成されたもの。
| `'< 2018-05-01'` | 2018-05-01 より前に作成されたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に作成されたもの。

::: code

```twig
{# Fetch tags created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set tags = craft.tags()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch tags created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$tags = \craft\elements\Tag::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```

:::

### `dateUpdated`

タグの最終アップデート日に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
| - | -
| `'>= 2018-04-01'` | 2018-04-01 以降にアップデートされたもの。
| `'< 2018-05-01'` | 2018-05-01 より前にアップデートされたもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間にアップデートされたもの。

::: code

```twig
{# Fetch tags updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set tags = craft.tags()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch tags updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$tags = \craft\elements\Tag::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```

:::

### `fixedOrder`

クエリの結果を [id](#id) で指定された順序で返します。

::: code

```twig
{# Fetch tags in a specific order #}
{% set tags = craft.tags()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch tags in a specific order
$tags = \craft\elements\Tag::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```

:::

### `group`

タグが属するタググループに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
| - | -
| `'foo'` | ハンドルが `foo` のグループ内。
| `'not foo'` | ハンドルが `foo` のグループ内ではない。
| `['foo', 'bar']` | ハンドルが `foo` または `bar` のグループ内。
| `['not', 'foo', 'bar']` | ハンドルが `foo` または `bar` のグループ内ではない。
| [TagGroup](api:craft\models\TagGroup) オブジェクト | オブジェクトで表されるグループ内。

::: code

```twig
{# Fetch tags in the Foo group #}
{% set tags = craft.tags()
    .group('foo')
    .all() %}
```

```php
// Fetch tags in the Foo group
$tags = \craft\elements\Tag::find()
    ->group('foo')
    ->all();
```

:::

### `groupId`

グループの ID ごとに、タグが属するタググループに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
| - | -
| `1` | ID が 1 のグループ内。
| `'not 1'` | ID が 1 のグループ内ではない。
| `[1, 2]` | ID が 1 または 2 のグループ内。
| `['not', 1, 2]` | ID が 1 または 2 のグループ内ではない。

::: code

```twig
{# Fetch tags in the group with an ID of 1 #}
{% set tags = craft.tags()
    .groupId(1)
    .all() %}
```

```php
// Fetch tags in the group with an ID of 1
$tags = \craft\elements\Tag::find()
    ->groupId(1)
    ->all();
```

:::

### `id`

タグの ID に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
| - | -
| `1` | ID が 1。
| `'not 1'` | ID が 1ではない。
| `[1, 2]` | ID が 1 または 2。
| `['not', 1, 2]` | ID が 1 または 2 ではない。

::: code

```twig
{# Fetch the tag by its ID #}
{% set tag = craft.tags()
    .id(1)
    .one() %}
```

```php
// Fetch the tag by its ID
$tag = \craft\elements\Tag::find()
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
{# Fetch tags in reverse #}
{% set tags = craft.tags()
    .inReverse()
    .all() %}
```

```php
// Fetch tags in reverse
$tags = \craft\elements\Tag::find()
    ->inReverse()
    ->all();
```

:::

### `limit`

返されるタグの数を決定します。

::: code

```twig
{# Fetch up to 10 tags  #}
{% set tags = craft.tags()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 tags
$tags = \craft\elements\Tag::find()
    ->limit(10)
    ->all();
```

:::

### `offset`

結果からスキップされるタグの数を決定します。

::: code

```twig
{# Fetch all tags except for the first 3 #}
{% set tags = craft.tags()
    .offset(3)
    .all() %}
```

```php
// Fetch all tags except for the first 3
$tags = \craft\elements\Tag::find()
    ->offset(3)
    ->all();
```

:::

### `orderBy`

返されるタグの順序を決定します。

::: code

```twig
{# Fetch all tags in order of date created #}
{% set tags = craft.tags()
    .orderBy('dateCreated asc')
    .all() %}
```

```php
// Fetch all tags in order of date created
$tags = \craft\elements\Tag::find()
    ->orderBy('dateCreated asc')
    ->all();
```

:::

### `relatedTo`

特定の他のエレメントと関連付けられたタグだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[リレーション](https://docs.craftcms.com/v3/relations.html)を参照してください。

::: code

```twig
{# Fetch all tags that are related to myCategory #}
{% set tags = craft.tags()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all tags that are related to $myCategory
$tags = \craft\elements\Tag::find()
    ->relatedTo($myCategory)
    ->all();
```

:::

### `search`

検索クエリにマッチするタグだけに、クエリの結果を絞り込みます。

このパラメーターがどのように機能するかの詳細については、[検索](https://docs.craftcms.com/v3/searching.html)を参照してください。

::: code

```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{# Fetch all tags that match the search query #}
{% set tags = craft.tags()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all tags that match the search query
$tags = \craft\elements\Tag::find()
    ->search($searchQuery)
    ->all();
```

:::

### `site`

タグを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
| - | -
| `'foo'` | ハンドルが `foo` のサイトから。
| `\craft\elements\db\Site` オブジェクト | オブジェクトで表されるサイトから。

::: code

```twig
{# Fetch tags from the Foo site #}
{% set tags = craft.tags()
    .site('foo')
    .all() %}
```

```php
// Fetch tags from the Foo site
$tags = \craft\elements\Tag::find()
    ->site('foo')
    ->all();
```

:::

### `siteId`

サイトの ID ごとに、タグを照会するサイトを決定します。

デフォルトでは、現在のサイトが使用されます。

::: code

```twig
{# Fetch tags from the site with an ID of 1 #}
{% set tags = craft.tags()
    .siteId(1)
    .all() %}
```

```php
// Fetch tags from the site with an ID of 1
$tags = \craft\elements\Tag::find()
    ->siteId(1)
    ->all();
```

:::

### `title`

タグのタイトルに基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
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
{# Fetch tags with a title that contains "Foo" #}
{% set tags = craft.tags()
    .title('*Foo*')
    .all() %}
```

```php
// Fetch tags with a title that contains "Foo"
$tags = \craft\elements\Tag::find()
    ->title('*Foo*')
    ->all();
```

:::

### `trashed`

ソフトデリートされたタグだけに、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch trashed tags #}
{% set tags = {twig-function}
    .trashed()
    .all() %}
```

```php
// Fetch trashed tags
$tags = \craft\elements\Tag::find()
    ->trashed()
    ->all();
```

:::

### `uid`

タグの UID に基づいて、クエリの結果を絞り込みます。

::: code

```twig
{# Fetch the tag by its UID #}
{% set tag = craft.tags()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the tag by its UID
$tag = \craft\elements\Tag::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```

:::

### `uri`

タグの URI に基づいて、クエリの結果を絞り込みます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するタグ
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

{# Fetch the tag with that URI #}
{% set tag = craft.tags()
    .uri(requestedUri|literal)
    .one() %}
```

```php
// Get the requested URI
$requestedUri = \Craft::$app->request->getPathInfo();

// Fetch the tag with that URI
$tag = \craft\elements\Tag::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```

:::

### `with`

関連付けられたエレメントを eager-loaded した状態で、マッチしたタグをクエリが返します。

このパラメーターがどのように機能するかの詳細については、[エレメントのEager-Loading](https://docs.craftcms.com/v3/dev/eager-loading-elements.html)を参照してください。

::: code

```twig
{# Fetch tags eager-loaded with the "Related" field’s relations #}
{% set tags = craft.tags()
    .with(['related'])
    .all() %}
```

```php
// Fetch tags eager-loaded with the "Related" field’s relations
$tags = \craft\elements\Tag::find()
    ->with(['related'])
    ->all();
```

:::

<!-- END PARAMS -->

