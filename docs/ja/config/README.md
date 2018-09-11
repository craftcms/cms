# コンフィギュレーションの概要

[[toc]]

## PHP 定数

`web/index.php` ファイルには、Craft の読み込みと環境設定を行なう際に、Craft の起動スクリプトがチェックする [PHP 定数](php-constants.md)を定義することができます。

## 一般設定

Craft は、いくつかの[一般設定](config-settings.md)をサポートしています。`config/general.php` ファイルでデフォルト値を上書きすることができます。

```php
return [
    'devMode' => true, 
];
```

## データベース接続設定

Craft は、いくつかの[データベース接続設定](db-settings.md)をサポートしています。`config/db.php` ファイルでデフォルト値を上書きすることができます。

## データキャッシュ設定

デフォルトでは、Craft は `storage/runtime/cache/` フォルダにデータキャッシュを蓄積します。`config/app.php` で `cache` アプリケーションコンポーネントを上書きすることによって代替の[キャッシュストレージ](https://www.yiiframework.com/doc/guide/2.0/en/caching-data#supported-cache-storage)を使うよう Craft を設定できます。

```php
<?php
return [
    'components' => [
        'cache' => [
            'class' => yii\caching\ApcCache::class,
            'useApcu' => true,
        ],
    ],
];
```

### 実例

キャッシュストレージ設定の一般的な例です。

#### Memcached

```php
<?php
return [
    'components' => [
        'cache' => [
            'class' => yii\caching\MemCache::class,
            'useMemcached' => true,
            'username' => getenv('MEMCACHED_USERNAME'),
            'password' => getenv('MEMCACHED_PASSWORD'),
            'defaultDuration' => 86400,
            'servers' => [
                [
                    'host' => 'localhost',
                    'persistent' => true,
                    'port' => 11211,
                    'retryInterval' => 15,
                    'status' => true,
                    'timeout' => 15,
                    'weight' => 1,
                ],
            ],
        ],
    ],
];
```

#### Redis

Redis キャッシュストレージを利用するには、あらかじめ [yii2-redis](https://github.com/yiisoft/yii2-redis) ライブラリをインストールする必要があります。次に、Craft の `cache` コンポーネントでそれを利用するよう設定します。

```php
<?php
return [
    'components' => [
        'cache' => [
            'class' => yii\redis\Cache::class,
            'defaultDuration' => 86400,
            'redis' => [
                'hostname' => 'localhost',
                'port' => 6379,
                'password' => getenv('REDIS_PASSWORD'),
                'database' => 0,
            ],
        ],
    ],
];
```

## Guzzle 設定

Craft は、次のような HTTP リクエストを作成するたびに [Guzzle 6](http://docs.guzzlephp.org/en/latest/) を使用します。

- Craft のアップデートをチェックするとき
- Craft のサポートウィジェットからサポートリクエストを送信するとき
- Feed ウィジェットから RSS フィードを読み込むとき
- Amazon S3 のようなリモートボリュームにあるアセットを操作するとき

`config/` フォルダに `guzzle.php` ファイルを作成することによって、これらのリクエストを送信する際に Guzzle が使用するコンフィグ設定をカスタマイズできます。そのファイルは、設定を上書きした配列を返さなければなりません。

```php
<?php

return [
    'headers' => ['Foo' => 'Bar'],
    'query'   => ['testing' => '123'],
    'auth'    => ['username', 'password'],
    'proxy'   => 'tcp://localhost:80',
];
```

ここで定義されたオプションは、新しい `GuzzleHttp\Client` インスタンスに渡されます。利用可能なオプションのリストは、[Guzzle のドキュメント](http://docs.guzzlephp.org/en/latest/)を参照してください。

## エイリアス

Craft のいくつかの設定やファンクションでは、基本ファイルシステムのパスや URL を代用する [Yii エイリアス](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases)をサポートしています。 これには次ものが含まれます。

- サイトのベース URL 設定
- ボリュームのベース URL 設定
- ローカルボリュームのファイルシステムパス設定
- <config:resourceBasePath> と <config:resourceBaseUrl> のコンフィグ設定
- Twig ファンクションの [svg()](../dev/functions.md#svg-svg-sanitize)

次のエイリアスは、そのまま利用可能です。

| エイリアス | 説明 |
| ----- | ----------- |
| `@app` | `vendor/craftcms/cms/src/` のパス |
| `@config` | `config/` フォルダのパス |
| `@contentMigrations` | `migrations/` フォルダのパス |
| `@craft` | `vendor/craftcms/cms/src/` のパス |
| `@lib` | `vendor/craftcms/cms/lib/` のパス |
| `@root` | ルートプロジェクトのパス（PHP 定数の [CRAFT_BASE_PATH](php-constants.md#craft-base-path) と同じ） |
| `@runtime` | `storage/runtime/` フォルダのパス |
| `@storage` | `storage/` フォルダのパス |
| `@templates` | `templates/` フォルダのパス |
| `@translations` | `translations/` フォルダのパス |
| `@vendor` | `vendor/` フォルダのパス |
| `@web` | リクエストのために読み込まれた `index.php` ファイルを含むフォルダの URL |
| `@webroot` | リクエストのために読み込まれた `index.php` ファイルを含むフォルダのパス |

<config:aliases> コンフィグ設定を利用して、追加の独自エイリアスを定義することができます。例えば、アセットボリュームが存在するベース URL とベースパスを定義するエイリアスを作成したいかもしれません。

```php
'aliases' => [
    '@assetBaseUrl' => 'http://my-project.com/assets',
    '@assetBasePath' => '/path/to/web/assets',
],
```

これらを利用して、アセットボリュームのベース URL やファイルシステムのパス設定を記入しはじめることができます。例：`@assetBaseUrl/user-photos` と `@assetBasePath/user-photos`

必要であれば、`.env` ファイルや環境設定のどこかで、環境変数のエイリアス値をセットすることができます。

```bash
ASSET_BASE_URL=http://my-project.com/assets
ASSET_BASE_PATH=/path/to/web/assets
```

[getenv()](http://php.net/manual/en/function.getenv.php) を使用して、エイリアスの定義にセットすることができます。

```php
'aliases' => [
    '@assetBaseUrl' => getenv('ASSET_BASE_URL'),
    '@assetBasePath' => getenv('ASSET_BASE_PATH'),
],
```

## ボリューム設定の上書き

設定ファイルでボリューム設定を定義することを好む場合、`config/volumes.php` で設定できます。このファイルは配列を返さなければならず、キーにはボリュームのハンドルをマップし、値には上書きする設定を定義してあるネストされた配列を持たせます。

::: warning
Craft が上書きのために `config/volumes.php` をチェックしはじめる前に、コントロールパネルでボリュームを作成しておく必要があります。
:::

```php
return [
    'siteAssets' => [
        'path' => getenv('ASSETS_BASE_PATH').'/site',
        'url' => getenv('ASSETS_BASE_URL').'/site',
    ],
    'companyLogos' => [
        'path' => getenv('ASSETS_BASE_PATH').'/logos',
        'url' => getenv('ASSETS_BASE_URL').'/logos',
    ],
];
```

## URL ルール

`config/routes.php` にカスタムの [URL ルール](https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#url-rules) を定義することができます。詳細については、[ルーティング](../routing.md) を参照してください。

## アプリケーション設定

`config/app.php` から、Craft のすべての[アプリケーション設定](https://www.yiiframework.com/doc/guide/2.0/en/structure-applications#application-configurations)をカスタマイズできます。配列として返された項目は、 メインのアプリケーション設定の配列にマージされます。

### Mailer コンポーネント

（メール送信を担っている）`mailer` コンポーネントの設定を上書きするために、`config/app.php` を調整します。

```php
<?php

return [
    'components' => [
        'mailer' => function() {
            // Get the stored email settings
            $settings = Craft::$app->systemSettings->getEmailSettings();

            // Override the transport adapter class
            $settings->transportType = craft\mailgun\MailgunAdapter::class;

            // Override the transport adapter settings
            $settings->transportSettings = [
                'domain' => 'foo.com',
                'apiKey' => 'key-xxxxxxxxxx',
            ];

            return craft\helpers\MailerHelper::createMailer($settings);
        },

        // ...
    ],

    // ...
];
```

### Queue コンポーネント

Craft のジョブキューは [Yii2 Queue Extension](https://github.com/yiisoft/yii2-queue) によって動いています。デフォルトでは、Craft はエクステンションの [DB driver](https://github.com/yiisoft/yii2-queue/blob/master/docs/guide/driver-db.md) をベースとする [custom queue driver](craft\queue\Queue) を使用しますが、`config/app.php` から Craft の `queue` コンポーネントを上書きすることによって、別のドライバに切り替えることができます。

```php
<?php

return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'queue', // Queue channel key
        ], 
    ],
    
    // ...
];
```

利用可能なドライバは、[Yii2 Queue Extension documentation](https://github.com/yiisoft/yii2-queue/tree/master/docs/guide) に記載されています。

::: warning
<api:craft\queue\QueueInterface> を実装しているドライバだけがコントロールパネル内に表示されます。
:::

::: tip
キュードライバが独自のワーカーを提供している場合、`config/general.php` の <config:runQueueAutomatically> コンフィグ設定を `false` に設定します。
:::

