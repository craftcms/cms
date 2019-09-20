# グローバル変数

ありとあらゆるテンプレートでは、次の変数を読み込むことができます。

## `craft`

様々なヘルパーファンクションやオブジェクトのアクセスポイントを提供する <api:craft\web\twig\variables\CraftVariable>  オブジェクト。

### `craft.app`

<api:craft\web\Application> インスタンス（PHP コード内で `Craft::$app` と記述したときに取得できるもの）への参照は、`craft.app` 経由でテンプレートでも利用可能です。

::: warning
`craft.app` 経由でアクセスすることは、先進的であると考えられます。他の Twig 特有の変数やファンクションよりもセキュリティの上で意味があります。さらに、Craft のメジャーバージョン間で生じる互換性を破る変更に、テンプレートを反応させやすくするでしょう。
:::

```twig
{% set field = craft.app.fields.getFieldByHandle('body') %}
```

## `currentSite`

<api:craft\models\Site> オブジェクトで表される、リクエストされたサイト。

```twig
{{ currentSite.name }}
```

現在のサイトと同じグループのすべてのサイトは、`currentSite.group.sites` 経由でアクセスすることができます。

```twig
<nav>
    <ul>
        {% for site in currentSite.group.sites %}
            <li><a href="{{ alias(site.baseUrl) }}">{{ site.name }}</a></li>
        {% endfor %}
    </ul>
</nav>
```

## `currentUser`

<api:craft\elements\User> オブジェクトで表される、現在ログインしているユーザー。誰もログインしていない場合は、`null`。

```twig
{% if currentUser %}
    Welcome, {{ currentUser.friendlyName }}!
{% endif %}
```

## `devMode`

コンフィグ設定 <config:devMode> が現在有効になっているかどうか。

```twig
{% if devMode %}
    Craft is running in dev mode.
{% endif %}
```

## `loginUrl`

<config:loginPath> コンフィグ設定に基づく、サイトのログインページの URL。

```twig
{% if not currentUser %}
    <a href="{{ loginUrl }}">Login</a>
{% endif %}
```

## `logoutUrl`

<config:logoutPath> コンフィグ設定に基づく、Craft ユーザーのログアウト URL。ここに遷移した後、Craft はユーザーをホームページへ自動的にリダイレクトします。「ログアウト _ページ_ 」といったものはありません。

```twig
{% if currentUser %}
    <a href="{{ logoutUrl }}">Logout</a>
{% endif %}
```

## `now`

現在の日付と時刻がセットされた [DateTime](http://php.net/manual/en/class.datetime.php) オブジェクト。

```twig
Today is {{ now|date('M j, Y') }}.
```

## `POS_BEGIN`

定数 [craft\web\View::POS_BEGIN](api:craft\web\View#constants) の Twig 対応のコピー。

## `POS_END`

定数 [craft\web\View::POS_END](api:craft\web\View#constants) の Twig 対応のコピー。

## `POS_HEAD`

定数 [craft\web\View::POS_HEAD](api:craft\web\View#constants) の Twig 対応のコピー。

## `POS_LOAD`

定数 [craft\web\View::POS_LOAD](api:craft\web\View#constants) の Twig 対応のコピー。

## `POS_READY`

定数 [craft\web\View::POS_READY](api:craft\web\View#constants) の Twig 対応のコピー。

## `siteName`

「設定 > サイト」で定義されている、サイトの名前。

```twig
<h1>{{ siteName }}</h1>
```

## `siteUrl`

サイトの URL。

```twig
<link rel="home" href="{{ siteUrl }}">
```

## `SORT_ASC`

PHP 定数 `SORT_ASC` の Twig 対応のコピー。

## `SORT_DESC`

PHP 定数 `SORT_DESC` の Twig 対応のコピー。

## `SORT_FLAG_CASE`

PHP 定数 `SORT_FLAG_CASE` の Twig 対応のコピー。

## `SORT_LOCALE_STRING`

PHP 定数 `SORT_LOCALE_STRING` の Twig 対応のコピー。

## `SORT_NATURAL`

PHP 定数 `SORT_NATURAL` の Twig 対応のコピー。

## `SORT_NUMERIC`

PHP 定数 `SORT_NUMERIC` の Twig 対応のコピー。

## `SORT_REGULAR`

PHP 定数 `SORT_REGULAR` の Twig 対応のコピー。

## `SORT_STRING`

PHP 定数 `SORT_STRING` の Twig 対応のコピー。

## `systemName`

「設定 > 一般」で定義されている、システム名。

## `view`

テンプレートを駆動している <api:craft\web\View> インスタンスへの参照。

## グローバル設定の変数

それそれのサイトの[グローバル設定](../globals.md)は、ハンドルにちなんで命名されたグローバル変数としてテンプレートで利用可能です。

それらは <api:craft\elements\GlobalSet> オブジェクトとして表されます。

```twig
<p>{{ companyInfo.companyName }} was established in {{ companyInfo.yearEstablished }}.</p>
```

