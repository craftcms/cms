<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\Lib\ModuleContainer;
use Codeception\Module\Yii2;
use Codeception\PHPUnit\TestCase;
use Codeception\Stub;
use Codeception\TestInterface;
use craft\config\DbConfig;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\errors\InvalidPluginException;
use craft\events\DeleteElementEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;
use craft\queue\BaseJob;
use craft\queue\Queue;
use craft\services\Elements;
use ReflectionException;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use Yii;
use yii\base\Application;
use yii\base\Event;
use yii\base\InvalidArgumentException;
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
        'projectConfig' => null,
        'fullMock' => false,
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
        parent::_initialize();

        $config = $this->_getConfig();
        Craft::$testConfig = $config;

        if ($config['fullMock'] !== true) {
            $this->setupDb();
        }
    }

    /**
     * @param TestInterface $test
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws \yii\base\Exception
     */
    public function _before(TestInterface $test)
    {
        self::$currentTest = $test;

        parent::_before($test);

        // If full mock. Create the mock app and dont perform to any further actions.
        if ($this->_getConfig('fullMock') === true) {
            $mockApp = TestSetup::getMockApp($test);
            \Craft::$app = $mockApp;
            Yii::$app = $mockApp;

            $this->mockModulesAndPlugins($test);

            return;
        }

        // Re-apply project config - Fixtures may have done stuff...
        if ($this->_getConfig('projectConfig')) {
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
     */
    public function _after(TestInterface $test)
    {
        if ($this->_getConfig('fullMock') === true) {
            parent::_after($test);

            return;
        }

        // Ensure elements get hard deleted
        Event::on(Elements::class, Elements::EVENT_BEFORE_DELETE_ELEMENT, function(DeleteElementEvent $event) {
            $event->hardDelete = true;
        });

        parent::_after($test);

        ob_start();

        // Create a Craft::$app object
        TestSetup::warmCraft();

        if ($projectConfig = $this->_getConfig('projectConfig')) {
            // Tests over. Reset the project config to its original state.
            TestSetup::setupProjectConfig($projectConfig['file']);

            \Craft::$app->getProjectConfig()->applyConfigChanges(
                Yaml::parse(file_get_contents($projectConfig['file']))
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

            $dbSetupConfig = $this->_getConfig('dbSetup');

            // Get rid of everything.
            if (isset($dbSetupConfig['clean']) && $dbSetupConfig['clean'] === true) {
                TestSetup::cleanseDb($dbConnection);
            }

            // Setup the project config from the passed file.
            $projectConfig = $this->_getConfig('projectConfig');
            if ($projectConfig && isset($projectConfig['file'])) {
                // Just set it up.
                TestSetup::setupProjectConfig($projectConfig['file']);
            }

            // Install the db from install.php
            if (isset($dbSetupConfig['setupCraft']) && $dbSetupConfig['setupCraft'] === true) {
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
    public function installPlugin(array $plugin)
    {
        if (!\Craft::$app->getPlugins()->installPlugin($plugin['handle'])) {
            throw new InvalidConfigException('Invalid plugin handle: ' . $plugin['handle'] . '');
        }
    }

    /**
     * @return string
     */
    public static function getCodeceptionName(): string
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
     * @param string $eventInstance
     * @param array $eventValues
     */
    public function expectEvent(
        string $class,
        string $eventName,
        $callback,
        string $eventInstance = '',
        array $eventValues = []
    ) {
        // Add this event.
        $eventTriggered = false;

        // Listen to this event and log it.
        Event::on($class, $eventName, function($event) use (&$eventTriggered, $eventInstance, $eventValues) {
            $eventTriggered = true;

            if ($eventInstance && !$event instanceof $eventInstance) {
                $this->fail("Triggered event is not instance of $eventInstance");
            }

            foreach ($eventValues as $eventValue) {
                $this->validateEventValue($event, $eventValue);
            }
        });

        $callback();

        $this->assertTrue($eventTriggered, 'Asserting that an event is triggered.');
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
     * An easy way of handling the testing of queue jobs.
     *
     * @param string $queueItem
     * @param array $params
     * @throws InvalidArgumentException
     */
    public function runQueue(string $queueItem, array $params = [])
    {
        /* @var BaseJob $job */
        $job = new $queueItem($params);

        if (!$job instanceof BaseJob) {
            throw new InvalidArgumentException('Not a job');
        }

        Craft::$app->getQueue()->push($job);

        Craft::$app->getQueue()->run();
    }

    /**
     * @param string $description
     */
    public function assertPushedToQueue(string $description)
    {
        if (\Craft::$app->getQueue() instanceof Queue) {
            $this->assertTrue((new Query())
                ->select('id')
                ->where(['description' => $description])
                ->from(Table::QUEUE)
                ->exists()
            );
        }
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
     * @param array $config
     * @return array
     */
    public function createEventItems(array $config = []): array
    {
        $items = [];
        foreach ($config as $configItem) {
            $items[] = new EventItem($configItem);
        }

        return $items;
    }

    /**
     * Creates a DB config according to the loaded .env variables.
     *
     * @return DbConfig
     */
    public static function createDbConfig(): DbConfig
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
     * @param $event
     * @param EventItem $eventRequirements
     */
    protected function validateEventValue($event, EventItem $eventRequirements)
    {
        $eventPropItem = $event->{$eventRequirements->eventPropName};
        $desiredValue = $eventRequirements->desiredValue;

        // Its a class. Special compare requirements exist.
        if ($eventRequirements->type === EventItem::TYPE_CLASS) {
            $this->assertInstanceOf(
                $eventRequirements->desiredClass,
                $eventPropItem
            );

            // Compare the properties form the $desiredValue array based on key value.
            if (is_array($desiredValue) || is_object($desiredValue)) {
                foreach ($desiredValue as $key => $value) {
                    $this->assertSame(
                        $value,
                        $eventPropItem->{$key}
                    );
                }
            }
        }

        // Is not a class, i.e. a string, array, bool e.t.c.
        if ($eventRequirements->type === EventItem::TYPE_OTHERVALUE) {
            $this->assertSame(
                $desiredValue,
                $eventPropItem
            );
        }
    }

    /**
     * @param TestCase $test
     * @throws ReflectionException
     */
    protected function mockModulesAndPlugins(TestCase $test)
    {
        foreach ($this->_getConfig('plugins') as $plugin) {
            $moduleClass = $plugin['class'];

            $this->addModule($test, $moduleClass);
        }

        $config = TestSetup::createConfigService();
        foreach ($config->getConfigFromFile('app')['modules'] ?? [] as $handle => $class) {
            $this->addModule($test, $class);
        }
    }

    /**
     * @param TestCase $test
     * @param string $moduleClass
     * @throws ReflectionException
     */
    protected function addModule(TestCase $test, string $moduleClass)
    {
        if (!method_exists($moduleClass, 'getComponentMap')) {
            return;
        }

        $componentMap = $moduleClass::getComponentMap();

        // Set it.
        \Craft::$app->loadedModules[$moduleClass] = TestSetup::getMockApp($test, $componentMap, $moduleClass);
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
}
