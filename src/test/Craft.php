<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\Lib\ModuleContainer;
use Codeception\Module\Yii2;
use Codeception\Stub;
use Codeception\TestInterface;
use Composer\IO\NullIO;
use craft\composer\Factory;
use craft\composer\Installer;
use craft\config\DbConfig;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\errors\InvalidPluginException;
use craft\errors\SectionNotFoundException;
use craft\events\DeleteElementEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use ReflectionException;
use ReflectionObject;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use yii\base\Application;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\db\Exception;

/**
 * Craft module for codeception
 *
 * @todo LINE 115
 *
 * There is a potential 'bug'/hampering feature with the Yii2 Codeception module.
 * DB connections initialized through the configFile param (see https://codeception.com/docs/modules/Yii2)
 * Are not captured by the Yii2Connector\ConnectionWatcher and Yii2Connector\TransactionForcer i.e. all DB interactions done through
 * Craft::$app->getDb() are not stored and roll'd back in transactions.
 *
 * This is probably because the starting of the app (triggered by $this->client->startApp()) is done BEFORE the
 * DB event listeners are registered. Moving the order of these listeners to the top of the _before function means the connection
 * is registered.
 *
 * What I need to investigate is whether I am doing something wrong in the src/tests/_craft/config/test.php or if this is PR 'worthy'
 * For now: Remounting the DB object using Craft::$app->set() after the event listeners are called works perfectly fine.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class Craft extends Yii2
{
    // Public Properties
    // =========================================================================

    /**
     * A static version of the config for use on the tests/_craft/config/test.php file
     *
     * @var array
     */
    public static $testConfig;

    /**
     * @var TestInterface
     */
    public static $currentTest;

    /**
     * Application config file must be set.
     *
     * @var array
     */
    protected $addedConfig = [
        'plugins' => [],
        'setupDb' => null,
        'projectConfig' => null
    ];

    /**
     * For expecting events code
     *
     * @var array
     */
    protected $triggeredEvents = [];

    /**
     * For expecting events code
     *
     * @var array
     */
    protected $requiredEvents = [];

    // Public Methods
    // =========================================================================

    /**
     * Craft constructor.
     *
     * We need to merge the config settings here as this is the earliest point in the instance's existence.
     * Doing it in _initialize() won't work as the config variables have already been added.
     *
     * @param ModuleContainer $moduleContainer
     * @param null $config
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        // Merge our config with Yii'2 config.
        $this->config = array_merge($this->_getConfig(), $this->addedConfig);

        parent::__construct($moduleContainer, $config);
    }

    /**
     * @inheritDoc
     */
    public function _initialize()
    {
        Craft::$testConfig = $this->_getConfig();
        $this->setupDb();

        parent::_initialize();
    }

    /**
     * @inheritDoc
     */
    public function _before(TestInterface $test)
    {
        self::$currentTest = $test;

        parent::_before($test);

        // Re-apply project config - Fixtures may have done stuff...
        if ($this->refreshProjectConfigPerTest()) {
            \Craft::$app->getProjectConfig()->applyYamlChanges();
        }

        App::maxPowerCaptain();

        $db = \Craft::createObject(
            App::dbConfig(self::createDbConfig())
        );

        \Craft::$app->set('db', $db);
    }

    /**
     * @param TestInterface $test
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws SectionNotFoundException
     */
    public function _after(TestInterface $test)
    {
        // Ensure elements get hard deleted
        Event::on(Elements::class, Elements::EVENT_BEFORE_DELETE_ELEMENT, function(DeleteElementEvent $event) {
            $event->hardDelete = true;
        });

        parent::_after($test);

        ob_start();

        // Create a Craft::$app object
        TestSetup::warmCraft();

        if ($this->refreshProjectConfigPerTest()) {
            // Tests over. Reset the project config to its original state.
            TestSetup::setupProjectConfig($this->_getConfig('projectConfig')['file']);

            \Craft::$app->getProjectConfig()->applyConfigChanges(
                Yaml::parse(file_get_contents($this->_getConfig('projectConfig')['file']))
            );
        }

        \Craft::$app->trigger(Application::EVENT_AFTER_REQUEST);
        // Dont output anything or we get header's already sent exception
        ob_end_clean();
        TestSetup::tearDownCraft();
    }

    /**
     * @throws Throwable
     */
    public function setupDb()
    {
        ob_start();

        try {
            // Create a Craft::$app object
            TestSetup::warmCraft();

            $dbConnection = \Craft::createObject(App::dbConfig(self::createDbConfig()));

            if (!$dbConnection instanceof Connection) {
                throw new Exception('Unable to establish a DB connection to setup the DB');
            }

            \Craft::$app->set('db', $dbConnection);

            // Get rid of everything.
            if ($this->_getConfig('dbSetup')['clean'] === true) {
                TestSetup::cleanseDb($dbConnection);
            }

            // Setup the project config from the passed file.
            $projectConfig = $this->_getConfig('projectConfig');
            if ($projectConfig && isset($projectConfig['file'])) {
                // Just set it up.
                TestSetup::setupProjectConfig($projectConfig['file']);
            }

            // Install the db from install.php
            if ($this->_getConfig('dbSetup')['setupCraft'] === true) {
                TestSetup::setupCraftDb($dbConnection, $this);
            }

            // Ready to rock.
            \Craft::$app->setIsInstalled();

            // Apply migrations
            if ($migrations = $this->_getConfig('migrations')) {
                foreach ($migrations as $migration) {
                    TestSetup::validateAndApplyMigration($migration['class'], $migration['params']);
                }
            }

            // Add any plugins
            foreach ($this->_getConfig('plugins') as $plugin) {
                $this->installPlugin($plugin);
            }

            // Trigger the end of a 'request'. This lets project config do its stuff.
            // TODO: Probably Craft::$app->getProjectConfig->saveModifiedConfigData() but i feel the below is more solid.
            \Craft::$app->trigger(Application::EVENT_AFTER_REQUEST);
        } catch (Throwable $exception) {
            // Get clean and throw a tantrum.
            ob_end_clean();
            throw $exception;
        }

        // Dont output anything or we get header's already sent exception
        ob_end_clean();
        TestSetup::tearDownCraft();
    }

    /**
     * @param array $plugin
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws InvalidPluginException
     */
    public function installPlugin(array $plugin) {
        if (isset($plugin['isAtRoot']) && $plugin['isAtRoot'] === true) {
            $this->addPluginFromRoot($plugin);
        }

        if (!\Craft::$app->getPlugins()->installPlugin($plugin['handle'])) {
            throw new InvalidConfigException('Invalid plugin handle: ' . $plugin['handle'] . '');
        }
    }

    /**
     * @return string
     */
    public static function getCodeceptionName() : string
    {
        return '\craft\test\Craft';
    }

    /**
     * @param $path
     * @return string
     */
    public static function normalizePathSeparators($path)
    {
        return is_string($path) ? str_replace("\\", '/', $path) : false;
    }

    // Helpers for test methods
    // =========================================================================

    /**
     * Ensure that an event is triggered by the $callback() function.
     *
     * @param string $class
     * @param string $eventName
     * @param $callback
     */
    public function expectEvent(string $class, string $eventName, $callback)
    {
        // Add this event.
        $requiredEvent = null;

        // Listen to this event and log it.
        Event::on($class, $eventName, function () use (&$requiredEvent) {
            $requiredEvent = true;
        });

        $callback();

        $this->assertTrue($requiredEvent, 'Asserting that an event is triggered.');
    }

    /**
     * @param Module $module
     * @param string $component
     * @param array $methods
     * @param array $constructParams
     * @throws InvalidConfigException
     */
    public function mockMethods(Module $module, string $component, array $methods = [], array $constructParams = [])
    {
        $componentInstance = $module->get($component);

        $module->set($component, Stub::construct(get_class($componentInstance), [$constructParams], $methods));
    }

    /**
     * @param string $component
     * @param array $methods
     * @param array $constructParams
     * @throws InvalidConfigException
     */
    public function mockCraftMethods(string $component, array $methods = [], array $constructParams = [])
    {
        return $this->mockMethods(\Craft::$app, $component, $methods, $constructParams);
    }

    /**
     * @param string $fieldHandle
     * @return FieldLayout|null
     */
    public function getFieldLayoutByFieldHandle(string $fieldHandle)
    {
        if (!$field = \Craft::$app->getFields()->getFieldByHandle($fieldHandle)) {
            return null;
        }

        $layoutId = (new Query())->select('layoutId')
            ->from(Table::FIELDLAYOUTFIELDS)
            ->where(['fieldId' => $field->id])
            ->column();

        if ($layoutId) {
            $layoutId = ArrayHelper::firstValue($layoutId);
            return \Craft::$app->getFields()->getLayoutById($layoutId);
        }

        return null;
    }

    /**
     * Creates a DB config according to the loaded .env variables.
     *
     * @return DbConfig
     */
    public static function createDbConfig() : DbConfig
    {
        return new DbConfig([
            'password' => getenv('DB_PASSWORD'),
            'user' => getenv('DB_USER'),
            'database' => getenv('DB_DATABASE'),
            'tablePrefix' => getenv('DB_TABLE_PREFIX'),
            'driver' => getenv('DB_DRIVER'),
            'port' => getenv('DB_PORT'),
            'schema' => getenv('DB_SCHEMA'),
            'server' => getenv('DB_SERVER'),
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @todo Remove once final version of above is published.
     *
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke
     * @return mixed
     * @throws ReflectionException
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $method = (new ReflectionObject($object))->getMethod($method);
        $method->setAccessible(true);

        $result = $method->invokeArgs($object, $args);

        if ($revoke) {
            $method->setAccessible(false);
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * Completely based on parent except we use CraftConnector. Gives us more control
     */
    protected function recreateClient()
    {
        $entryUrl = $this->_getConfig('entryUrl');
        $entryFile = $this->_getConfig('entryScript') ?: basename($entryUrl);
        $entryScript = $this->_getConfig('entryScript') ?: parse_url($entryUrl, PHP_URL_PATH);

        $this->client = new CraftConnector([
            'SCRIPT_FILENAME' => $entryFile,
            'SCRIPT_NAME' => $entryScript,
            'SERVER_NAME' => parse_url($entryUrl, PHP_URL_HOST),
            'SERVER_PORT' => parse_url($entryUrl, PHP_URL_PORT) ?: '80',
            'HTTPS' => parse_url($entryUrl, PHP_URL_SCHEME) === 'https'
        ]);

        $this->configureClient($this->_getConfig());
    }

    /**
     * Check if the codeception file wants us to set it up only once
     *
     * @return bool
     */
    protected function refreshProjectConfigPerTest() : bool
    {
        $projectConfig = $this->_getConfig('projectConfig');

        if(!$projectConfig) {
            return false;
        }

        return ($projectConfig && array_key_exists('once', $projectConfig) && $projectConfig['once'] === false);
    }

    /**
     * @todo This is a WIP. Currently its a proof of concept.
     *
     * The problem is how do we update vendor/craftcms/plugins.php file. As far as i can see this is a requirement for ensuring plugins work.
     * Updating this file is difficult if the plugin is not in the /vendors directory. I.E. If it is the project root
     *
     * @param array $pluginArray
     * @throws InvalidPluginException
     * @throws ReflectionException
     *
     * @internal This is not the final version.
     * 1. Is there a better what to accessing the plugins.php file than creating a composerInstance? Surely there is.
     * 2. If not. Should the craft plugin installer be edited. Namely the addPlugin method be made public.
     *
     * Basically we can sum up this todo down to: How are we going to ensure that vendor/craftcms/plugins.php contains the plugins defined by the codeception file.
     */
    protected function addPluginFromRoot(array $pluginArray)
    {
        $rootPath = dirname(CRAFT_VENDOR_PATH);

        if (!is_file($rootPath . '/composer.json')) {
            throw new InvalidPluginException($pluginArray['handle'], 'Selected plugin to be at root, but it is not.');
        }

        $composer = (new Factory())->createComposer(new NullIO());

        $installer = new Installer(new NullIO(), $composer);

        $package = $composer->getPackage();

        $package->setExtra(
            ArrayHelper::merge(
                $package->getExtra(),
                ['basePath' => $rootPath . '/src', 'class' => $pluginArray['class']]
            )
        );

        $this->invokeMethod($installer, 'addPlugin', [$package]);
    }
}
