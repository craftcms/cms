<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craft\test;


use Codeception\Lib\ModuleContainer;
use Codeception\Module\Yii2;
use Codeception\Step;
use Codeception\TestInterface;
use craft\config\DbConfig;
use craft\helpers\App;
use Codeception\Lib\Connector\Yii2 as Yii2Connector;
use yii\base\Event;

/**
 * Craft module for codeception
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class Craft extends Yii2
{
    // Setup work
    // =========================================================================

    /**
     * Application config file must be set.
     * @var array
     */
    protected $addedConfig = [
        'plugins' => [],
        'migrations' => [],
        'modules' => [],
        'setupDb' => null,
    ];

    /**
     * For expecting events code
     * @var array
     */
    protected $triggeredEvents = [];
    protected $requiredEvents = [];

    /**
     * Craft constructor.
     * We need to merge the config settings here as this is the earliest point in the instance's existance.
     * Doing it in _initialize() wont work as the config variables have already been added.
     *
     * @param ModuleContainer $moduleContainer
     * @param null $config
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        // Merge our config with Yii'2 config.
        $this->config = array_merge(parent::_getConfig(), $this->addedConfig);

        parent::__construct($moduleContainer, $config);
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function _initialize()
    {
        // Unless told not to. Lets setup the database.
        if ($this->_getConfig('setupDb') === true) {
            $this->setupDb();
        }

        parent::_initialize();

    }

    /**
     * TODO: Plugin migrations & installations, additional migrations e.t.c.
     *
     * @param null $databaseKey
     * @param null $databaseConfig
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function setupDb()
    {
        // Create a Craft::$app object
        TestSetup::warmCraft();

        ob_start();

        $conn = \Craft::createObject(App::dbConfig(self::createDbConfig()));

        \Craft::$app->set('db', $conn);

        // TODO: Here is where we need to add plugins and migrations to run aswell. To do that we need to rewrite the TestSetup class.
        $testSetup = new TestSetup($conn);
        $testSetup->clenseDb();

        $testSetup->setupCraftDb();

        // Dont output anything or we get header's already sent exception
        ob_end_clean();
        TestSetup::tearDownCraft();
    }

    /**
     * Creates a DB config according to the loaded .ENV variables.
     * @return DbConfig
     */
    public static function createDbConfig()
    {
        return new DbConfig([
            'password' => getenv('TEST_DB_PASS'),
            'user' => getenv('TEST_DB_USER'),
            'database' => getenv('TEST_DB_NAME'),
            'tablePrefix' => getenv('TEST_DB_TABLE_PREFIX'),
            'driver' => getenv('TEST_DB_DRIVER'),
            'port' => getenv('TEST_DB_PORT'),
            'schema' => getenv('TEST_DB_SCHEMA'),
            'server' => getenv('TEST_DB_SERVER'),
        ]);
    }

    /**
     * @param TestInterface $test
     * @throws \yii\base\InvalidConfigException
     */
    public function _before(TestInterface $test)
    {
        parent::_before($test);

        /**
         * TODO:
         * There is a potential 'bug'/hampering feature with the Yii2 Codeception module.
         * DB connections initialized through the configFile param (see https://codeception.com/docs/modules/Yii2)
         * Are not captured by the Yii2Connector\ConnectionWatcher and Yii2Connector\TransactionForcer i.e. all DB interacitons done through
         * Craft::$app->getDb() are not stored and roll'd back in transacitons.
         *
         * This is probably because the starting of the app (triggered by $this->client->startApp()) is done BEFORE the
         * DB event listeners are registered. Moving the order of these listeners to the top of the _before function means the conneciton
         * is registered.
         *
         * What i need to investigate is whether iam doing something wrong in the src/tests/_craft/config/test.php or if this is PR 'worthy'
         * For now: Remounting the DB object using Craft::$app->set() after the event listeners are called works perfectly fine.
         */
        $db = \Craft::createObject(
            \craft\helpers\App::dbConfig(new \craft\config\DbConfig([
                'database' => getenv('TEST_DB_NAME'),
                'driver' => getenv('TEST_DB_DRIVER'),
                'user' => getenv('TEST_DB_USER'),
                'password' =>getenv('TEST_DB_PASS'),
                'tablePrefix' => getenv('TEST_DB_TABLE_PREFIX'),
                'port' => '3306',
            ])));

        \Craft::$app->set('db', $db);
    }

    /**
     * @param TestInterface $test
     * @throws \yii\db\Exception
     */
    public function _after(TestInterface $test)
    {
        // https://github.com/yiisoft/yii2/issues/11633 || The (possibly) MyISAM {{%searchindex}} table doesnt support transactions.
        // So we manually delete any rows in there except if the element id is 1 (The user added when creating the DB)
        parent::_after($test);

        \Craft::$app->getDb()->createCommand()
            ->delete('{{%searchindex}}', 'elementId != 1')
            ->execute();
    }

    // Helper and to-be-directly used in test methods.
    // =========================================================================

    /**
     * Ensure that an event is trigered by the $callback() function.
     *
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

        $this->assertTrue($requiredEvent, 'Asserting that an event is triggered');
    }
}