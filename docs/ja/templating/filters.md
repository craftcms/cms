# フィルタ

[Twig に付随する](http://twig.sensiolabs.org/doc/filters/index.html)テンプレートフィルタに加えて、Craft がいくつか独自のものを提供します。

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

利用可能な `numberOptions` は、[こちらのリスト](https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter#$numberFormatterOptions-detail)を参照してください。

利用可能な `textOptions` は、[こちらのリスト](https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter#$numberFormatterTextOptions-detail) を参照してください。

```twig
{{ 1000000|currency('USD') }} => $1,000,000.00
{{ 1000000|currency('USD', [], [], true) }} => $1,000,000
```

## `date`

Twig の [date](https://twig.symfony.com/doc/2.x/filters/date.html) フィルタと同様ですが、次の `format` 値を追加サポートしています。

- `'short'`
- `'medium'`（デフォルト）
- `'long'`
- `'full'`

これらのフォーマットが使用されると、日付は <api:craft\i18n\Formatter::asDate()> でローカライズされた日付の書式にフォーマットされます。

`translate` 引数も利用可能です。`true` を渡した場合、値を返す前にフォーマットされた日付へ <api:craft\helpers\DateTimeHelper::translateDate()> が実行されます。

```twig
{{ entry.postDate|date('short') }}
```

## `datetime`

[date](#date) フィルタと同様ですが、結果にはタイムスタンプも含まれます。

```twig
{{ entry.postDate|datetime('short') }}
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
 <p>There <em>is</em> an “i” in “team”!It’s at position {{ position + 1 }}.</p>
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

ヒント：類推できない方のために、[シシカバブ](http://en.wikipedia.org/wiki/Kebab#Shish)の参照です。

```twig
{{ "foo bar?"|kebab }}
{# Outputs: foo-bar #}
```

## `lcfirst`

文字列の最初の文字を小文字にします。

## `literal`

文字列に <api:craft\helpers\Db::escapeParam> を実行します。

## `markdown` または `md`

[Markdown](http://daringfireball.net/projects/markdown/) で文字列を処理します。

```twig
{% set content %}
# Everything You Need to Know About Computer Keyboards

The only *real* computer keyboard ever made was famously
the [Apple Extended Keyboard II] [1].
 
 [1]: http://www.flickr.com/photos/gruber/sets/72157604797968156/
{% endset %}

{{ content|markdown }}
```

## `multisort`

[ArrayHelper::multisort()](api:yii\helpers\BaseArrayHelper::multisort()) で配列をソートします。

## `number`

ユーザーが優先する言語に応じて、数値をフォーマットします。

グループシンボル（例えば、英語のコンマ）を省略したい場合は、オプションで `false` を渡すことができます。

```twig
{{ 1000000|number }} => 1,000,000
{{ 1000000|number(false) }} => 1000000
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
 LAST:currentUser.lastName
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

[time](#time) フィルタと同様ですが、日付よりも時間のためのものです。

```twig
{{ entry.postDate|time('short') }}
```

## `timestamp`

<api:craft\i18n\Formatter::asTimestamp()> 経由で、人が読めるタイムスタンプとして日付をフォーマットします。

## `translate` または `t`

[Craft::t()](api:yii\BaseYii::t()) でメッセージを翻訳します。カテゴリの指定がない場合、デフォルトで `site` になります。

```twig
{{ "Hello world"|t }}
```

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

