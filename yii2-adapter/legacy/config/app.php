<?php

use craft\helpers\App;
use craft\i18n\MessageFormatter;
use craft\i18n\PhpMessageSource;
use craft\log\Dispatcher;
use craft\mutex\Mutex;
use craft\queue\QueueComponent;
use craft\services\Addresses;
use craft\services\AssetIndexer;
use craft\services\Assets;
use craft\services\Auth;
use craft\services\Categories;
use craft\services\Dashboard;
use craft\services\Deprecator;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\ElementSources;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Fs;
use craft\services\Gc;
use craft\services\Globals;
use craft\services\Gql;
use craft\services\Images;
use craft\services\ImageTransforms;
use craft\services\Path;
use craft\services\Plugins;
use craft\services\Relations;
use craft\services\Revisions;
use craft\services\Routes;
use craft\services\Search;
use craft\services\Security;
use craft\services\Sites;
use craft\services\Sso;
use craft\services\Structures;
use craft\services\SystemMessages;
use craft\services\Tags;
use craft\services\TemplateCaches;
use craft\services\Tokens;
use craft\services\UserGroups;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\services\Utilities;
use craft\services\Volumes;
use craft\services\Webpack;
use craft\web\UrlManager;
use craft\web\UrlRule;
use CraftCms\Cms\Announcement\Announcements;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Yii2Adapter\Log\LogTarget;

return [
    'id' => 'CraftCMS',
    'name' => Cms::NAME,
    'version' => Cms::VERSION,
    'schemaVersion' => Cms::SCHEMA_VERSION,
    'minVersionRequired' => Cms::MIN_VERSION_REQUIRED,
    'basePath' => dirname(__DIR__), // Defines the @app alias
    'runtimePath' => '@storage/runtime', // Defines the @runtime alias
    'controllerNamespace' => 'craft\controllers',

    'components' => [
        'addresses' => [
            'class' => Addresses::class,
        ],
        'announcements' => Announcements::class,
        'assets' => [
            'class' => Assets::class,
        ],
        'assetIndexer' => [
            'class' => AssetIndexer::class,
        ],
        'auth' => [
            'class' => Auth::class,
        ],
        'categories' => [
            'class' => Categories::class,
        ],
        'conditions' => [
            'class' => Conditions::class,
        ],
        'dashboard' => [
            'class' => Dashboard::class,
        ],
        'deprecator' => [
            'class' => Deprecator::class,
        ],
        'drafts' => [
            'class' => Drafts::class,
        ],
        'elementSources' => [
            'class' => ElementSources::class,
        ],
        'elements' => [
            'class' => Elements::class,
        ],
        'entries' => [
            'class' => Entries::class,
        ],
        'fields' => [
            'class' => Fields::class,
        ],
        'fs' => [
            'class' => Fs::class,
        ],
        'gc' => [
            'class' => Gc::class,
        ],
        'globals' => [
            'class' => Globals::class,
        ],
        'gql' => [
            'class' => Gql::class,
        ],
        'images' => [
            'class' => Images::class,
        ],
        'imageTransforms' => [
            'class' => ImageTransforms::class,
        ],
        'log' => [
            'class' => Dispatcher::class,
            'targets' => [
                [
                    'class' => LogTarget::class,
                ],
            ],
        ],
        'mutex' => [
            'class' => Mutex::class,
        ],
        'path' => [
            'class' => Path::class,
        ],
        'plugins' => [
            'class' => Plugins::class,
        ],
        'queue' => [
            'class' => QueueComponent::class,
        ],
        'relations' => [
            'class' => Relations::class,
        ],
        'revisions' => [
            'class' => Revisions::class,
        ],
        'routes' => [
            'class' => Routes::class,
        ],
        'search' => [
            'class' => Search::class,
        ],
        'security' => [
            'class' => Security::class,
            'sensitiveKeywords' => [
                'key',
                'pass',
                'password',
                'pw',
                'secret',
                'sk',
                'tok',
                'token',
            ],
        ],
        'structures' => [
            'class' => Structures::class,
        ],
        'systemMessages' => [
            'class' => SystemMessages::class,
        ],
        'tags' => [
            'class' => Tags::class,
        ],
        'templateCaches' => [
            'class' => TemplateCaches::class,
        ],
        'tokens' => [
            'class' => Tokens::class,
        ],
        'urlManager' => [
            'class' => UrlManager::class,
            'enablePrettyUrl' => true,
            'ruleConfig' => ['class' => UrlRule::class],
        ],
        'users' => [
            'class' => Users::class,
        ],
        'volumes' => [
            'class' => Volumes::class,
        ],
        'userGroups' => [
            'class' => UserGroups::class,
        ],
        'userPermissions' => [
            'class' => UserPermissions::class,
        ],
        'utilities' => [
            'class' => Utilities::class,
        ],
        'webpack' => [
            'class' => Webpack::class,
        ],
        'sites' => [
            'class' => Sites::class,
        ],
        'sso' => [
            'class' => Sso::class,
        ],
        'i18n' => [
            'class' => craft\i18n\I18N::class,
            'messageFormatter' => [
                'class' => MessageFormatter::class,
            ],
            'translations' => [
                'yii' => [
                    'class' => PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@yii/messages',
                    'forceTranslation' => true,
                    'allowOverrides' => true,
                ],
                'app' => [
                    'class' => PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@craftcms/resources/translations',
                    'forceTranslation' => true,
                    'allowOverrides' => true,
                ],
                'site' => [
                    'class' => PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@translations',
                    'forceTranslation' => true,
                ],
            ],
        ],

        // Dynamically configured components
        // -------------------------------------------------------------------------

        'cache' => function() {
            $config = App::cacheConfig();

            return Craft::createObject($config);
        },

        'db' => function() {
            $config = App::dbConfig();

            return Craft::createObject($config);
        },

        'db2' => function() {
            $config = App::dbConfig();

            return Craft::createObject($config);
        },

        'formatter' => function() {
            return \craft\i18n\Locale::fromNewLocale(I18N::getFormattingLocale())->getFormatter();
        },

        'formattingLocale' => fn() => \craft\i18n\Locale::fromNewLocale(I18N::getFormattingLocale()),

        'locale' => fn() => \craft\i18n\Locale::fromNewLocale(I18N::getLocale()),

        'mailer' => function() {
            $config = App::mailerConfig();

            return Craft::createObject($config);
        },

        'projectConfig' => function() {
            $config = App::projectConfigConfig();

            return Craft::createObject($config);
        },

        'view' => function() {
            $config = App::viewConfig();

            return Craft::createObject($config);
        },
    ],
];
