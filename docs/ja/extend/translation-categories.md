# 翻訳カテゴリ

モジュールやプラグインは、Yii の [Message Translations](https://www.yiiframework.com/doc/guide/1.1/en/topics.i18n#message-translation) 機能を使用することによって、カスタム翻訳カテゴリを提供できます。

::: tip
メッセージ変換がどのように機能するかの詳細については、[静的メッセージの翻訳](../static-translations.md)を参照してください。
:::

翻訳カテゴリは、 <api:yii\i18n\I18N::$translations> 配列に新しい翻訳ソースを加えることにより、プログラムで追加できます。

```php
use craft\i18n\PhpMessageSource;

public function init()
{
    parent::init();

    Craft::$app->i18n->translations['my-category'] = [
        'class' => PhpMessageSource::class,
        'sourceLanguage' => 'en',
        'basePath' => __DIR__ . '/translations',
        'allowOverrides' => true,
    ];
}
```

[アプリケーション設定](../config/app.md)をコントロールできる場合、そこから翻訳カテゴリを追加することもできます。

```php
// -- config/app.php --
return [
    'components' => [
        'i18n' => [
            'translations' => [
                'my-category' => [
                    'class' => craft\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                    'basePath' => dirname(__DIR__) . '/translations',
                    'allowOverrides' => true,
                ],
            ],
        ],
    ],
];
```

## プラグインの翻訳

プラグインは自動的に登録され、プラグインハンドルにちなんで名付けられた、カスタム翻訳カテゴリを取得します。プラグインは ベースソースフォルダ内の `translations/` 内で翻訳ファイルを提供できます。

```
src/
├── Plugin.php
├── ...
└── translations/
    └── de/
        └── plugin-handle.php
```

