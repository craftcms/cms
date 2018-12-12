# フィルタ

[Twig に付随する](https://twig.symfony.com/doc/filters/index.html)テンプレートフィルタに加えて、Craft がいくつか独自のものを提供します。

## `atom`

（とりわけ、Atom フィードで使用される）ISO-8601 形式の日付を出力します。

```twig
{{ entry.postDate|atom }}
```

## `camel`

「camelCase」でフォーマットされた文字列を返します。

```twig
{{ "foo bar"|camel }}
{# Outputs: fooBar #}
```

## `column`

配列に [ArrayHelper::getColumn()](api:yii\helpers\BaseArrayHelper::getColumn()) を実行し、その結果を返します。

```twig
{% set entryIds = entries|column('id') %}
```

## `currency( currency, numberOptions, textOptions, stripZeroCents )`

ユーザーが優先する言語に応じて指定された通貨で、数値をフォーマットします。

最後の引数に `true` を渡すと、セントがゼロであれば「.00」が削除されます。

利用可能な `numberOptions` は、[こちらのリスト](api:yii\i18n\Formatter::$numberFormatterOptions)を参照してください。

利用可能な `textOptions` は、[こちらのリスト](api:yii\i18n\Formatter::$numberFormatterTextOptions) を参照してください。

```twig
{{ 1000000|currency('USD') }} → $1,000,000.00
{{ 1000000|currency('USD', [], [], true) }} → $1,000,000
```

## `date`

タイムスタンプ、または、[DateTime](http://php.net/manual/en/class.datetime.php) オブジェクトのフォーマットされた日付を出力します。

```twig
{{ entry.postDate|date }} → Sep 26, 2018
```

`format` パラメータに値を渡すことで、詳細がどの程度提供されるかをカスタマイズできます。

```twig
{{ entry.postDate|date('short') }} → 9/26/2018
```

利用可能な `format` 値は、次の通りです。

| フォーマット | 実例 |
| -------------------- | ----------------------------- |
| `short` | 9/26/2018 |
| `medium` _（デフォルト）_ | Sep 26, 2018 |
| `long` | September 26, 2018 |
| `full` | Wednesday, September 26, 2018 |

使用される正確な時刻のフォーマットは、現在のアプリケーションのローケルに依存します。異なるローケルの時刻のフォーマットを使用したい場合、`locale` パラメータを利用します。

```twig
{{ entry.postDate|date('short', locale='en-GB') }} → 26/9/2018
```

PHP の `date()` ファンクションでサポートされるものと同じ [フォーマットオプション](http://php.net/manual/en/function.date.php) を使用して、カスタムの日付フォーマットを渡すこともできます。

```twig
{{ entry.postDate|date('Y-m-d') }} → 2018-09-26
```

`timezone` パラメータを使用して、出力される時刻のタイムゾーンをカスタマイズできます。

```twig
{{ entry.postDate|date('short', timezone='UTC') }} → 9/27/2018
```

## `datetime`

タイムスタンプ、または、[DateTime](http://php.net/manual/en/class.datetime.php) オブジェクトのフォーマットされた（時刻を含む）日付を出力します。

```twig
{{ entry.postDate|datetime }} → Sep 26, 2018, 5:00:00 PM
```

`format` パラメータに値を渡すことで、詳細がどの程度提供されるかをカスタマイズできます。

```twig
{{ entry.postDate|datetime('short') }} → 9/26/2018, 5:00 PM
```

利用可能な `format` 値は、次の通りです。

| フォーマット | 実例 |
| -------------------- | ----------------------------------------------- |
| `short` | 9/26/2018, 5:00 PM |
| `medium` _（デフォルト）_ | Sep 26, 2018, 5:00:00 PM |
| `long` | September 26, 2018 at 5:00:00 PM PDT |
| `full` | Wednesday, September 26, 2018 at 5:00:00 PM PDT |

使用される正確な時刻のフォーマットは、現在のアプリケーションのローケルに依存します。異なるローケルの時刻のフォーマットを使用したい場合、`locale` パラメータを利用します。

```twig
{{ entry.postDate|datetime('short', locale='en-GB') }} → 26/9/2018, 17:00
```

`timezone` パラメータを使用して、出力される時刻のタイムゾーンをカスタマイズできます。

```twig
{{ entry.postDate|datetime('short', timezone='UTC') }} → 9/27/2018, 12:00 AM
```

## `duration`

[DateInterval](http://php.net/manual/en/class.dateinterval.php) オブジェクトに <api:craft\helpers\DateTimeHelper::humanDurationFromInterval()> を実行します。

```twig
<p>Posted {{ entry.postDate.diff(now)|duration(false) }} ago.</p>
```

## `encenc`

文字列を暗号化し、base64 エンコードします。

```twig
{{ "secure-string"|encenc }}
```

## `filesize`

バイト数をより良い何かにフォーマットします。

## `filter`

配列から空のエレメントを削除し、変更された配列を返します。

## `filterByValue`

配列に <api:craft\helpers\ArrayHelper::filterByValue()> を実行します。

## `group`

共通のプロパティに基づいて、配列の項目をグループ化します。

```twig
{% set allEntries = craft.entries.section('blog').all() %}
{% set allEntriesByYear = allEntries|group('postDate|date("Y")') %}

{% for year, entriesInYear in allEntriesByYear %}
    <h2>{{ year }}</h2>

    <ul>
        {% for entry in entriesInYear %}
            <li><a href="{{ entry.url }}">{{ entry.title }}</a></li>
        {% endfor %}
    </ul>
{% endfor %}
```

## `hash`

不正に変更されるべきではないフォームのデータを安全に渡すために、メッセージ認証コード（HMAC）の鍵付ハッシュを指定された文字列の先頭に追加します。

```twig
<input type="hidden" name="foo" value="{{ 'bar'|hash }}">
```

PHP スクリプトは、[Security::validateData()](api:yii\base\Security::validateData()) を経由して値を検証できます。

```php
$foo = craft()->request->getPost('foo');
$foo = craft()->security->validateData($foo);

if ($foo !== false) {
    // data is valid
}
```

## `id`

<api:craft\web\View::formatInputId()> を経由して、HTML の input 要素の `id` としてうまく動作するよう、文字列をフォーマットします。

```twig
{% set name = 'input[name]' %}
<input type="text" name="{{ name }}" id="{{ name|id }}">
```

## `index`

配列に [ArrayHelper::index()](api:yii\helpers\BaseArrayHelper::index()) を実行します。

```twig
{% set entries = entries|index('id') %}
```

## `indexOf`

配列内の渡された値のインデックス、または、他の文字列に含まれる渡された文字列のインデックスを返します。（返される位置は、0 からはじまることに注意してください。）見つからなかった場合、代わりに `-1` が返されます。

```twig
{% set colors = ['red', 'green', 'blue'] %}
<p>Green is located at position {{ colors|indexOf('green') + 1 }}.</p>

{% set position = "team"|indexOf('i') %}
{% if position != -1 %}
    <p>There <em>is</em> an “i” in “team”! It’s at position {{ position + 1 }}.</p>
{% endif %}
```

## `intersect`

渡された配列内にある値だけを含む配列を返します。

```twig
{% set ownedIngredients = [
    'vodka',
    'gin',
    'triple sec',
    'tonic',
    'grapefruit juice'
] %}

{% set longIslandIcedTeaIngredients = [
    'vodka',
    'tequila',
    'rum',
    'gin',
    'triple sec',
    'sweet and sour mix',
    'Coke'
] %}

{% set ownedLongIslandIcedTeaIngredients =
    ownedIngredients|intersect(longIslandIcedTeaIngredients)
%}
```

## `json_encode`

Twig の [json_encode](https://twig.symfony.com/doc/2.x/filters/json_encode.html) フィルタと同様ですが、引数 `options` がセットされておらず、レスポンスのコンテンツタイプが `text/html` または `application/xhtml+xml` の場合、デフォルトで `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT` になります。

## `kebab`

「kebab-case」でフォーマットされた文字列を返します。

ヒント：類推できない方のために、[シシカバブ](https://en.wikipedia.org/wiki/Kebab#Shish)の参照です。

```twig
{{ "foo bar?"|kebab }}
{# Outputs: foo-bar #}
```

## `lcfirst`

文字列の最初の文字を小文字にします。

## `literal`

文字列に <api:craft\helpers\Db::escapeParam> を実行します。

## `markdown` または `md`

[Markdown](https://daringfireball.net/projects/markdown/) で文字列を処理します。

```twig
{% set content %}
# Everything You Need to Know About Computer Keyboards

The only *real* computer keyboard ever made was famously
the [Apple Extended Keyboard II] [1].
    
    [1]: https://www.flickr.com/photos/gruber/sets/72157604797968156/
{% endset %}

{{ content|markdown }}
```

このフィルタは、2つの引数をサポートしています。

- `flavor` は、`'original'`（デフォルト値）、`'gfm'`（GitHub-Flavored Markdown）、`'gfm-comment'`（改行が`<br>`に変換された GFM）、 または、`'extra'`（Markdown Extra）にできます。
- `inlineOnly` は、`<p>` タグを除き、インライン要素だけを解析するかどうかを決定します。（デフォルトは `false`）

## `multisort`

[ArrayHelper::multisort()](api:yii\helpers\BaseArrayHelper::multisort()) で配列をソートします。

## `number`

ユーザーが優先する言語に応じて、数値をフォーマットします。

グループシンボル（例えば、英語のコンマ）を省略したい場合は、オプションで `false` を渡すことができます。

```twig
{{ 1000000|number }} → 1,000,000
{{ 1000000|number(false) }} → 1000000
```

## `parseRefs`

[リファレンスタグ](../reference-tags.md)の文字列を解析します。

```twig
{% set content %}
    {entry:blog/hello-world:link} was my first blog post. Pretty geeky, huh?
{% endset %}
    
{{ content|parseRefs|raw }}
```

## `pascal`

「PascalCase」（別名「UpperCamelCase」）でフォーマットされた文字列を返します。

```twig
{{ "foo bar"|pascal }}
{# Outputs: FooBar #}
```

## `percentage`

ユーザーが優先する言語に応じて、割合をフォーマットします。

## `replace`

文字列の一部を他のものに置き換えます。

ペアの検索 / 置換のオブジェクトを渡すことで、一度に複数のものを置き換えることができます。

```twig
{% set str = "Hello, FIRST LAST" %}

{{ str|replace({
    FIRST: currentUser.firstName,
    LAST:  currentUser.lastName
}) }}
```

または、一度に1つのものを置き換えることができます。

```twig
{% set str = "Hello, NAME" %}

{{ str|replace('NAME', currentUser.name) }}
```

置換文字列の値の最初と最後にスラッシュを付けてマッチするものを検索することで、正規表現も利用できます。

```twig
{{ tag.name|lower|replace('/[^\\w]+/', '-') }}
```

## `rss`

RSS フィードに必要な形式（`D, d M Y H:i:s O`）で日付を出力します。

```twig
{{ entry.postDate|rss }}
```

## `snake`

「snake_case」でフォーマットされた文字列を返します。

```twig
{{ "foo bar"|snake }}
{# Outputs: foo_bar #}
```

## `time`

タイムスタンプ、または、[DateTime](http://php.net/manual/en/class.datetime.php) オブジェクトのフォーマットされた時刻を出力します。

```twig
{{ entry.postDate|time }} → 10:00:00 AM
```

`format` パラメータに値を渡すことで、詳細がどの程度提供されるかをカスタマイズできます。

```twig
{{ entry.postDate|time('short') }} → 10:00 AM
```

利用可能な `format` 値は、次の通りです。

| フォーマット | 実例 |
| -------------------- | -------------- |
| `short` | 5:00 PM |
| `medium` _（デフォルト）_ | 5:00:00 PM |
| `long` | 5:00:00 PM PDT |

使用される正確な時刻のフォーマットは、現在のアプリケーションのローケルに依存します。異なるローケルの時刻のフォーマットを使用したい場合、`locale` パラメータを利用します。

```twig
{{ entry.postDate|time('short', locale='en-GB') }} → 17:00
```

`timezone` パラメータを使用して、出力される時刻のタイムゾーンをカスタマイズできます。

```twig
{{ entry.postDate|time('short', timezone='UTC') }} → 12:00 AM
```

## `timestamp`

<api:craft\i18n\Formatter::asTimestamp()> 経由で、人が読めるタイムスタンプとして日付をフォーマットします。

## `translate` または `t`

[Craft::t()](api:yii\BaseYii::t()) でメッセージを翻訳します。

```twig
{{ "Hello world"|t('myCategory') }}
```

カテゴリの指定がない場合、デフォルトで `site` になります。

```twig
{{ "Hello world"|t }}
```

::: tip
これがどのように機能するかの詳細については、[静的メッセージの翻訳](../static-translations.md)を参照してください。
:::

## `ucfirst`

文字列の最初の文字を大文字にします。

## `ucwords`

文字列に含まれるそれぞれの単語の最初の文字を大文字にします。

## `unique`

配列に [array_unique()](http://php.net/manual/en/function.array-unique.php) を実行します。

## `values`

指定された配列のすべての値の配列を返しますが、カスタムキーは除かれます。

```twig
{% set arr1 = {foo: "Foo", bar: "Bar"} %}
{% set arr2 = arr1|values %}
{# arr2 = ["Foo", "Bar"] #}
```

## `without`

指定されたエレメントを除いた配列を返します。

```twig
{% set entries = craft.entries.section('articles').limit(3).find %}
{% set firstEntry = entries[0] %}
{% set remainingEntries = entries|without(firstEntry) %}
```

