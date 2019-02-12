# 環境設定

Craft プロジェクトが複数の環境（例：Development、Staging、および、Production）にまたがる場合、それぞれの環境ごとに異なる設定が必要です。

例えば、それぞれの環境ではおそらく固有の[データベース接続設定](db-settings.md)が必要になり、[Dev Mode](config:devMode) は Production ではなくローカルの開発環境で有効にするべきです。

Craft ではそれを達成するための2つの方法があります。[環境変数](#environment-variables)と[マルチ環境設定](#multi-environment-configs)です。

::: tip
これらは互いに排他的ではありません。ほとんどの Craft プロジェクトでは、異なる方法で両方のアプローチを使用します。
:::

## 環境変数

環境変数は PHP の [getenv()](http://php.net/manual/en/function.getenv.php) ファンクションで読み込むことのできる、Craft が動作しているサーバー上でセットされた値です。

いくつかの方法（例えば、いくつかのホストは環境変数を定義するための UI を提供するなど）で設定できますが、新しい Craft 3 プロジェクトに付属する `.env` ファイルに設定することを推奨します。`.env` ファイルは他のプロジェクトのファイルのように Git にコミットしないため、特殊です。そのため、機密性の高い情報やそれぞれの環境で変化するような情報を記入しておくのに便利な場所です。

新しい環境変数をセットするには `.env` ファイルを開き、次のように追加します。

```bash
# -- .env --
SITE_URL="http://my-project.test"
```

::: tip
`.env` 構文でサポートされる詳細については、[PHP Dotenv documentation](https://github.com/vlucas/phpdotenv/blob/master/README.md) を参照してください。
:::

`.env` に新しい変数を追加するときは、おそらく値なしで `.env.example` にも追加しておくとよいでしょう。`.env.example` は、新しい環境の `.env` ファイルのベースにするべき出発点になります。

```bash
# -- .env.example --
SITE_URL=""
```

環境変数を定義すると、実際の[コンフィグ設定](config-settings.md)や[データベース接続設定](db-settings.md)で次のように取得できます。

```php
// -- config/general.php --
'siteUrl' => getenv('SITE_URL'),
```

いくつかの環境で環境変数が定義されていない可能性がある場合、[三項演算子](http://php.net/manual/en/language.operators.comparison.php#language.operators.comparison.ternary)（`?:`）を使用してフォールバック値を定義します。

```php
// -- config/general.php --
'siteUrl' => getenv('SITE_URL') ?: '/',
```

## マルチ環境設定

すべての Craft の設定ファイル（`config/` フォルダ内の `.php` ファイル）は、それぞれ環境ごとに別々の設定を定義できます。

単一の環境設定は、次のようにシンプルです。

```php
// -- config/general.php --
return [
    'omitScriptNameInUrls' => true,
    'siteUrl' => 'https://examle.com',
];
```

例えば、開発環境で異なる `siteUrl` 値を設定したいとします。そのために、はじめにすべてのコンフィグ設定を `'*'` キーの配列に移動し、**マルチ環境設定**にします。

```php{4,7}
// -- config/general.php --
return [
    // Global settings
    '*' => [
        'omitScriptNameInUrls' => true,
        'siteUrl' => 'https://examle.com',
    ],
];
```

これで、Craft はマルチ環境設定としてこれを扱うことを知ります。あとは、開発環境向けに `siteUrl` 設定を上書きするだけです。

```php{10-12}
// -- config/general.php --
return [
    // Global settings
    '*' => [
        'omitScriptNameInUrls' => true,
        'siteUrl' => 'https://my-project.com',
    ],

    // Dev environment settings
    'dev' => [
        'siteUrl' => 'http://my-project.test',
    ],
];
```

実際の環境名は、あなた次第です。Craft は `web/index.php` ファイルに定義されている[CRAFT_ENVIRONMENT](php-constants.md#craft-environment) PHP定数と同じ名前のキーを探します。

デフォルトでは、新しい Craft 3 プロジェクトは `.env` ファイルに定義された `ENVIRONMENT` 環境変数を使用して[CRAFT_ENVIRONMENT](php-constants.md#craft-environment) 定数を定義します。

```php
// -- web/index.php --
// Load and run Craft
define('CRAFT_ENVIRONMENT', getenv('ENVIRONMENT') ?: 'production');
```

```bash
# -- .env --
# The environment Craft is currently running in ('dev', 'staging', 'production', etc.)
ENVIRONMENT="dev"
```

::: tip
[CRAFT_ENVIRONMENT](php-constants.md#craft-environment) 定数が定義されていない場合、Craft 2 との後方互換性を維持するため、現在のサーバー名（例：`my-project.test`）に基づいて、Craft が定義します。ですが、あなた自身が明示的に定義することを推奨します。
:::

::: warning
Craft のマルチ環境設定のサポートを有効にするには、空欄であっても `'*'` 配列が必要です。Craft はその設定がマルチ環境向けかどうかを決定する際に、チェックします。
:::

## エイリアス

[エイリアス](README.md#aliases) は、それぞれの環境ごとにパスや URL を定義するための方法を提供します。

例えば、アセットボリュームが存在する `web/assets/` フォルダにベースパスと URL を保管する `ASSET_BASE_PATH` と `ASSET_BASE_URL` 環境変数を定義することを望むかもしれません。

```bash
# -- .env --
# Path to web/assets/ relative to index.php
ASSET_BASE_PATH="./assets"

# URL to web/assets/
ASSET_BASE_URL="/assets"
```

これらの環境変数を `config/general.php` ファイルのカスタムエイリアスとして、次のように取得できます。

```php
'aliases' => [
    '@assetBasePath' => getenv('ASSET_BASE_PATH'),
    '@assetBaseUrl' => getenv('ASSET_BASE_URL'),
],
```

これで、アセットボリュームの設定を新しいエイリアスから参照できます。

![ローカルアセットボリュームのベース URL、ボリュームタイプ、および、ファイルシステムのパスの設定](../images/volume-settings-with-aliases.png)

