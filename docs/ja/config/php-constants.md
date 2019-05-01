# PHP 定数

`web/index.php` ファイルには、Craft の読み込みと環境設定を行う際に、Craft の起動スクリプトがチェックする PHP 定数を定義することができます。

### `CRAFT_BASE_PATH`

Craft がデフォルトで探す [config/](../directory-structure.md#config)、[templates/](../directory-structure.md#templates)、および、他のディレクトリの**ベースディレクトリ**のパス。（デフォルトでは、`vendor/` フォルダの親とみなされます。）

```php
// Tell Craft to look for config/, templates/, etc., two levels up from here
define('CRAFT_BASE_PATH', dirname(__DIR__, 2));
```

### `CRAFT_COMPOSER_PATH`

[composer.json](../directory-structure.md#composer-json) ファイルのパス。（デフォルトでは、ベースディレクトリ内に存在するものとします。）

```php
define('CRAFT_COMPOSER_PATH', 'path/to/composer.json');
```

### `CRAFT_CONFIG_PATH`

[config/](../directory-structure.md#config) フォルダのパス。（デフォルトでは、ベースディレクトリ内に存在するものとします。）

### `CRAFT_CONTENT_MIGRATIONS_PATH`

コンテンツマイグレーションの保管に使用される [migrations/](../directory-structure.md#migrations) フォルダのパス。（デフォルトでは、ベースディレクトリ内に存在するものとします。）

### `CRAFT_ENVIRONMENT`

環境特有の設定配列を定義する際に[マルチ環境設定](environments.md#multi-environment-configs)が参照できる環境名。（デフォルトでは、`$_SERVER['SERVER_NAME']` が使用されます。）

```php
// Set the environment from the ENVIRONMENT env var, or default to 'production'
define('CRAFT_ENVIRONMENT', getenv('ENVIRONMENT') ?: 'production');
```

### `CRAFT_LICENSE_KEY`

何らかの理由で、ライセンスキーファイルではなく PHP によって定義されなければならい場合の Craft のライセンスキー。（有効なライセンスキーを取得するまで、これをセットしないでください。）

### `CRAFT_LICENSE_KEY_PATH`

ファイル名を含めた Craft がライセンスキーファイルを保存するパス。（デフォルトでは、[config/](../directory-structure.md#config) フォルダ内に `license.key` が保存されます。）

### `CRAFT_LOG_PHP_ERRORS`

Craft が PHP の [log_errors](http://php.net/manual/en/errorfunc.configuration.php#ini.log-errors) 設定をセットすることを抑制し、`php.ini` 内の設定に任せるよう `false` をセットすることもできます。

```php
// Don't send PHP error logs to storage/logs/phperrors.log
define('CRAFT_LOG_PHP_ERRORS', false);
```

### `CRAFT_SITE`

Craft がこの `index.php` ファイルから提供するべき、サイトハンドル、または、サイト ID。（明確な理由がある場合のみ、これをセットしてください。セットされていなければ、Craft はリクエスト URL を調査することで正しいサイトを自動的に配信します。）

```php
// Show the German site
define('CRAFT_SITE', 'de');
```

### `CRAFT_STORAGE_PATH`

[storage/](../directory-structure.md#storage) フォルダのパス。（デフォルトでは、ベースディレクトリ内に存在するものとします。）

### `CRAFT_TEMPLATES_PATH`

[templates/](../directory-structure.md#templates) フォルダのパス。（デフォルトでは、ベースディレクトリ内に存在するものとします。）

### `CRAFT_TRANSLATIONS_PATH`

`translations/` フォルダのパス。（デフォルトでは、ベースディレクトリ内に存在するものとします。）

### `CRAFT_VENDOR_PATH`

[vendor/](../directory-structure.md#vendor) フォルダのパス。（デフォルトでは、起動スクリプトによって4つのディレクトリが稼働しているものとします。）

