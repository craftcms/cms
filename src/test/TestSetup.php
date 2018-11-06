<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craft\test;

use craft\db\Connection;
use craft\db\MigrationManager;
use craft\helpers\MigrationHelper;
use craft\migrations\Install;
use craft\models\Site;
use yii\db\Exception;

/**
 * Class TestSetup.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class TestSetup
{
    public function __construct(Connection $connection, MigrationManager $migrationManager)
    {
        $this->connection = $connection;
        $this->migrationManager = $migrationManager;
    }

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var MigrationManager
     */
    private $migrationManager;

    private $hasBeenCleansed = false;

    /**
     * @return bool
     * @throws Exception
     */
    public function clenseDb()
    {
        $tables = $this->connection->schema->getTableNames();
        foreach ($tables as $table) {
            // TODO: Current dropTable uses the getDb() service locator. Figure out a way to make this dependant on the injected Connection class.
            MigrationHelper::dropTable($table);
        }

        $tables = $this->connection->schema->getTableNames();
        if ($tables !== []) {
            throw new Exception('Unable to setup test enviroment');
        }

        $this->hasBeenCleansed = true;
        return true;
    }

    /**
     * @return bool
     */
    public function setupDb()
    {
        if ($this->hasBeenCleansed !== true || $this->connection->schema->getTableNames() !== []) {
            throw new Exception('Not allowed to setup the DB if it hasnt been cleansed');
        }

        $site = new Site([
            'name' => 'Craft test site',
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => 'https://craftcms.com',
            'language' => 'en-US',
        ]);

        $migration = new Install([
            'username' => 'craftcms',
            'password' => 'craftcms2018!!',
            'email' => 'support@craftcms.com',
            'site' => $site,
        ]);

        return $this->migrationManager->migrateUp($migration);
    }

}