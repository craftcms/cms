<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db\mysql;

use Craft;
use craft\app\errors\DbBackupException;
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
class Schema extends \yii\db\mysql\Schema
{
    // Constants
    // =========================================================================

    const TYPE_TINYTEXT = 'tinytext';
    const TYPE_MEDIUMTEXT = 'mediumtext';
    const TYPE_LONGTEXT = 'longtext';
    const TYPE_ENUM = 'enum';

    // Properties
    // =========================================================================

    /**
     * @var int The maximum length that objects' names can be.
     */
    public $maxObjectNameLength = 64;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->typeMap['tinytext'] = self::TYPE_TINYTEXT;
        $this->typeMap['mediumtext'] = self::TYPE_MEDIUMTEXT;
        $this->typeMap['longtext'] = self::TYPE_LONGTEXT;
        $this->typeMap['enum'] = self::TYPE_ENUM;
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
        return '`'.$name.'`';
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
            // Specifically look for a "SAVEPOINT does not exist" error.
            if ($e->getCode() == 42000 && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1305) {
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
            // Specifically look for a "SAVEPOINT does not exist" error.
            if ($e->getCode() == 42000 && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1305) {
                Craft::warning('Tried to roll back a savepoint, but it does not exist: '.$e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->db);
    }

    /**
     * Gets the default backup command to execute.
     *
     * @param ShellCommand $command  The command to execute.
     * @param string       $filePath The path of the backup file.
     *
     * @return ShellCommand The command to execute.
     * @throws DbBackupException
     */
    public function getDefaultBackupCommand(ShellCommand $command, $filePath)
    {
        $config = Craft::$app->getConfig();
        $port = $config->getDbPort();
        $server = $config->get('server', Config::CATEGORY_DB);
        $user = $config->get('user', Config::CATEGORY_DB);
        $database = $config->get('database', Config::CATEGORY_DB);
        $password = $config->get('password', Config::CATEGORY_DB);

        $tempConnectFile = Craft::$app->getPath()->getTempPath().'/my.cnf';
        $contents = "[client]".PHP_EOL."user={$user}".PHP_EOL."password={$password}".PHP_EOL."host={$server}".PHP_EOL."port={$port}";

        if (!Io::writeToFile($tempConnectFile, $contents)) {
            throw new DbBackupException('Could not write the my.cnf file for mysqldump to use to connect to the database.');
        }

        $command->setCommand('/Applications/MAMP/Library/bin/mysqldump');

        $command->addArg('--defaults-extra-file=', $tempConnectFile);
        $command->addArg('--add-drop-table');
        $command->addArg('--comments');
        $command->addArg('--create-options');
        $command->addArg('--dump-date');
        $command->addArg('--no-autocommit');
        $command->addArg('--routines');
        $command->addArg('--set-charset');
        $command->addArg('--triggers');
        $command->addArg('--result-file=', $filePath);
        $command->addArg($database);

        return $command;
    }

    /**
     * Generates the default database restore command to execute.
     *
     * @param ShellCommand $command  The command to execute.
     * @param string       $filePath The file path of the database backup to restore.
     *
     * @return ShellCommand The command to execute.
     * @throws DbBackupException
     */
    public function getDefaultRestoreCommand(ShellCommand $command, $filePath)
    {
        $config = Craft::$app->getConfig();
        $port = $config->getDbPort();
        $server = $config->get('server', Config::CATEGORY_DB);
        $user = $config->get('user', Config::CATEGORY_DB);
        $database = $config->get('database', Config::CATEGORY_DB);
        $password = $config->get('password', Config::CATEGORY_DB);

        $tempConnectFile = Craft::$app->getPath()->getTempPath().'/my.cnf';
        $contents = "[client]".PHP_EOL."user={$user}".PHP_EOL."password={$password}".PHP_EOL."host={$server}".PHP_EOL."port={$port}";

        if (!Io::writeToFile($tempConnectFile, $contents)) {
            throw new DbBackupException('Could not write the my.cnf file for mysqldump to use to connect to the database.');
        }

        $command->setCommand('/Applications/MAMP/Library/bin/mysqldump');

        $command->addArg('--defaults-extra-file=', $tempConnectFile);
        $command->addArg($database);
        $command->addArg('< ', $filePath);

        return $command;
    }
}
