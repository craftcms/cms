<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craft\test;

use craft\db\Connection;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\helpers\MigrationHelper;
use craft\migrations\Install;
use craft\models\Site;
use craft\web\UploadedFile;
use yii\db\Exception;

/**
 * Class TestSetup.
 *
 *  TODO:This class will be rewritten to support plugins. The way it currently set's up the install migration
 *  will be altered.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class TestSetup
{
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var bool
     */
    private $hasBeenCleansed = false;

    /**
     * Creates a craft object to play with. Ensures the Craft::$app service locator is working.
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function warmCraft()
    {
        $app = require dirname(__DIR__, 2).'/tests/_craft/config/test.php';
        $app['isInstalled'] = false;

        return \Craft::createObject($app);
    }

    /**
     * Taken from the Yii2 Module $i->_after
     */
    public static function tearDownCraft()
    {
        $_SESSION = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];

        if (isset(\Craft::$app) && \Craft::$app->has('session', true)) {
            \Craft::$app->session->close();
        }

        UploadedFile::reset();
        if (method_exists(\yii\base\Event::class, 'offAll')) {
            \yii\base\Event::offAll();
        }
        \Craft::setLogger(null);

        \Craft::$app = null;

    }

    /**
     * @return bool
     * @throws Exception
     */
    public function clenseDb()
    {
        $tables = $this->connection->schema->getTableNames();

        if ($this->connection->getIsMysql()) {
            $this->connection->createCommand("SET foreign_key_checks = 0")->execute();
            foreach ($tables as $table) {
                $this->connection->createCommand()->dropTable($table)->execute();
            }
            $this->connection->createCommand("SET foreign_key_checks = 1")->execute();
        } else {
            // TODO: Drop all in pgsql
        }

        $tables = $this->connection->schema->getTableNames();
        if ($tables !== []) {
            throw new Exception('Unable to setup test enviroment');
        }

        $this->hasBeenCleansed = true;
        return true;
    }

    /**
     * @param Migration $migration
     * @return false|null
     * @throws \Throwable
     */
    public function setupMigration(Migration $migration)
    {
        // Ensure our connection is used
        $migration->db = $this->connection;

        return $migration->up();
    }

    /**
     * @return void
     */
    public function setupCraftDb()
    {
        if ($this->connection->schema->getTableNames() !== []) {
            throw new Exception('Not allowed to setup the DB if it hasnt been cleansed');
        }

        $site = new Site([
            'name' => 'Craft test site',
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => 'https://craftcms.com',
            'language' => 'en-US',
            'primary' => true,
        ]);

        $migration = new Install([
            'db' => $this->connection,
            'username' => 'craftcms',
            'password' => 'craftcms2018!!',
            'email' => 'support@craftcms.com',
            'site' => $site,
        ]);

        return $migration->safeUp();
    }

}