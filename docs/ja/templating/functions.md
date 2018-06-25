# ファンクション

[Twig に付随する](http://twig.sensiolabs.org/doc/functions/index.html)テンプレートファンクションに加えて、Craft がいくつか独自のものを提供します。

## `alias( string )`

その文字列が[エイリアス](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html)ではじまるかをチェックする [Craft::getAlias()](api:yii\BaseYii::getAlias()) に、文字列を渡します。（詳細については、[コンフィギュレーション](../configuration.md#aliases)を参照してください。）

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
{{ ceil(42.1) }} => 43
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
{{ floor(42.9) }} => 42
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
{{ round(42.1) }} => 42
{{ round(42.9) }} => 43
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

## `svg( svg, sanitize )`

指定された SVG ファイルのコンテンツを返します。

### 引数

`svg()` ファンクションは、次の引数を持っています。

- **`svg`** – SVG ファイルパス、SVG ファイルのコンテンツ、または SVG ファイルに相当する <api:craft\elements\Asset> オブジェクト。
- **`sanitize`** – SVG が潜在的な悪意あるスクリプトのサニタイズをされるべきかどうか（デフォルトは `true`）。

## `url( path, params, protocol, mustShowScriptName )`

サイトのページへの URL を返します。

```twig
<a href="{{ url('company/contact') }}">Contact Us</a>
```

### 引数

`url()` ファンクションは、次の引数を持っています。

* **`path`** – 結果となる URL がサイトで指すべきパス。それは、ベースサイト URL に追加されます。
* **`params`** – URL に追加するクエリ文字列パラメータ。これは文字列（例：`'foo=1&bar=2'`）またはオブジェクト（例：`{foo:'1', bar:'2'}`）が利用可能です。
* **`protocol`** – URL が使用するプロトコル（`'http'` または `'https'`）。デフォルト値は、現在のリクエストが SSL 経由で配信されているかどうかに依存します。そうでなければ、サイト URL のプロトコルが使用され、SSL 経由なら `https` が使用されます。
* **`mustShowScriptName`** – ここに `true` がセットされている場合、「index.php」を含めた URL が返され、コンフィグ設定の <config:omitScriptNameInUrls> は無視されます。（ブラウザのアドレスバーに表示されない URL と .htaccess ファイルのリダイレクトとの衝突を避けたいような、Ajax 経由の POST リクエストで使用される URL の場合に有用です。）

::: tip
クエリ文字列パラメータを追加する、および / または、絶対 URL にプロトコルを適用するために、`url()` ファンクションを使うことができます。 

```twig
{{ url('http://example.com', 'foo=1', 'https') }}
{# Outputs: "https://example.com?foo=1" #}
```

:::

