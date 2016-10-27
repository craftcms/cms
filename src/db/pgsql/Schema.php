<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db\pgsql;

use Craft;
use craft\app\helpers\Io;
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
     * Backs up a database using pg_dump, or with any command specified by the
     * `backupCommand` database config setting.
     *
     * @param string $filePath         The path of the backup file.
     * @param array  $ignoreDataTables An array of tables to skip backing up the data for.
     *
     * @return bool Whether the backup was successful or not.
     */
    public function backup($filePath, $ignoreDataTables)
    {
        $command = new ShellCommand();

        // If we don't have proc_open, maybe we've got exec
        if (!function_exists('proc_open') && function_exists('exec')) {
            $command->useExec = true;
        }

        $config = Craft::$app->getConfig();
        $port = $config->getDbPort();
        $server = $config->get('server', Config::CATEGORY_DB);
        $user = $config->get('user', Config::CATEGORY_DB);
        $database = $config->get('database', Config::CATEGORY_DB);
        $schema = $config->get('schema', Config::CATEGORY_DB);

        if (($backupCommand = $config->get('backupCommand', Config::CATEGORY_DB)) !== '') {
            $backupCommand = preg_replace('/\{filePath\}/', $filePath, $backupCommand);
            $command->setCommand($backupCommand);
        } else {
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

            foreach ($ignoreDataTables as $ignoreDataTable) {
                $command->addArg('--exclude-table-data=', $ignoreDataTable);
            }
        }

        if ($command->execute()) {
            return true;
        } else {
            $error = $command->getError();
            $exitCode = $command->getExitCode();

            Craft::error('Could not back up database. Error: '.$error.'. Exit Code:'.$exitCode, __METHOD__);
            return false;
        }
    }

    /**
     * Restores a database backup with the given backup file. Note that all tables and data in the database will be
     * deleted before the backup file is executed.
     *
     * @param string $filePath The file path of the database backup to restore.
     *
     * @return void
     * @throws Exception if $filePath doesn’t exist
     */
    public function restore($filePath)
    {
        if (!Io::fileExists($filePath)) {
            throw new Exception("Could not find the SQL file to restore: {$filePath}");
        }
    }
}
