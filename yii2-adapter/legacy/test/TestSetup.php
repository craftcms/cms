<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\PHPUnit\TestCase as CodeceptionTestCase;
use Craft;
use craft\console\Application as ConsoleApplication;
use craft\db\Connection;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\i18n\Locale;
use craft\mail\Mailer;
use craft\queue\QueueComponent;
use craft\services\AssetIndexer;
use craft\services\Assets;
use craft\services\Categories;
use craft\services\Config;
use craft\services\Dashboard;
use craft\services\Deprecator;
use craft\services\Elements;
use craft\services\ElementSources;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Globals;
use craft\services\Images;
use craft\services\ImageTransforms;
use craft\services\Path;
use craft\services\Plugins;
use craft\services\Relations;
use craft\services\Routes;
use craft\services\Search;
use craft\services\Sites;
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
use craft\test\console\ConsoleTest;
use craft\test\Craft as CraftTest;
use craft\web\Application as WebApplication;
use craft\web\ErrorHandler;
use craft\web\Request;
use craft\web\Response;
use craft\web\Session;
use craft\web\UploadedFile;
use craft\web\User;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Migrations\Event\PostCreateTables;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config as ConfigFacade;
use Illuminate\Support\Facades\Event as LaravelEvent;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use yii\base\ErrorException;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\db\Exception;
use yii\mutex\Mutex;

/**
 * Class TestSetup.
 *
 * TestSetup performs various setup tasks required for craft\test\Craft.
 * It is not intended for use within public tests.
 * Use the various features of `craft\test\Craft` instead.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 *
 * @since 3.2
 */
class TestSetup
{
    /**
     * @since 3.6.0
     */
    public const SITE_URL = 'https://localhost/';

    /**
     * @since 3.6.0
     */
    public const USERNAME = 'craftcms';

    /**
     * @var array Project Config data
     */
    private static array $_parsedProjectConfig = [];

    /**
     * @var Config|null An instance of the config service.
     */
    private static ?Config $_configService = null;

    /**
     * Creates a craft object to play with. Ensures the Craft::$app service locator is working.
     *
     * @throws InvalidConfigException
     */
    public static function warmCraft(): mixed
    {
        $app = self::createTestCraftObjectConfig();
        $app['isInstalled'] = false;

        return Craft::createObject($app);
    }

    /**
     * Taken from the Yii2 Module $i->_after
     */
    public static function tearDownCraft(): void
    {
        $_SESSION = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];

        UploadedFile::reset();
        Event::offAll();

        Craft::setLogger(null);

        Craft::$app = null;

        // Reset Yii2 container to prevent singleton accumulation
        \Yii::$container = new \CraftCms\Yii2Adapter\Container();

        // Reset BaseYii statics that accumulate across tests
        \yii\BaseYii::$classMap = [];
        \yii\BaseYii::$aliases = ['@yii' => dirname((new \ReflectionClass(\yii\BaseYii::class))->getFileName())];

        // Reset Yii alias paths cache (private static)
        $ref = new \ReflectionClass(\Yii::class);
        $aliasPaths = $ref->getProperty('_aliasPaths');
        $aliasPaths->setValue(null, []);
        $aliasesChanged = $ref->getProperty('_aliasesChanged');
        $aliasesChanged->setValue(null, false);

        // Reset CustomFieldBehavior static handles
        \craft\behaviors\CustomFieldBehavior::$fieldHandles = [];
        \craft\behaviors\CustomFieldBehavior::$generatedFieldHandles = [];
    }

    /**
     * @throws Exception
     */
    public static function cleanseDb(Connection $connection): bool
    {
        $tables = $connection->schema->getTableNames();

        foreach ($tables as $table) {
            Db::dropAllForeignKeysToTable($table, $connection);
            $connection->createCommand()
                ->dropTable($table)
                ->execute();
        }

        $tables = $connection->schema->getTableNames();

        if ($tables !== []) {
            throw new Exception('Unable to setup test environment.');
        }

        return true;
    }

    public static function createTestCraftObjectConfig(): array
    {
        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
        $_SERVER['REMOTE_PORT'] = 654321;

        $basePath = CraftTest::normalizePathSeparators(dirname(__DIR__, 2));

        $srcPath = $basePath . '/legacy';
        $vendorPath = CRAFT_VENDOR_PATH;

        $appType = self::appType();

        Aliases::set('@craftunitsupport', $srcPath . '/test');
        Aliases::set('@craftunittemplates', $basePath . '/tests/_craft/templates');
        Aliases::set('@craftunitfixtures', $basePath . '/tests/fixtures');
        Aliases::set('@testsfolder', $basePath . '/tests');
        Aliases::set('@crafttestsfolder', $basePath . '/tests/_craft');

        // Normalize some Craft defined path aliases.
        Aliases::set('@lib', CraftTest::normalizePathSeparators(Aliases::get('@lib')));
        Aliases::set('@config', CraftTest::normalizePathSeparators(Aliases::get('@config')));
        Aliases::set('@contentMigrations', CraftTest::normalizePathSeparators(Aliases::get('@contentMigrations')));
        Aliases::set('@storage', CraftTest::normalizePathSeparators(Aliases::get('@storage')));
        Aliases::set('@templates', CraftTest::normalizePathSeparators(Aliases::get('@templates')));
        Aliases::set('@translations', CraftTest::normalizePathSeparators(Aliases::get('@translations')));

        $configService = self::$_configService ?? self::createConfigService();

        $config = Arr::merge(
            [
                'components' => [
                    'config' => $configService,
                ],
            ],
            require $srcPath . '/config/app.php',
            require $srcPath . '/config/app.' . $appType . '.php',
            ConfigFacade::get('craft.app', []),
            ConfigFacade::get("craft.app.$appType", []),
        );

        if (defined('CRAFT_SITE')) {
            $config['components']['sites']['currentSite'] = CRAFT_SITE;
        }

        $config['vendorPath'] = $vendorPath;

        $class = self::appClass($appType);

        return Arr::merge($config, [
            'class' => $class,
            'id' => 'craft-test',
            'env' => 'test',
            'basePath' => $srcPath,
        ]);
    }

    public static function createConfigService(): Config
    {
        $configService = new Config();
        $configService->env = 'test';
        $configService->configDir = CRAFT_CONFIG_PATH;
        $configService->appDefaultsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'defaults';

        return $configService;
    }

    /**
     * Determine the app type (console or web).
     */
    public static function appType(): string
    {
        $appType = 'web';
        if (isset(CraftTest::$currentTest) && CraftTest::$currentTest instanceof ConsoleTest) {
            $appType = 'console';
        }

        return $appType;
    }

    /**
     * @return class-string<ConsoleApplication|WebApplication>
     */
    public static function appClass(string $preDefinedAppType = ''): string
    {
        if (!$preDefinedAppType) {
            $preDefinedAppType = self::appType();
        }

        return $preDefinedAppType === 'console' ? ConsoleApplication::class : WebApplication::class;
    }

    public static function configureCraft(): bool
    {
        !defined('YII_ENV') && define('YII_ENV', 'test');

        $vendorPath = realpath(CRAFT_VENDOR_PATH);

        $configPath = realpath(CRAFT_CONFIG_PATH);
        $contentMigrationsPath = realpath(CRAFT_MIGRATIONS_PATH);
        $rootPath = realpath(CRAFT_ROOT_PATH);
        $storagePath = realpath(CRAFT_STORAGE_PATH);
        $templatesPath = realpath(CRAFT_TEMPLATES_PATH);
        $testsPath = realpath(CRAFT_TESTS_PATH);
        $translationsPath = realpath(CRAFT_TRANSLATIONS_PATH);

        // Log errors to craft/storage/logs/phperrors.log
        ini_set('log_errors', '1');
        ini_set('error_log', $storagePath . '/logs/phperrors.log');

        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', '1');
        defined('YII_DEBUG') || define('YII_DEBUG', true);
        defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', '');

        defined('CURLOPT_TIMEOUT_MS') || define('CURLOPT_TIMEOUT_MS', 155);
        defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

        $repoRoot = dirname(__DIR__, 2);
        $libPath = $repoRoot . '/lib';
        $srcPath = $repoRoot . '/legacy';

        require_once $libPath . '/yii2/Yii.php';
        require_once $srcPath . '/Craft.php';

        // Set aliases
        Aliases::set('@vendor', $vendorPath);
        Aliases::set('@lib', $libPath);
        Aliases::set('@config', $configPath);
        Aliases::set('@contentMigrations', $contentMigrationsPath);
        Aliases::set('@tests', $testsPath);
        Aliases::set('@translations', $translationsPath);

        self::$_configService = self::createConfigService();
        $generalConfig = Cms::config();

        // Set any custom aliases
        $customAliases = $generalConfig->aliases ?? $generalConfig->environmentVariables ?? null;
        if (is_array($customAliases)) {
            foreach ($customAliases as $name => $value) {
                if (is_string($value)) {
                    Aliases::set($name, $value);
                }
            }
        }

        // Prevent `headers already sent` error when running tests in PhpStorm
        // https://stackoverflow.com/questions/31175636/headers-already-sent-running-unit-tests-in-phpstorm
        ob_start();

        return true;
    }

    /**
     * @param  string|null  $projectConfigFolder  - Whether to override the folder specified in codeception.yml with a custom folder.
     *
     * @throws ErrorException
     */
    public static function setupProjectConfig(?string $projectConfigFolder = null): void
    {
        if (!$projectConfigFolder) {
            $config = \craft\test\Craft::$instance->_getConfig('projectConfig');
            $projectConfigFolder = dirname(CRAFT_TESTS_PATH) . DIRECTORY_SEPARATOR . $config['folder'];
        }

        if (!is_dir($projectConfigFolder)) {
            throw new InvalidArgumentException('Project config folder does not exist.');
        }

        $dest = CRAFT_CONFIG_PATH . DIRECTORY_SEPARATOR . 'project';

        // Remove any existing folders.
        self::removeProjectConfigFolders($dest);

        // Copy the data over.
        FileHelper::copyDirectory($projectConfigFolder, $dest);
    }

    /**
     * @throws ErrorException
     */
    public static function removeProjectConfigFolders(string $path): void
    {
        // Clear any existing.
        if (is_dir($path)) {
            FileHelper::removeDirectory($path);
        }
    }

    /**
     * Returns the data from the project.yml file specified in the codeception.yml file.
     *
     * @return array The project config in either yaml or as an array.
     */
    public static function getSeedProjectConfigData(): array
    {
        if (!empty(self::$_parsedProjectConfig)) {
            return self::$_parsedProjectConfig;
        }

        return self::$_parsedProjectConfig = app(ProjectConfig::class)->get(null, true);
    }

    /**
     * Whether project config should be used in tests.
     *
     * Returns the projectConfig configuration array if yes - `false` if not.
     */
    public static function useProjectConfig(): array|false
    {
        $config = \craft\test\Craft::$instance->_getConfig('projectConfig');

        if (!isset($config['folder'])) {
            return false;
        }

        return $config;
    }

    /**
     * @throws Exception
     */
    public static function setupCraftDb(Connection $connection): void
    {
        if ($connection->schema->getTableNames() !== []) {
            throw new Exception('Not allowed to setup the DB if it has not been cleansed');
        }

        $siteConfig = [
            'name' => 'Craft test site',
            'handle' => 'defaultSite',
            'hasUrls' => true,
            'baseUrl' => self::SITE_URL,
            'language' => 'en-US',
            'primary' => true,
        ];

        // Replace the default site with what is desired by the project config. If project config is enabled.
        if (self::useProjectConfig()) {
            $existingProjectConfig = self::getSeedProjectConfigData();

            if ($existingProjectConfig && isset($existingProjectConfig['sites'])) {
                $doesConfigExist = Collection::make($existingProjectConfig['sites'])
                    ->firstWhere('primary', true);

                if ($doesConfigExist) {
                    $siteConfig = $doesConfigExist;

                    // This isn't a `settable` property of craft/models/Site
                    unset($siteConfig['siteGroup']);
                }
            }
        }

        $site = new Site($siteConfig);

        LaravelEvent::listen(PostCreateTables::class, function() {
            Artisan::call('craft:add-categories-support', [
                '--force' => true,
            ]);
            Artisan::call('craft:add-global-sets-support', [
                '--force' => true,
            ]);
            Artisan::call('craft:add-tags-support', [
                '--force' => true,
            ]);
        });

        $migration = new Install(
            username: self::USERNAME,
            password: 'craftcms2018!!',
            email: 'support@craftcms.com',
            site: $site,
        );

        $migration->up();
    }

    /**
     * @template T of Module
     *
     * @param  class-string<T>|null  $moduleClass
     * @return T
     *
     * @credit https://github.com/nerds-and-company/schematic/blob/master/tests/_support/Helper/Unit.php
     */
    public static function getMockModule(CodeceptionTestCase $test, array $serviceMap = [], ?string $moduleClass = null): Module
    {
        $moduleClass ??= self::appClass();
        $serviceMap = $serviceMap ?: self::getCraftServiceMap();

        $mockApp = self::getMock($test, $moduleClass);

        $mockMapForMagicGet = [];

        foreach ($serviceMap as $craftComponent) {
            $class = $craftComponent[0];
            [$accessMethod, $accessProperty] = $craftComponent[1];

            // Create a mock.
            $mock = self::getMock($test, $class);

            // Set the `ServiceLocator::$object->property` magic getter
            if ($accessProperty) {
                // Set the map.
                $mockMapForMagicGet[] = [$accessProperty, $mock];
            }

            // Set the ServiceLocator::$object->getProperty()` get method.
            if ($accessMethod) {
                $class = new ReflectionClass($test);
                $method = $class->getMethod('any');

                $mockApp->expects($method->invoke($test))
                    ->method($accessMethod)
                    ->willReturn($mock);
            }
        }

        $class = new ReflectionClass($test);
        $method = $class->getMethod('any');

        // Set the map
        $mockApp->expects($method->invoke($test))
            ->method('__get')
            ->willReturnMap($mockMapForMagicGet);

        return $mockApp;
    }

    /**
     * @template T
     *
     * @param  class-string<T>  $class
     * @return T|MockObject
     *
     * @credit https://github.com/nerds-and-company/schematic/blob/master/tests/_support/Helper/Unit.php
     */
    public static function getMock(CodeceptionTestCase $test, string $class)
    {
        $reflection = new ReflectionClass($test);
        $method = $reflection->getMethod('getMockBuilder');

        return $method->invokeArgs($test, [$class])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @todo Missed any?
     */
    public static function getCraftServiceMap(): array
    {
        $map = [
            [Assets::class, ['getAssets', 'assets']],
            [AssetIndexer::class, ['getAssetIndexer', 'assetIndexer']],
            [ImageTransforms::class, ['getImageTransforms', 'imageTransforms']],
            [Categories::class, ['getCategories', 'categories']],
            [Config::class, ['getConfig', 'config']],
            [Dashboard::class, ['getDashboard', 'dashboard']],
            [Deprecator::class, ['getDeprecator', 'deprecator']],
            [ElementSources::class, ['getElementSources', 'elementSources']],
            [Elements::class, ['getElements', 'elements']],
            [SystemMessages::class, ['getSystemMessages', 'systemMessages']],
            [Entries::class, ['getEntries', 'entries']],
            [Fields::class, ['getFields', 'fields']],
            [Globals::class, ['getGlobals', 'globals']],
            [Images::class, ['getImages', 'images']],
            [Locale::class, ['getLocale', 'locale']],
            [Mailer::class, ['getMailer', 'mailer']],
            [Mutex::class, ['getMutex', 'mutex']],
            [Path::class, ['getPath', 'path']],
            [Plugins::class, ['getPlugins', 'plugins']],
            [\craft\services\ProjectConfig::class, ['getProjectConfig', 'projectConfig']],
            [QueueComponent::class, ['getQueue', 'queue']],
            [Relations::class, ['getRelations', 'relations']],
            [Routes::class, ['getRoutes', 'routes']],
            [Search::class, ['getSearch', 'search']],
            [Sites::class, ['getSites', 'sites']],
            [Structures::class, ['getStructures', 'structures']],
            [SystemMessages::class, ['getSystemMessages', 'systemMessages']],
            [Tags::class, ['getTags', 'tags']],
            [TemplateCaches::class, ['getTemplateCaches', 'templateCaches']],
            [Tokens::class, ['getTokens', 'tokens']],
            [UserGroups::class, ['getUserGroups', 'userGroups']],
            [UserPermissions::class, ['getUserPermissions', 'userPermissions']],
            [Users::class, ['getUsers', 'users']],
            [Utilities::class, ['getUtilities', 'utilities']],
            [Volumes::class, ['getVolumes', 'volumes']],
        ];

        $appType = self::appType();

        if ($appType === 'web') {
            $map = Arr::merge($map, [
                [Request::class, ['getRequest', 'request']],
                [Session::class, ['getSession', 'session']],
                [ErrorHandler::class, ['getErrorHandler', 'errorHandler']],
                [Response::class, ['getResponse', 'response']],
                [User::class, ['getUser', 'user']],
            ]);
        }

        if ($appType === 'console') {
            $map = Arr::merge($map, [
                [\craft\console\Request::class, ['getRequest', 'request']],
                [\yii\console\ErrorHandler::class, ['getErrorHandler', 'errorHandler']],
                [\yii\console\Response::class, ['getResponse', 'response']],
                [\craft\console\User::class, ['getUser', 'user']],
            ]);
        }

        return $map;
    }
}
