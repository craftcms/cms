<?php

return [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '3.0.30.2',
    'schemaVersion' => '3.0.93',
    'minVersionRequired' => '2.6.2788',
    'basePath' => dirname(__DIR__), // Defines the @app alias
    'runtimePath' => '@storage/runtime', // Defines the @runtime alias
    'controllerNamespace' => 'craft\controllers',

    'components' => [
        'api' => [
            'class' => craft\services\Api::class,
        ],
        'assets' => [
            'class' => craft\services\Assets::class,
        ],
        'assetIndexer' => [
            'class' => craft\services\AssetIndexer::class,
        ],
        'assetTransforms' => [
            'class' => craft\services\AssetTransforms::class,
        ],
        'categories' => [
            'class' => craft\services\Categories::class,
        ],
        'composer' => [
            'class' => \craft\services\Composer::class,
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
        'elementIndexes' => [
            'class' => craft\services\ElementIndexes::class,
        ],
        'elements' => [
            'class' => craft\services\Elements::class,
        ],
        'entries' => [
            'class' => craft\services\Entries::class,
        ],
        'entryRevisions' => [
            'class' => craft\services\EntryRevisions::class,
        ],
        'feeds' => [
            'class' => craft\feeds\Feeds::class,
        ],
        'fields' => [
            'class' => craft\services\Fields::class,
        ],
        'globals' => [
            'class' => craft\services\Globals::class,
        ],
        'images' => [
            'class' => craft\services\Images::class,
        ],
        'matrix' => [
            'class' => craft\services\Matrix::class,
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
        'contentMigrator' => [
            'class' => craft\db\MigrationManager::class,
            'type' => craft\db\MigrationManager::TYPE_CONTENT,
            'migrationNamespace' => 'craft\contentmigrations',
            'migrationPath' => '@contentMigrations',
        ],
        'migrator' => [
            'class' => craft\db\MigrationManager::class,
            'type' => craft\db\MigrationManager::TYPE_APP,
            'migrationNamespace' => 'craft\migrations',
            'migrationPath' => '@app/migrations',
        ],
        'sites' => [
            'class' => craft\services\Sites::class,
            'currentSite' => null,
        ],
        'systemSettings' => [
            'class' => craft\services\SystemSettings::class,
            'defaults' => [
                'users' => [
                    'requireEmailVerification' => true,
                    'allowPublicRegistration' => false,
                    'defaultGroup' => null,
                    'photoVolumeId' => null,
                    'photoSubpath' => ''
                ],
            ]
        ],
        'i18n' => [
            'class' => craft\i18n\I18N::class,
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

        'mailer' => function() {
            $config = craft\helpers\App::mailerConfig();
            return Craft::createObject($config);
        },

        'locale' => function() {
            return Craft::$app->getI18n()->getLocaleById(Craft::$app->language);
        },

        'mutex' => function() {
            $config = craft\helpers\App::mutexConfig();
            return Craft::createObject($config);
        },

        'formatter' => function() {
            return Craft::$app->getLocale()->getFormatter();
        },

        'log' => function() {
            $config = craft\helpers\App::logConfig();
            return $config ? Craft::createObject($config) : null;
        },

        'view' => function() {
            $config = craft\helpers\App::viewConfig();
            return Craft::createObject($config);
        },
    ],
];
