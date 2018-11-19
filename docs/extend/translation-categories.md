# Translation Categories

Modules and plugins can provide custom translation categories, for use by Yii’s [Message Translations](https://www.yiiframework.com/doc/guide/1.1/en/topics.i18n#message-translation) feature.

::: tip
See [Static Message Translations](../static-translations.md) for more details on how message translations work.
:::

Translation categories can be added programmatically by adding a new translation source onto the <api:yii\i18n\I18N::$translations> array.

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

If you have control over the [application config](../config/app.md), you could also add the translation category from there:

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

## Plugin Translations

Plugins get a custom translation category registered automatically, named after the plugin handle. Plugins can provide translation files within a `translations/` folder in their base source folder.

```
src/
├── Plugin.php
├── ...
└── translations/
    └── de/
        └── plugin-handle.php
```
