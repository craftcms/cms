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
use craft\base\ElementInterface;
use craft\config\DbConfig;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidPluginException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\ProjectConfig;
use craft\models\FieldLayout;
use craft\queue\BaseJob;
use craft\queue\Queue;
use DateTime;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\Application;
use yii\base\ErrorException as YiiBaseErrorException;
use yii\base\Event;
use yii\base\Exception as YiiBaseException;
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
 * @since 3.2.0
 */
class Craft extends Yii2
{
    /**
     * @var self The current instance
     */
    public static $instance;

    /**
     * @var TestInterface
     */
    public static $currentTest;

    /**
     * @var array Application config file must be set.
     */
    protected $addedConfig = [
        'migrations' => [],
        'plugins' => [],
        'setupDb' => null,
        'projectConfig' => null,
        'fullMock' => false,
        'edition' => \Craft::Solo
    ];

    /**
     * @var array For expecting events code
     */
    protected $triggeredEvents = [];

    /**
     * @var array For expecting events code
     */
    protected $requiredEvents = [];

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
     * @inheritdoc
     */
    public function _initialize()
    {
        parent::_initialize();

        self::$instance = $this;

        if ($this->_getConfig('fullMock') !== true) {
            $this->setupDb();
        }
    }

    /**
     * @throws YiiBaseErrorException
     */
    public function _afterSuite()
    {
        parent::_afterSuite();

        if (TestSetup::useProjectConfig()) {
            TestSetup::removeProjectConfigFolders(CRAFT_CONFIG_PATH . DIRECTORY_SEPARATOR . 'project');
        }
    }

    /**
     * @param TestInterface $test
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     * @throws YiiBaseErrorException
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

        $this->resetProjectConfig();

        $db = \Craft::createObject(
            App::dbConfig(self::createDbConfig())
        );

        \Craft::$app->set('db', $db);
    }

    /**
     * Reset's the project config.
     *
     * @param bool $force Whether to force the reset. If set to true the `reset` key of the projectConfig configuration will
     * be ignored and the project config will be reset regardless.
     * @return bool
     * @since 3.3.10
     */
    public function resetProjectConfig(bool $force = false): bool
    {
        $projectConfig = $this->_getConfig('projectConfig');

        // If reset is disabled and we dont have to $force we can abandon....
        if (isset($projectConfig['reset']) && $projectConfig['reset'] === false && $force === false) {
            return true;
        }

        // Re-apply project config
        if ($projectConfig = TestSetup::useProjectConfig()) {
            // Tests just beginning. Reset the project config to its original state.
            TestSetup::setupProjectConfig();

            \Craft::$app->getProjectConfig()->applyConfigChanges(
                TestSetup::getSeedProjectConfigData()
            );

            \Craft::$app->getProjectConfig()->saveModifiedConfigData();
        } else {
            \Craft::$app->getProjectConfig()->rebuild();

            // We also manually set the edition if desired by the current config
            $edition = $this->_getConfig('edition');
            if (is_int($edition)) {
                \Craft::$app->setEdition(
                    $edition
                );
            }
        }

        return true;
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

            // Prevent's a static properties bug.
            ProjectConfig::reset();

            App::maxPowerCaptain();

            $dbConnection = \Craft::createObject(App::dbConfig(self::createDbConfig()));

            if (!$dbConnection instanceof Connection) {
                throw new Exception('Unable to establish a DB connection to setup the DB');
            }

            \Craft::$app->set('db', $dbConnection);

            $dbSetupConfig = $this->_getConfig('dbSetup');

            // Setup the project config from the passed file.
            if ($projectConfig = TestSetup::useProjectConfig()) {
                TestSetup::setupProjectConfig();
            }

            // Get rid of everything.
            if (isset($dbSetupConfig['clean']) && $dbSetupConfig['clean'] === true) {
                TestSetup::cleanseDb($dbConnection);
            }

            // Install the db from install.php
            if (isset($dbSetupConfig['setupCraft']) && $dbSetupConfig['setupCraft'] === true) {
                TestSetup::setupCraftDb($dbConnection);
            }

            // Ready to rock.
            \Craft::$app->setIsInstalled();

            if (isset($dbSetupConfig['applyMigrations']) && $dbSetupConfig['applyMigrations'] === true) {
                \Craft::$app->getContentMigrator()->up();
            }

            // Apply migrations
            if ($migrations = $this->_getConfig('migrations')) {
                foreach ($migrations as $migration) {
                    TestSetup::validateAndApplyMigration($migration['class'], $migration['params'], true);
                }
            }

            // Add any plugins
            if ($plugins = $this->_getConfig('plugins')) {
                foreach ($plugins as $plugin) {
                    $this->installPlugin($plugin);
                }
            }

            // Trigger the end of a 'request'. This lets project config do its stuff.
            // TODO: Probably Craft::$app->getProjectConfig->saveModifiedConfigData() but i feel the below is more solid.
            \Craft::$app->state = Application::STATE_END;
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
     * @return string|bool
     */
    public static function normalizePathSeparators($path)
    {
        return is_string($path) ? str_replace("\\", '/', $path) : false;
    }


    /**
     * Creates a DB config according to the loaded .env variables.
     *
     * @return DbConfig
     */
    public static function createDbConfig(): DbConfig
    {
        return new DbConfig([
            'dsn' => App::env('DB_DSN'),
            'user' => App::env('DB_USER'),
            'password' => App::env('DB_PASSWORD'),
            'tablePrefix' => App::env('DB_TABLE_PREFIX'),
            'schema' => App::env('DB_SCHEMA'),
        ]);
    }

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
     * @param ElementInterface $element
     * @param bool $failHard
     * @return bool
     * @throws ElementNotFoundException
     * @throws Throwable
     * @throws YiiBaseException
     */
    public function saveElement(ElementInterface $element, bool $failHard = true): bool
    {
        if (!\Craft::$app->getElements()->saveElement($element)) {
            if ($failHard) {
                throw new InvalidArgumentException(
                    implode(', ', $element->getErrorSummary(true))
                );
            }

            return false;
        }

        return true;
    }

    /**
     * @param string $elementType
     * @param array $searchProperties
     * @param int $amount
     * @param bool $searchAll - Wether anyStatus() and trashed(null) should be applied
     * @return array
     */
    public function assertElementsExist(string $elementType, array $searchProperties = [], int $amount = 1, bool $searchAll = false): array
    {
        /* @var ElementQuery $elementQuery */
        $elementQuery = $elementType::find();
        if ($searchAll) {
            $elementQuery->anyStatus();
            $elementQuery->trashed(null);
        }

        foreach ($searchProperties as $searchProperty => $value) {
            $elementQuery->$searchProperty = $value;
        }

        $elements = $elementQuery->all();
        $this->assertCount($amount, $elements);

        return $elements;
    }

    /**
     * @param callable $callable
     * @param string $message
     */
    public function assertTestFails(callable $callable, string $message = '')
    {
        $failed = false;
        try {
            $callable();
        } catch (ExpectationFailedException $exception) {
            $failed = true;
            if ($message) {
                $this->assertSame($message, $exception->getMessage());
            }

            $this->assertTrue(true, 'Test failed as was expected.');
        }

        if ($failed === false) {
            $this->fail('Test was supposed to fail but didnt.');
        }
    }

    /**
     * @todo Allow passing of DateTime objects as param 2 and 3 - won't be hard to implement.
     * @param TestInterface $test
     * @param string $dateOne
     * @param string $dateTwo
     * @param float $secondsDelta
     * @throws \Exception
     */
    public function assertEqualDates(TestInterface $test, string $dateOne, string $dateTwo, float $secondsDelta = 5.0)
    {
        $dateOne = new DateTime($dateOne);
        $dateTwo = new DateTime($dateTwo);

        if (method_exists($test, 'assertEqualsWithDelta')) {
            $test->assertEqualsWithDelta((float)$dateOne->format('U'), (float)$dateTwo->format('U'), $secondsDelta);
        }
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
                ->select(['id'])
                ->where(['description' => $description])
                ->from([Table::QUEUE])
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

        $layoutId = (new Query())
            ->select(['layoutId'])
            ->from([Table::FIELDLAYOUTFIELDS])
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
