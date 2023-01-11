<?php

return [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '4.3.6.1',
    'schemaVersion' => '4.0.0.9',
    'minVersionRequired' => '3.7.11',
    'basePath' => dirname(__DIR__), // Defines the @app alias
    'runtimePath' => '@storage/runtime', // Defines the @runtime alias
    'controllerNamespace' => 'craft\controllers',

    'components' => [
        'addresses' => [
            'class' => craft\services\Addresses::class,
        ],
        'announcements' => [
            'class' => craft\services\Announcements::class,
        ],
        'api' => [
            'class' => craft\services\Api::class,
        ],
        'assets' => [
            'class' => craft\services\Assets::class,
        ],
        'assetIndexer' => [
            'class' => craft\services\AssetIndexer::class,
        ],
        'categories' => [
            'class' => craft\services\Categories::class,
        ],
        'composer' => [
            'class' => craft\services\Composer::class,
        ],
        'conditions' => [
            'class' => craft\services\Conditions::class,
        ],
        'content' => [
            'class' => craft\services\Content::class,
        ],
        'dashboard' => [
            'class' => craft\services\Dashboard::class,
        ],
        'deprecator' => [
            'class' => craft\services\Deprecator::class,
        ],
        'drafts' => [
            'class' => craft\services\Drafts::class,
        ],
        'elementSources' => [
            'class' => craft\services\ElementSources::class,
        ],
        'elements' => [
            'class' => craft\services\Elements::class,
        ],
        'entries' => [
            'class' => craft\services\Entries::class,
        ],
        'fields' => [
            'class' => craft\services\Fields::class,
        ],
        'fs' => [
            'class' => craft\services\Fs::class,
        ],
        'gc' => [
            'class' => craft\services\Gc::class,
        ],
        'globals' => [
            'class' => craft\services\Globals::class,
        ],
        'gql' => [
            'class' => craft\services\Gql::class,
        ],
        'images' => [
            'class' => craft\services\Images::class,
        ],
        'imageTransforms' => [
            'class' => craft\services\ImageTransforms::class,
        ],
        'log' => [
            'class' => craft\log\Dispatcher::class,
        ],
        'matrix' => [
            'class' => craft\services\Matrix::class,
        ],
        'mutex' => [
            'class' => craft\mutex\Mutex::class,
        ],
        'path' => [
            'class' => craft\services\Path::class,
        ],
        'plugins' => [
            'class' => craft\services\Plugins::class,
        ],
        'pluginStore' => [
            'class' => craft\services\PluginStore::class,
        ],
        'queue' => [
            'class' => craft\queue\Queue::class,
        ],
        'relations' => [
            'class' => craft\services\Relations::class,
        ],
        'revisions' => [
            'class' => craft\services\Revisions::class,
        ],
        'routes' => [
            'class' => craft\services\Routes::class,
        ],
        'search' => [
            'class' => craft\services\Search::class,
        ],
        'sections' => [
            'class' => craft\services\Sections::class,
        ],
        'security' => [
            'class' => craft\services\Security::class,
            'sensitiveKeywords' => [
                'key',
                'pass',
                'password',
                'pw',
                'secret',
                'tok',
                'token',
            ],
        ],
        'structures' => [
            'class' => craft\services\Structures::class,
        ],
        'systemMessages' => [
            'class' => craft\services\SystemMessages::class,
        ],
        'tags' => [
            'class' => craft\services\Tags::class,
        ],
        'templateCaches' => [
            'class' => craft\services\TemplateCaches::class,
        ],
        'tokens' => [
            'class' => craft\services\Tokens::class,
        ],
        'updates' => [
            'class' => craft\services\Updates::class,
        ],
        'urlManager' => [
            'class' => craft\web\UrlManager::class,
            'enablePrettyUrl' => true,
            'ruleConfig' => ['class' => craft\web\UrlRule::class],
        ],
        'users' => [
            'class' => craft\services\Users::class,
        ],
        'volumes' => [
            'class' => craft\services\Volumes::class,
        ],
        'userGroups' => [
            'class' => craft\services\UserGroups::class,
        ],
        'userPermissions' => [
            'class' => craft\services\UserPermissions::class,
        ],
        'utilities' => [
            'class' => craft\services\Utilities::class,
        ],
        'webpack' => [
            'class' => craft\services\Webpack::class,
        ],
        'contentMigrator' => [
            'class' => craft\db\MigrationManager::class,
            'track' => craft\db\MigrationManager::TRACK_CONTENT,
            'migrationNamespace' => 'craft\contentmigrations',
            'migrationPath' => '@contentMigrations',
        ],
        'migrator' => [
            'class' => craft\db\MigrationManager::class,
            'track' => craft\db\MigrationManager::TRACK_CRAFT,
            'migrationNamespace' => 'craft\migrations',
            'migrationPath' => '@app/migrations',
        ],
        'sites' => [
            'class' => craft\services\Sites::class,
            'currentSite' => craft\helpers\App::env('CRAFT_SITE'),
        ],
        'i18n' => [
            'class' => craft\i18n\I18N::class,
            'messageFormatter' => [
                'class' => craft\i18n\MessageFormatter::class,
            ],
            'translations' => [
                'yii' => [
                    'class' => craft\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@yii/messages',
                    'forceTranslation' => true,
                    'allowOverrides' => true,
                ],
                'app' => [
                    'class' => craft\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@app/translations',
                    'forceTranslation' => true,
                    'allowOverrides' => true,
                ],
                'site' => [
                    'class' => craft\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@translations',
                    'forceTranslation' => true,
                ],
            ],
        ],

        // Dynamically configured components
        // -------------------------------------------------------------------------

        'cache' => function() {
            $config = craft\helpers\App::cacheConfig();
            return Craft::createObject($config);
        },

        'db' => function() {
            $config = craft\helpers\App::dbConfig();
            return Craft::createObject($config);
        },

        'formatter' => function() {
            return Craft::$app->getFormattingLocale()->getFormatter();
        },

        'formattingLocale' => function() {
            return craft\helpers\App::createFormattingLocale();
        },

        'locale' => function() {
            return Craft::$app->getI18n()->getLocaleById(Craft::$app->language);
        },

        'mailer' => function() {
            $config = craft\helpers\App::mailerConfig();
            return Craft::createObject($config);
        },

        'projectConfig' => function() {
            $config = craft\helpers\App::projectConfigConfig();
            return Craft::createObject($config);
        },

        'view' => function() {
            $config = craft\helpers\App::viewConfig();
            return Craft::createObject($config);
        },
    ],
];
