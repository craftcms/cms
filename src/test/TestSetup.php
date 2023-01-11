<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\PHPUnit\TestCase as CodeceptionTestCase;
use Craft;
use craft\console\Application as ConsoleApplication;
use craft\db\Connection;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\errors\MigrationException;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\i18n\Locale;
use craft\mail\Mailer;
use craft\migrations\Install;
use craft\models\Site;
use craft\queue\Queue;
use craft\services\Api;
use craft\services\AssetIndexer;
use craft\services\Assets;
use craft\services\Categories;
use craft\services\Composer;
use craft\services\Config;
use craft\services\Content;
use craft\services\Dashboard;
use craft\services\Deprecator;
use craft\services\Elements;
use craft\services\ElementSources;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Globals;
use craft\services\Images;
use craft\services\ImageTransforms;
use craft\services\Matrix;
use craft\services\Path;
use craft\services\Plugins;
use craft\services\PluginStore;
use craft\services\ProjectConfig;
use craft\services\Relations;
use craft\services\Routes;
use craft\services\Search;
use craft\services\Sections;
use craft\services\Sites;
use craft\services\Structures;
use craft\services\SystemMessages;
use craft\services\Tags;
use craft\services\TemplateCaches;
use craft\services\Tokens;
use craft\services\Updates;
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
use PHPUnit\Framework\MockObject\MockObject;
use yii\base\ErrorException;
use yii\base\Event;
use yii\base\InvalidArgumentException;
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
 * @since 3.2
 */
class TestSetup
{
    /**
     * @since 3.6.0
     */
    public const SITE_URL = 'https://test.craftcms.test/';

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
     * @return mixed
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
        if (method_exists(Event::class, 'offAll')) {
            Event::offAll();
        }

        Craft::setLogger(null);

        Craft::$app = null;
    }

    /**
     * @param Connection $connection
     * @return bool
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

    /**
     * @param string $class
     * @phpstan-param class-string<Migration> $class
     * @param array $params
     * @param bool $ignorePreviousMigrations
     * @return bool
     * @throws InvalidConfigException
     * @throws MigrationException
     */
    public static function validateAndApplyMigration(string $class, array $params, bool $ignorePreviousMigrations = false): bool
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Migration class: ' . $class . ' does not exist');
        }

        $migration = new $class($params);

        if (!$migration instanceof Migration) {
            throw new InvalidArgumentException(
                'Migration class is not an instance of: ' . Migration::class
            );
        }

        $contentMigrator = Craft::$app->getContentMigrator();
        // Should we ignore this migration?
        if ($ignorePreviousMigrations) {
            $history = $contentMigrator->getMigrationHistory();

            // Technically... This migration is applied.
            if (isset($history[$class])) {
                return true;
            }
        }

        $contentMigrator->migrateUp($migration);

        return true;
    }

    /**
     * @return array
     */
    public static function createTestCraftObjectConfig(): array
    {
        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
        $_SERVER['REMOTE_PORT'] = 654321;

        $basePath = CraftTest::normalizePathSeparators(dirname(__DIR__, 2));

        $srcPath = $basePath . '/src';
        $vendorPath = CRAFT_VENDOR_PATH;

        $appType = self::appType();

        Craft::setAlias('@craftunitsupport', $srcPath . '/test');
        Craft::setAlias('@craftunittemplates', $basePath . '/tests/_craft/templates');
        Craft::setAlias('@craftunitfixtures', $basePath . '/tests/fixtures');
        Craft::setAlias('@testsfolder', $basePath . '/tests');
        Craft::setAlias('@crafttestsfolder', $basePath . '/tests/_craft');

        // Normalize some Craft defined path aliases.
        Craft::setAlias('@craft', CraftTest::normalizePathSeparators(Craft::getAlias('@craft')));
        Craft::setAlias('@lib', CraftTest::normalizePathSeparators(Craft::getAlias('@lib')));
        Craft::setAlias('@config', CraftTest::normalizePathSeparators(Craft::getAlias('@config')));
        Craft::setAlias('@contentMigrations', CraftTest::normalizePathSeparators(Craft::getAlias('@contentMigrations')));
        Craft::setAlias('@storage', CraftTest::normalizePathSeparators(Craft::getAlias('@storage')));
        Craft::setAlias('@templates', CraftTest::normalizePathSeparators(Craft::getAlias('@templates')));
        Craft::setAlias('@translations', CraftTest::normalizePathSeparators(Craft::getAlias('@translations')));

        $configService = self::$_configService ?? self::createConfigService();

        $config = ArrayHelper::merge(
            [
                'components' => [
                    'config' => $configService,
                ],
            ],
            require $srcPath . '/config/app.php',
            require $srcPath . '/config/app.' . $appType . '.php',
            $configService->getConfigFromFile('app'),
            $configService->getConfigFromFile("app.$appType")
        );

        if (defined('CRAFT_SITE')) {
            $config['components']['sites']['currentSite'] = CRAFT_SITE;
        }

        $config['vendorPath'] = $vendorPath;

        $class = self::appClass($appType);

        return ArrayHelper::merge($config, [
            'class' => $class,
            'id' => 'craft-test',
            'env' => 'test',
            'basePath' => $srcPath,
        ]);
    }

    /**
     * @return Config
     */
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
     *
     * @return string
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
     * @param string $preDefinedAppType
     * @return string
     * @phpstan-return class-string<ConsoleApplication|WebApplication>
     */
    public static function appClass(string $preDefinedAppType = ''): string
    {
        if (!$preDefinedAppType) {
            $preDefinedAppType = self::appType();
        }

        return $preDefinedAppType === 'console' ? ConsoleApplication::class : WebApplication::class;
    }

    /**
     * @return bool
     */
    public static function configureCraft(): bool
    {
        define('YII_ENV', 'test');

        $vendorPath = realpath(CRAFT_VENDOR_PATH);

        $configPath = realpath(CRAFT_CONFIG_PATH);
        $contentMigrationsPath = realpath(CRAFT_MIGRATIONS_PATH);
        $storagePath = realpath(CRAFT_STORAGE_PATH);
        $templatesPath = realpath(CRAFT_TEMPLATES_PATH);
        $testsPath = realpath(CRAFT_TESTS_PATH);
        $translationsPath = realpath(CRAFT_TRANSLATIONS_PATH);

        // Log errors to craft/storage/logs/phperrors.log
        ini_set('log_errors', '1');
        ini_set('error_log', $storagePath . '/logs/phperrors.log');

        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        defined('YII_DEBUG') || define('YII_DEBUG', true);
        defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', '');

        defined('CURLOPT_TIMEOUT_MS') || define('CURLOPT_TIMEOUT_MS', 155);
        defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

        $repoRoot = dirname(__DIR__, 2);
        $libPath = $repoRoot . '/lib';
        $srcPath = $repoRoot . '/src';

        require $libPath . '/yii2/Yii.php';
        require $srcPath . '/Craft.php';

        // Set aliases
        Craft::setAlias('@vendor', $vendorPath);
        Craft::setAlias('@craftcms', $repoRoot);
        Craft::setAlias('@lib', $libPath);
        Craft::setAlias('@craft', $srcPath);
        Craft::setAlias('@appicons', $srcPath . DIRECTORY_SEPARATOR . 'icons');
        Craft::setAlias('@config', $configPath);
        Craft::setAlias('@contentMigrations', $contentMigrationsPath);
        Craft::setAlias('@storage', $storagePath);
        Craft::setAlias('@templates', $templatesPath);
        Craft::setAlias('@tests', $testsPath);
        Craft::setAlias('@translations', $translationsPath);

        self::$_configService = self::createConfigService();
        $generalConfig = self::$_configService->getConfigFromFile('general');

        // Set any custom aliases
        $customAliases = $generalConfig['aliases'] ?? $generalConfig['environmentVariables'] ?? null;
        if (is_array($customAliases)) {
            foreach ($customAliases as $name => $value) {
                if (is_string($value)) {
                    Craft::setAlias($name, $value);
                }
            }
        }

        // Prevent `headers already sent` error when running tests in PhpStorm
        // https://stackoverflow.com/questions/31175636/headers-already-sent-running-unit-tests-in-phpstorm
        ob_start();

        return true;
    }

    /**
     * @param string|null $projectConfigFolder - Whether to override the folder specified in codeception.yml with a custom folder.
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
     * @param string $path
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

        return self::$_parsedProjectConfig = Craft::$app->getProjectConfig()->get(null, true);
    }

    /**
     * Whether project config should be used in tests.
     *
     * Returns the projectConfig configuration array if yes - `false` if not.
     *
     * @return array|false
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
     * @param Connection $connection
     * @throws Exception
     */
    public static function setupCraftDb(Connection $connection): void
    {
        if ($connection->schema->getTableNames() !== []) {
            throw new Exception('Not allowed to setup the DB if it has not been cleansed');
        }

        $siteConfig = [
            'name' => 'Craft test site',
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => self::SITE_URL,
            'language' => 'en-US',
            'primary' => true,
        ];

        // Replace the default site with what is desired by the project config. If project config is enabled.
        if (self::useProjectConfig()) {
            $existingProjectConfig = self::getSeedProjectConfigData();

            if ($existingProjectConfig && isset($existingProjectConfig['sites'])) {
                $doesConfigExist = ArrayHelper::firstWhere(
                    $existingProjectConfig['sites'],
                    'primary'
                );

                if ($doesConfigExist) {
                    $siteConfig = $doesConfigExist;

                    // This isn't a `settable` property of craft/models/Site
                    unset($siteConfig['siteGroup']);
                }
            }
        }

        $site = new Site($siteConfig);

        $migration = new Install([
            'db' => $connection,
            'username' => self::USERNAME,
            'password' => 'craftcms2018!!',
            'email' => 'support@craftcms.com',
            'site' => $site,
        ]);

        $migration->safeUp();
    }

    /**
     * @template T of Module
     * @param CodeceptionTestCase $test
     * @param array $serviceMap
     * @param string|null $moduleClass
     * @phpstan-param class-string<T>|null $moduleClass
     * @return T
     * @credit https://github.com/nerds-and-company/schematic/blob/master/tests/_support/Helper/Unit.php
     */
    public static function getMockModule(CodeceptionTestCase $test, array $serviceMap = [], ?string $moduleClass = null): Module
    {
        $moduleClass = $moduleClass ?? self::appClass();
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
                $mockApp->expects($test->any())
                    ->method($accessMethod)
                    ->willReturn($mock);
            }
        }

        // Set the map
        $mockApp->expects($test->any())
            ->method('__get')
            ->willReturnMap($mockMapForMagicGet);

        return $mockApp;
    }

    /**
     * @template T
     * @param CodeceptionTestCase $test
     * @param string $class
     * @phpstan-param class-string<T> $class
     * @return T|MockObject
     * @credit https://github.com/nerds-and-company/schematic/blob/master/tests/_support/Helper/Unit.php
     */
    public static function getMock(CodeceptionTestCase $test, string $class)
    {
        return $test->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return array
     * @todo Missed any?
     *
     */
    public static function getCraftServiceMap(): array
    {
        $map = [
            [Api::class, ['getApi', 'api']],
            [Assets::class, ['getAssets', 'assets']],
            [AssetIndexer::class, ['getAssetIndexer', 'assetIndexer']],
            [ImageTransforms::class, ['getImageTransforms', 'imageTransforms']],
            [Categories::class, ['getCategories', 'categories']],
            [Composer::class, ['getComposer', 'composer']],
            [Config::class, ['getConfig', 'config']],
            [Content::class, ['getContent', 'content']],
            [MigrationManager::class, ['getContentMigrator', 'contentMigrator']],
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
            [Matrix::class, ['getMatrix', 'matrix']],
            [MigrationManager::class, ['getMigrator', 'migrator']],
            [Mutex::class, ['getMutex', 'mutex']],
            [Path::class, ['getPath', 'path']],
            [Plugins::class, ['getPlugins', 'plugins']],
            [PluginStore::class, ['getPluginStore', 'pluginStore']],
            [ProjectConfig::class, ['getProjectConfig', 'projectConfig']],
            [Queue::class, ['getQueue', 'queue']],
            [Relations::class, ['getRelations', 'relations']],
            [Routes::class, ['getRoutes', 'routes']],
            [Search::class, ['getSearch', 'search']],
            [Sections::class, ['getSections', 'sections']],
            [Sites::class, ['getSites', 'sites']],
            [Structures::class, ['getStructures', 'structures']],
            [SystemMessages::class, ['getSystemMessages', 'systemMessages']],
            [Tags::class, ['getTags', 'tags']],
            [TemplateCaches::class, ['getTemplateCaches', 'templateCaches']],
            [Tokens::class, ['getTokens', 'tokens']],
            [Updates::class, ['getUpdates', 'updates']],
            [UserGroups::class, ['getUserGroups', 'userGroups']],
            [UserPermissions::class, ['getUserPermissions', 'userPermissions']],
            [Users::class, ['getUsers', 'users']],
            [Utilities::class, ['getUtilities', 'utilities']],
            [Volumes::class, ['getVolumes', 'volumes']],
        ];

        $appType = self::appType();

        if ($appType === 'web') {
            $map = ArrayHelper::merge($map, [
                [Request::class, ['getRequest', 'request']],
                [Session::class, ['getSession', 'session']],
                [ErrorHandler::class, ['getErrorHandler', 'errorHandler']],
                [Response::class, ['getResponse', 'response']],
                [User::class, ['getUser', 'user']],
            ]);
        }

        if ($appType === 'console') {
            $map = ArrayHelper::merge($map, [
                [\craft\console\Request::class, ['getRequest', 'request']],
                [\yii\console\ErrorHandler::class, ['getErrorHandler', 'errorHandler']],
                [\yii\console\Response::class, ['getResponse', 'response']],
                [\craft\console\User::class, ['getUser', 'user']],
            ]);
        }

        return $map;
    }
}
