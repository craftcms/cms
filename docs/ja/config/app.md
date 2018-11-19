# アプリケーション設定

`config/app.php` から、Craft の [Yii アプリケーション設定](https://www.yiiframework.com/doc/guide/2.0/en/structure-applications#application-configurations) 全体をカスタマイズできます。配列として返された項目は、 メインのアプリケーション設定の配列にマージされます。

`config/app.web.php` および `config/app.console.php` から、ウェブリクエストやコンソールリクエストだけに対して Craft のアプリケーション設定をカスタマイズすることもできます。

[[toc]]

## Cache コンポーネント

デフォルトでは、Craft は `storage/runtime/cache/` フォルダにデータキャッシュを蓄積します。`config/app.php` で `cache` アプリケーションコンポーネントを上書きすることによって、代替の[キャッシュストレージ](https://www.yiiframework.com/doc/guide/2.0/en/caching-data#supported-cache-storage)を使うよう Craft を設定できます。

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

#### Memcached の実例

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

#### Redis の実例

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

## Mailer コンポーネント

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

            // Create a Mailer component config with these settings
            $config = craft\helpers\App::mailerConfig($settings);
            
            // Instantiate and return it
            return Craft::createObject($config);
        },
    ],
];
```

::: tip
`config/app.php` から Mailer コンポーネントに行った変更は、「設定 > メール」からメールの設定をテストする際には反映されません。
:::

## Queue コンポーネント

Craft のジョブキューは [Yii2 Queue Extension](https://github.com/yiisoft/yii2-queue) によって動いています。デフォルトでは、Craft はエクステンションの [DB driver](https://github.com/yiisoft/yii2-queue/blob/master/docs/guide/driver-db.md) をベースとする [custom queue driver](craft\queue\Queue) を使用しますが、`config/app.php` から Craft の `queue` コンポーネントを上書きすることによって、別のドライバに切り替えることができます。

```php
<?php
return [
    'components' => [
        'queue' => [
            'class' => yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'queue', // Queue channel key
        ], 
    ],
];
```

利用可能なドライバは、[Yii2 Queue Extension documentation](https://github.com/yiisoft/yii2-queue/tree/master/docs/guide) に記載されています。

::: warning
<api:craft\queue\QueueInterface> を実装しているドライバだけがコントロールパネル内に表示されます。
:::

::: tip
キュードライバが独自のワーカーを提供している場合、`config/general.php` の <config:runQueueAutomatically> コンフィグ設定を `false` に設定します。
:::

## モジュール

`config/app.php` からカスタム Yii モジュールを登録し bootstrap することもできます。詳細については、[モジュールの構築方法](../extend/module-guide.md)を参照してください。

