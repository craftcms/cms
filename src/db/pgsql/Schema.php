<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db\pgsql;

use Craft;
use craft\app\services\Config;
use mikehaertl\shellcommand\Command as ShellCommand;
use yii\db\Exception;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Schema extends \yii\db\pgsql\Schema
{
    // Properties
    // =========================================================================

    /**
     * @var int The maximum length that objects' names can be.
     */
    public $maxObjectNameLength = 63;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->defaultSchema = Craft::$app->getConfig()->get('schema', Config::CATEGORY_DB);
    }

    /**
     * Creates a query builder for the database.
     * This method may be overridden by child classes to create a DBMS-specific query builder.
     *
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->db);
    }

    /**
     * Quotes a database name for use in a query.
     *
     * @param $name
     *
     * @return string
     */
    public function quoteDatabaseName($name)
    {
        return '"'.$name.'"';
    }

    /**
     * Releases an existing savepoint.
     *
     * @param string $name The savepoint name.
     *
     * @throws Exception
     */

    public function releaseSavepoint($name)
    {
        try {
            parent::releaseSavepoint($name);
        } catch (Exception $e) {
            // Specifically look for a "No such savepoint" error.
            if ($e->getCode() == 3 && isset($e->errorInfo[0]) && isset($e->errorInfo[1]) && $e->errorInfo[0] == '3B001' && $e->errorInfo[1] == 7) {
                Craft::warning('Tried to release a savepoint, but it does not exist: '.$e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name The savepoint name.
     *
     * @throws Exception
     */
    public function rollBackSavepoint($name)
    {
        try {
            parent::rollBackSavepoint($name);
        } catch (Exception $e) {
            // Specifically look for a "No such savepoint" error.
            if ($e->getCode() == 3 && isset($e->errorInfo[0]) && isset($e->errorInfo[1]) && $e->errorInfo[0] == '3B001' && $e->errorInfo[1] == 7) {
                Craft::warning('Tried to roll back a savepoint, but it does not exist: '.$e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getLastInsertID($sequenceName = '')
    {
        if ($sequenceName !== '') {
            $sequenceName = $this->defaultSchema.'.'.$this->getRawTableName($sequenceName).'_id_seq';
        }

        return parent::getLastInsertID($sequenceName);
    }

    /**
     * Gets the default backup command to execute.
     *
     * @param ShellCommand $command          The command to execute.
     * @param string       $filePath         The path of the backup file.
     *
     * @return ShellCommand The command to execute.
     */
    public function getDefaultBackupCommand(ShellCommand $command, $filePath)
    {
        $config = Craft::$app->getConfig();
        $port = $config->getDbPort();
        $server = $config->get('server', Config::CATEGORY_DB);
        $user = $config->get('user', Config::CATEGORY_DB);
        $database = $config->get('database', Config::CATEGORY_DB);
        $schema = $config->get('schema', Config::CATEGORY_DB);

        $command->setCommand('pg_dump');

        $command->addArg('--dbname=', $database);
        $command->addArg('--host=', $server);
        $command->addArg('--port=', $port);
        $command->addArg('--username=', $user);
        $command->addArg('--no-password');
        $command->addArg('--if-exists');
        $command->addArg('--clean');
        $command->addArg('--file=', $filePath);
        $command->addArg('--schema=', $schema);

        return $command;
    }

    /**
     * Generates the default database restore command to execute.
     *
     * @param ShellCommand $command  The command to execute.
     * @param string       $filePath The file path of the database backup to restore.
     *
     * @return ShellCommand The command to execute.
     */
    public function getDefaultRestoreCommand(ShellCommand $command, $filePath)
    {
        $config = Craft::$app->getConfig();
        $port = $config->getDbPort();
        $server = $config->get('server', Config::CATEGORY_DB);
        $user = $config->get('user', Config::CATEGORY_DB);
        $database = $config->get('database', Config::CATEGORY_DB);

        $command->setCommand('psql');

        $command->addArg('--dbname=', $database);
        $command->addArg('--host=', $server);
        $command->addArg('--port=', $port);
        $command->addArg('--username=', $user);
        $command->addArg('--no-password');
        $command->addArg('< ', $filePath);

        return $command;
    }
}
