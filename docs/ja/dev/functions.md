# ファンクション

[Twig に付随する](https://twig.symfony.com/doc/functions/index.html)テンプレートファンクションに加えて、Craft がいくつか独自のものを提供します。

## `alias( string )`

その文字列が[エイリアス](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases)ではじまるかをチェックする [Craft::getAlias()](api:yii\BaseYii::getAlias()) に、文字列を渡します。（詳細については、[コンフィギュレーション](../config/README.md#aliases)を参照してください。）

```twig
<img src="{{ alias('@assetBaseUrl/images/logo.png') }}">
```

## `beginBody()`

「begin body」に登録されたスクリプトやスタイルを出力します。`<body>` タグの直後に配置する必要があります。

```twig
<body>
    {{ beginBody() }}

    <h1>{{ page.name }}</h1>
    {{ page.body }}
</body>
```

## `ceil( num )`

整数値に切り上げます。

```twig
{{ ceil(42.1) }} → 43
```

## `className( object )`

指定されたオブジェクトの完全修飾クラス名を返します。

## `clone( object )`

指定されたオブジェクトのクローンを作成します。

```twig
{% set query = craft.entries.section('news') %}
{% set articles = clone(query).type('articles') %}
```

## `csrfInput()`

不可視の CSRF トークン入力欄を返します。CSRF 保護が有効になっているすべてのサイトでは、POST 経由で送信するそれぞれのフォームにこれを含めなければなりません。

```twig
<form method="post">
    {{ csrfInput() }}
    <!-- ... -->
</form>
```

## `endBody()`

「end body」に登録されたスクリプトやスタイルを出力します。`</body>` タグの直前に配置する必要があります。

```twig
<body>
    <h1>{{ page.name }}</h1>
    {{ page.body }}

    {{ endBody() }}
</body>
```

## `floor( num )`

整数値に切り捨てます。

```twig
{{ floor(42.9) }} → 42
```

## `getenv( name )`

環境変数の値を返します。

```twig
{{ getenv('MAPS_API_KEY') }}
```

## `head()`

「head」に登録されたスクリプトやスタイルを出力します。`</head>` タグの直前に配置する必要があります。

```twig
<head>
    <title>{{ siteName }}</title>
    {{ head() }}
</head>
```

## `redirectInput( url )`

`<input type="hidden" name="redirect" value="{{ url|hash }}">` を入力するためのショートカットです。

## `round( num )`

最も近い整数値に数を丸めます。

```twig
{{ round(42.1) }} → 42
{{ round(42.9) }} → 43
```

## `seq( name, length, next )`

`name` で定義されたシーケンスの次または現在の番号を出力します。

```twig
<p>This entry has been read {{ seq('hits:' ~ entry.id) }} times.</p>
```

ファンクションが呼び出されるたびに、与えられたシーケンスは自動的にインクリメントされます。

オプションで特定の長さにゼロ詰めした数値にすることができます。

```twig
{{ now|date('Y') ~ '-' ~ seq('orderNumber:' ~ now|date('Y'), 5) }}
{# outputs: 2018-00001 #}
```

インクリメントせずにシーケンスの現在の数字を表示するには、`next` 引数に `false` をセットします。

```twig
<h5><a href="{{ entry.url }}">{{ entry.title }}</a></h5>
<p>{{ seq('hits:' ~ entry.id, next=false) }} views</p>
```

## `shuffle( array )`

配列内のエレメントの順序をランダム化します。

```twig
{% set promos = shuffle(homepage.promos) %}

{% for promo in promos %}
    <div class="promo {{ promo.slug }}">
        <h3>{{ promo.title }}</h3>
        <p>{{ promo.description }}</p>
        <a class="cta" href="{{ promo.ctaUrl }}">{{ promo.ctaLabel }}</a>
    </div>
{% endfor %}
```

## `siteUrl( path, params, scheme, siteId )`

サイト上のページへの URL を作成するため _だけ_ という点を除けば、[url()](#url-path-params-protocol-mustshowscriptname) と似ています。

```twig
<a href="{{ siteUrl('company/contact') }}">Contact Us</a>
```

### 引数

`siteUrl()` ファンクションは、次の引数を持っています。

* **`path`** – 結果となる URL がサイトで指すべきパス。それは、ベースサイト URL に追加されます。
* **`params`** – URL に追加するクエリ文字列パラメータ。これは文字列（例：`'foo=1&bar=2'`）またはオブジェクト（例：`{foo:'1', bar:'2'}`）が利用可能です。
* **`scheme`** – URL が使用するスキーム（`'http'` または `'https'`）。デフォルト値は、現在のリクエストが SSL 経由で配信されているかどうかに依存します。そうでなければ、サイト URL のスキームが使用され、SSL 経由なら `https` が使用されます。
* **`siteId`** – URL が指すべきサイト ID。デフォルトでは、現在のサイトが使用されます。

## `svg( svg, sanitize )`

潜在的な悪意のあるスクリプトのサニタイズをされた SVG 文書を出力します。

::: tip
SVG 内の `id` 属性は自動的に名前空間になり、DOM 内の他の `id` 属性との競合を防ぎます。それが望ましくなければ、SVG ファイルを`templates/` フォルダ内に保存し、[include](https://twig.symfony.com/doc/2.x/tags/include.html) タグでロードすることができます。

```twig
{% include "_includes/sprites.svg" %}
```

:::

### 引数

`svg()` ファンクションは、次の引数を持っています。

- **`svg`** – SVG ファイルパス、SVG ファイルのコンテンツ、または SVG ファイルに相当する <api:craft\elements\Asset> オブジェクト。
- **`sanitize`** – SVG が潜在的な悪意あるスクリプトのサニタイズをされるべきかどうか（デフォルトは `true`）。

```twig
{# file path #}
{{ svg('@webroot/path/to/file.svg') }}

{# file contents #}
{{ svg('<svg ... />') }}

{# asset #}
{{ svg(entry.myAssetsField.one()) }}
```

## `url( path, params, scheme, mustShowScriptName )`

URL を返します。

```twig
<a href="{{ url('company/contact') }}">Contact Us</a>
```

### 引数

`url()` ファンクションは、次の引数を持っています。

* **`path`** – 結果となる URL がサイトで指すべきパス。それは、ベースサイト URL に追加されます。
* **`params`** – URL に追加するクエリ文字列パラメータ。これは文字列（例：`'foo=1&bar=2'`）またはオブジェクト（例：`{foo:'1', bar:'2'}`）が利用可能です。
* **`scheme`** – URL が使用するスキーム（`'http'` または `'https'`）。デフォルト値は、現在のリクエストが SSL 経由で配信されているかどうかに依存します。そうでなければ、サイト URL のスキームが使用され、SSL 経由なら `https` が使用されます。
* **`mustShowScriptName`** – ここに `true` がセットされている場合、「index.php」を含めた URL が返され、コンフィグ設定の <config:omitScriptNameInUrls> は無視されます。（ブラウザのアドレスバーに表示されない URL と .htaccess ファイルのリダイレクトとの衝突を避けたいような、Ajax 経由の POST リクエストで使用される URL の場合に有用です。）

::: tip
クエリ文字列パラメータを追加、および / または、絶対 URL にスキームを適用するために、`url()` ファンクションを使用することができます。

```twig
{{ url('http://my-project.com', 'foo=1', 'https') }}
{# Outputs: "https://my-project.com?foo=1" #}
```

:::

