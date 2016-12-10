<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db;

use Craft;
use craft\db\mysql\QueryBuilder;
use craft\errors\DbConnectException;
use craft\errors\ShellCommandException;
use craft\events\BackupEvent;
use craft\events\RestoreEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\services\Config;
use mikehaertl\shellcommand\Command as ShellCommand;
use yii\base\Exception;
use yii\db\Exception as DbException;

/**
 * @inheritdoc
 *
 * @property QueryBuilder $queryBuilder The query builder for the current DB connection.
 * @method QueryBuilder getQueryBuilder() Returns the query builder for the current DB connection.
 * @method Command createCommand($sql = null, $params = []) Creates a command for execution.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Connection extends \yii\db\Connection
{
    // Constants
    // =========================================================================

    /**
     * @event BackupEvent The event that is triggered before the backup is created.
     */
    const EVENT_BEFORE_CREATE_BACKUP = 'beforeCreateBackup';

    /**
     * @event BackupEvent The event that is triggered after the backup is created.
     */
    const EVENT_AFTER_CREATE_BACKUP = 'afterCreateBackup';

    /**
     * @event RestoreEvent The event that is triggered before the restore is started.
     */
    const EVENT_BEFORE_RESTORE_BACKUP = 'beforeRestoreBackup';

    /**
     * @event RestoreEvent The event that is triggered after the restore occurred.
     */
    const EVENT_AFTER_RESTORE_BACKUP = 'afterRestoreBackup';

    const DRIVER_MYSQL = 'mysql';
    const DRIVER_PGSQL = 'pgsql';

    // Properties
    // =========================================================================

    /**
     * @var string the class used to create new database [[Command]] objects. If you want to extend the [[Command]] class,
     * you may configure this property to use your extended version of the class.
     * @see   createCommand
     * @since 2.0.7
     */
    public $commandClass = Command::class;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @throws DbConnectException if there are any issues
     */
    public function open()
    {
        try {
            parent::open();
        } catch (DbException $e) {
            Craft::error($e->getMessage(), __METHOD__);

            // TODO: Multi-db driver check.
            if (!extension_loaded('pdo')) {
                throw new DbConnectException(Craft::t('app', 'Craft CMS requires the PDO extension to operate.'));
            } else if (!extension_loaded('pdo_mysql')) {
                throw new DbConnectException(Craft::t('app', 'Craft CMS requires the PDO_MYSQL driver to operate.'));
            } else {
                Craft::error($e->getMessage(), __METHOD__);
                throw new DbConnectException(Craft::t('app', 'Craft CMS can’t connect to the database with the credentials in config/db.php.'));
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new DbConnectException(Craft::t('app', 'Craft CMS can’t connect to the database with the credentials in config/db.php.'));
        }
    }

    /**
     * Performs a backup operation. If a `backupCommand` config setting has been set, will execute it. If not,
     * will execute the default database schema specific backup defined in `getDefaultBackupCommand()`, which uses
     * `pg_dump` for PostgreSQL and `mysqldump` for MySQL.
     *
     * @return string The file path to the database backup
     * @throws Exception if the backupCommand config setting is false
     * @throws ShellCommandException in case of failure
     */
    public function backup()
    {
        // Determine the backup file path
        $currentVersion = 'v'.Craft::$app->version;
        $siteName = FileHelper::sanitizeFilename($this->_getFixedSiteName(), ['asciiOnly' => true]);
        $filename = ($siteName ? $siteName.'_' : '').gmdate('ymd_His').'_'.strtolower(StringHelper::randomString(10)).'_'.$currentVersion.'.sql';
        $file = Craft::$app->getPath()->getDbBackupPath().'/'.StringHelper::toLowerCase($filename);

        $this->backupTo($file);

        return $file;
    }

    /**
     * Performs a backup operation. If a `backupCommand` config setting has been set, will execute it. If not,
     * will execute the default database schema specific backup defined in `getDefaultBackupCommand()`, which uses
     * `pg_dump` for PostgreSQL and `mysqldump` for MySQL.
     *
     * @param string $file The file path the database backup should be saved at
     *
     * @return void
     * @throws Exception if the backupCommand config setting is false
     * @throws ShellCommandException in case of failure
     */
    public function backupTo($file)
    {
        // Determine the command that should be executed
        $backupCommand = Craft::$app->getConfig()->get('backupCommand');

        if ($backupCommand === null) {
            /** @var mysql\Schema|pgsql\Schema $schema */
            $schema = $command = $this->getSchema();
            $backupCommand = $schema->getDefaultBackupCommand();
        }

        if ($backupCommand === false) {
            throw new Exception('Database not backed up because the backup command is false.');
        }

        // Create the shell command
        $command = $this->_createShellCommand($backupCommand, $file);

        // Fire a 'beforeCreateBackup' event
        $this->trigger(self::EVENT_BEFORE_CREATE_BACKUP, new BackupEvent([
            'file' => $file
        ]));

        $success = $command->execute();

        // Nuke any temp connection files that might have been created.
        FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath());

        if (!$success) {
            throw ShellCommandException::createFromCommand($command);
        }

        // Fire an 'afterCreateBackup' event
        $this->trigger(self::EVENT_AFTER_CREATE_BACKUP, new BackupEvent([
            'file' => $file
        ]));
    }

    /**
     * Restores a database at the given file path.
     *
     * @param string $filePath The path of the database backup to restore.
     *
     * @return void
     * @throws Exception if the restoreCommand config setting is false
     * @throws ShellCommandException in case of failure
     */
    public function restore($filePath)
    {
        // Determine the command that should be executed
        $restoreCommand = Craft::$app->getConfig()->get('restoreCommand');

        if ($restoreCommand === null) {
            /** @var mysql\Schema|pgsql\Schema $schema */
            $schema = $command = $this->getSchema();
            $restoreCommand = $schema->getDefaultRestoreCommand();
        }

        if ($restoreCommand === false) {
            throw new Exception('Database not restored because the restore command is false.');
        }

        // Create the shell command
        $command = $this->_createShellCommand($restoreCommand, $filePath);

        // Fire a 'beforeRestoreBackup' event
        $this->trigger(self::EVENT_BEFORE_RESTORE_BACKUP, new RestoreEvent([
            'file' => $filePath
        ]));

        $success = $command->execute();

        // Nuke any temp connection files that might have been created.
        FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath());

        if (!$success) {
            throw ShellCommandException::createFromCommand($command);
        }

        // Fire an 'afterRestoreBackup' event
        $this->trigger(self::EVENT_AFTER_RESTORE_BACKUP, new BackupEvent([
            'file' => $filePath
        ]));
    }

    /**
     * @param $name
     *
     * @return string
     */
    public function quoteDatabaseName($name)
    {
        return $this->getSchema()->quoteTableName($name);
    }

    /**
     * Returns whether a table exists.
     *
     * @param string       $table
     * @param boolean|null $refresh
     *
     * @return boolean
     */
    public function tableExists($table, $refresh = null)
    {
        // Default to refreshing the tables if Craft isn't installed yet
        if ($refresh || ($refresh === null && !Craft::$app->getIsInstalled())) {
            $this->getSchema()->refresh();
        }

        $table = $this->getSchema()->getRawTableName($table);

        return in_array($table, $this->getSchema()->getTableNames());
    }

    /**
     * Checks if a column exists in a table.
     *
     * @param string       $table
     * @param string       $column
     * @param boolean|null $refresh
     *
     * @return boolean
     */
    public function columnExists($table, $column, $refresh = null)
    {
        // Default to refreshing the tables if Craft isn't installed yet
        if ($refresh || ($refresh === null && !Craft::$app->getIsInstalled())) {
            $this->getSchema()->refresh();
        }

        $table = $this->getTableSchema('{{'.$table.'}}');

        if ($table) {
            if (($column = $table->getColumn($column)) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a foreign key name based on the table and column names.
     *
     * @param string       $table
     * @param string|array $columns
     *
     * @return string
     */
    public function getForeignKeyName($table, $columns)
    {
        $table = $this->_getTableNameWithoutPrefix($table);
        $columns = ArrayHelper::toArray($columns);
        $name = $this->tablePrefix.$table.'_'.implode('_', $columns).'_fk';

        return $this->trimObjectName($name);
    }

    /**
     * Returns an index name based on the table, column names, and whether
     * it should be unique.
     *
     * @param string       $table
     * @param string|array $columns
     * @param boolean      $unique
     *
     * @return string
     */
    public function getIndexName($table, $columns, $unique = false)
    {
        $table = $this->_getTableNameWithoutPrefix($table);
        $columns = ArrayHelper::toArray($columns);
        $name = $this->tablePrefix.$table.'_'.implode('_', $columns).($unique ? '_unq' : '').'_idx';

        return $this->trimObjectName($name);
    }

    /**
     * Returns a primary key name based on the table and column names.
     *
     * @param string       $table
     * @param string|array $columns
     *
     * @return string
     */
    public function getPrimaryKeyName($table, $columns)
    {
        $table = $this->_getTableNameWithoutPrefix($table);
        $columns = ArrayHelper::toArray($columns);
        $name = $this->tablePrefix.$table.'_'.implode('_', $columns).'_pk';

        return $this->trimObjectName($name);
    }

    /**
     * Ensures that an object name is within the schema's limit.
     *
     * @param string $name
     *
     * @return string
     */
    public function trimObjectName($name)
    {
        $schema = $this->getSchema();

        if (!isset($schema->maxObjectNameLength)) {
            return $name;
        }

        $name = trim($name, '_');
        $nameLength = StringHelper::length($name);

        if ($nameLength > $schema->maxObjectNameLength) {
            $parts = array_filter(explode('_', $name));
            $totalParts = count($parts);
            $totalLetters = $nameLength - ($totalParts - 1);
            $maxLetters = $schema->maxObjectNameLength - ($totalParts - 1);

            // Consecutive underscores could have put this name over the top
            if ($totalLetters > $maxLetters) {
                foreach ($parts as $i => $part) {
                    $newLength = round($maxLetters * StringHelper::length($part) / $totalLetters);
                    $parts[$i] = mb_substr($part, 0, $newLength);
                }
            }

            $name = implode('_', $parts);

            // Just to be safe
            if (StringHelper::length($name) > $schema->maxObjectNameLength) {
                $name = mb_substr($name, 0, $schema->maxObjectNameLength);
            }
        }

        return $name;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a table name without the table prefix
     *
     * @param string $table
     *
     * @return string
     */
    private function _getTableNameWithoutPrefix($table)
    {
        $table = $this->getSchema()->getRawTableName($table);

        if ($this->tablePrefix) {
            $prefixLength = strlen($this->tablePrefix);

            if (strncmp($table, $this->tablePrefix, $prefixLength) === 0) {
                $table = substr($table, $prefixLength);
            }
        }

        return $table;
    }

    /**
     * Creates a shell command set to the given string. The string can contain tokens.
     *
     * @param string $command The tokenized command to be executed
     * @param string $file The path to the backup file
     *
     * @return ShellCommand
     */
    private function _createShellCommand($command, $file)
    {
        // Swap out any tokens in the command
        $config = Craft::$app->getConfig();
        $tokens = [
            '{file}' => $file,
            '{port}' => $config->getDbPort(),
            '{server}' => $config->get('server', Config::CATEGORY_DB),
            '{user}' => $config->get('user', Config::CATEGORY_DB),
            '{database}' => $config->get('database', Config::CATEGORY_DB),
            '{schema}' => $config->get('schema', Config::CATEGORY_DB),
        ];
        $command = str_replace(array_keys($tokens), array_values($tokens), $command);

        // Create the shell command
        $shellCommand = new ShellCommand();
        $shellCommand->setCommand($command);

        // If we don't have proc_open, maybe we've got exec
        if (!function_exists('proc_open') && function_exists('exec')) {
            $shellCommand->useExec = true;
        }

        return $shellCommand;
    }

    /**
     * TODO: remove this method after the next breakpoint and just use getPrimarySite() directly.
     *
     * @return string
     */
    private function _getFixedSiteName() {
        try {
            return (new Query())
                ->select(['siteName'])
                ->from(['{{%info}}'])
                ->column()[0];
        } catch (\Exception $e) {
            return Craft::$app->getSites()->getPrimarySite()->name;
        }
    }
}
