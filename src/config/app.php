<?php

return [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '3.5.6',
    'schemaVersion' => '3.5.13',
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
        'drafts' => [
            'class' => craft\services\Drafts::class,
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
            'currentSite' => defined('CRAFT_SITE') ? CRAFT_SITE : (defined('CRAFT_LOCALE') ? CRAFT_LOCALE : null),
        ],
        'systemSettings' => [
            'class' => craft\services\SystemSettings::class,
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
            return Craft::$app->getLocale()->getFormatter();
        },

        'locale' => function() {
            $i18n = Craft::$app->getI18n();

            if (Craft::$app->getRequest()->getIsCpRequest() && !Craft::$app->getResponse()->isSent) {
                // Is someone logged in?
                $session = Craft::$app->getSession();
                $id = $session->getHasSessionId() || $session->getIsActive() ? $session->get(Craft::$app->getUser()->idParam) : null;
                if ($id) {
                    // If they have a preferred locale, use it
                    $usersService = Craft::$app->getUsers();
                    if (($locale = $usersService->getUserPreference($id, 'locale')) !== null) {
                        return $i18n->getLocaleById($locale);
                    }

                    // Otherwise see if they have a preferred language
                    if (($language = $usersService->getUserPreference($id, 'language')) !== null) {
                        return $i18n->getLocaleById($language);
                    }
                }

                // If the defaultCpLocale setting is set, go with that
                $generalConfig = Craft::$app->getConfig()->getGeneral();
                if ($generalConfig->defaultCpLocale) {
                    return $i18n->getLocaleById($generalConfig->defaultCpLocale);
                }
            }

            // Default to the application language
            return $i18n->getLocaleById(Craft::$app->language);
        },

        'log' => function() {
            $config = craft\helpers\App::logConfig();
            return $config ? Craft::createObject($config) : null;
        },

        'mailer' => function() {
            $config = craft\helpers\App::mailerConfig();
            return Craft::createObject($config);
        },

        'mutex' => function() {
            $config = craft\helpers\App::dbMutexConfig();
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
