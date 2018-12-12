<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Composer\Util\Platform;
use Craft;
use craft\config\DbConfig;
use craft\db\mysql\QueryBuilder as MysqlQueryBuilder;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\QueryBuilder as PgsqlQueryBuilder;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\errors\DbConnectException;
use craft\errors\ShellCommandException;
use craft\events\BackupEvent;
use craft\events\RestoreEvent;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use mikehaertl\shellcommand\Command as ShellCommand;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Exception as DbException;

/**
 * @inheritdoc
 * @property MysqlQueryBuilder|PgsqlQueryBuilder $queryBuilder The query builder for the current DB connection.
 * @property MysqlSchema|PgsqlSchema $schema The schema information for the database opened by this connection.
 * @property bool $supportsMb4 Whether the database supports 4+ byte characters.
 * @method MysqlQueryBuilder|PgsqlQueryBuilder getQueryBuilder() Returns the query builder for the current DB connection.
 * @method MysqlSchema|PgsqlSchema getSchema() Returns the schema information for the database opened by this connection.
 * @method TableSchema getTableSchema($name, $refresh = false) Obtains the schema information for the named table.
 * @method Command createCommand($sql = null, $params = []) Creates a command for execution.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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

    // Static
    // =========================================================================

    /**
     * Creates a new Connection instance based off the given DbConfig object.
     *
     * @param DbConfig $config
     * @return static
     * @deprecated in 3.0.18. Use [[App::dbConfig()]] instead.
     */
    public static function createFromConfig(DbConfig $config): Connection
    {
        $config = App::dbConfig($config);
        return Craft::createObject($config);
    }

    // Properties
    // =========================================================================

    /**
     * @var bool|null whether the database supports 4+ byte characters
     * @see getSupportsMb4()
     * @see setSupportsMb4()
     */
    private $_supportsMb4;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether this is a MySQL connection.
     *
     * @return bool
     */
    public function getIsMysql(): bool
    {
        return $this->getDriverName() === DbConfig::DRIVER_MYSQL;
    }

    /**
     * Returns whether this is a PostgreSQL connection.
     *
     * @return bool
     */
    public function getIsPgsql(): bool
    {
        return $this->getDriverName() === DbConfig::DRIVER_PGSQL;
    }

    /**
     * Returns the version of the DB.
     *
     * @return string
     */
    public function getVersion(): string
    {
        $version = $this->getMasterPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        return App::normalizeVersion($version);
    }

    /**
     * Returns whether the database supports 4+ byte characters.
     *
     * @return bool
     */
    public function getSupportsMb4(): bool
    {
        if ($this->_supportsMb4 !== null) {
            return $this->_supportsMb4;
        }
        return $this->_supportsMb4 = $this->getIsPgsql();
    }

    /**
     * Sets whether the database supports 4+ byte characters.
     *
     * @param bool $supportsMb4
     */
    public function setSupportsMb4(bool $supportsMb4)
    {
        $this->_supportsMb4 = $supportsMb4;
    }

    /**
     * @inheritdoc
     * @throws DbConnectException if there are any issues
     * @throws \Throwable
     */
    public function open()
    {
        try {
            parent::open();
        } catch (DbException $e) {
            Craft::error($e->getMessage(), __METHOD__);

            if ($this->getIsMysql()) {
                if (!extension_loaded('pdo')) {
                    throw new DbConnectException('Craft CMS requires the PDO extension to operate.', 0, $e);
                }
                if (!extension_loaded('pdo_mysql')) {
                    throw new DbConnectException('Craft CMS requires the PDO_MYSQL driver to operate.', 0, $e);
                }
            } else {
                if (!extension_loaded('pdo')) {
                    throw new DbConnectException('Craft CMS requires the PDO extension to operate.', 0, $e);
                }
                if (!extension_loaded('pdo_pgsql')) {
                    throw new DbConnectException('Craft CMS requires the PDO_PGSQL driver to operate.', 0, $e);
                }
            }

            Craft::error($e->getMessage(), __METHOD__);
            throw new DbConnectException('Craft CMS can’t connect to the database with the credentials in config/db.php.', 0, $e);
        } catch (\Throwable $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new DbConnectException('Craft CMS can’t connect to the database with the credentials in config/db.php.', 0, $e);
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
    public function backup(): string
    {
        // Determine the backup file path
        $currentVersion = 'v' . Craft::$app->getVersion();
        $systemName = FileHelper::sanitizeFilename($this->_getFixedSystemName(), ['asciiOnly' => true]);
        $filename = ($systemName ? $systemName . '_' : '') . gmdate('ymd_His') . '_' . strtolower(StringHelper::randomString(10)) . '_' . $currentVersion . '.sql';
        $file = Craft::$app->getPath()->getDbBackupPath() . '/' . mb_strtolower($filename);

        $this->backupTo($file);

        return $file;
    }

    /**
     * Performs a backup operation. If a `backupCommand` config setting has been set, will execute it. If not,
     * will execute the default database schema specific backup defined in `getDefaultBackupCommand()`, which uses
     * `pg_dump` for PostgreSQL and `mysqldump` for MySQL.
     *
     * @param string $filePath The file path the database backup should be saved at
     * @throws Exception if the backupCommand config setting is false
     * @throws ShellCommandException in case of failure
     */
    public function backupTo(string $filePath)
    {
        // Determine the command that should be executed
        $backupCommand = Craft::$app->getConfig()->getGeneral()->backupCommand;

        if ($backupCommand === null) {
            $schema = $this->getSchema();
            $backupCommand = $schema->getDefaultBackupCommand();
        }

        if ($backupCommand === false) {
            throw new Exception('Database not backed up because the backup command is false.');
        }

        // Create the shell command
        $command = $this->_createShellCommand($backupCommand);
        $command = $this->_parseCommandTokens($command, $filePath);

        // Fire a 'beforeCreateBackup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_CREATE_BACKUP)) {
            $this->trigger(self::EVENT_BEFORE_CREATE_BACKUP, new BackupEvent([
                'file' => $filePath
            ]));
        }

        $this->_executeDatabaseShellCommand($command);

        // Fire an 'afterCreateBackup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_BACKUP)) {
            $this->trigger(self::EVENT_AFTER_CREATE_BACKUP, new BackupEvent([
                'file' => $filePath
            ]));
        }
    }

    /**
     * Restores a database at the given file path.
     *
     * @param string $filePath The path of the database backup to restore.
     * @throws Exception if the restoreCommand config setting is false
     * @throws ShellCommandException in case of failure
     */
    public function restore(string $filePath)
    {
        // Determine the command that should be executed
        $restoreCommand = Craft::$app->getConfig()->getGeneral()->restoreCommand;

        if ($restoreCommand === null) {
            $schema = $this->getSchema();
            $restoreCommand = $schema->getDefaultRestoreCommand();
        }

        if ($restoreCommand === false) {
            throw new Exception('Database not restored because the restore command is false.');
        }

        // Create the shell command
        $command = $this->_createShellCommand($restoreCommand);
        $command = $this->_parseCommandTokens($command, $filePath);

        // Fire a 'beforeRestoreBackup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RESTORE_BACKUP)) {
            $this->trigger(self::EVENT_BEFORE_RESTORE_BACKUP, new RestoreEvent([
                'file' => $filePath
            ]));
        }

        $this->_executeDatabaseShellCommand($command);

        // Fire an 'afterRestoreBackup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RESTORE_BACKUP)) {
            $this->trigger(self::EVENT_AFTER_RESTORE_BACKUP, new BackupEvent([
                'file' => $filePath
            ]));
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public function quoteDatabaseName(string $name): string
    {
        return $this->getSchema()->quoteTableName($name);
    }

    /**
     * Returns whether a table exists.
     *
     * @param string $table
     * @param bool|null $refresh
     * @return bool
     */
    public function tableExists(string $table, bool $refresh = null): bool
    {
        // Default to refreshing the tables if Craft isn't installed yet
        if ($refresh || ($refresh === null && !Craft::$app->getIsInstalled())) {
            $this->getSchema()->refresh();
        }

        $table = $this->getSchema()->getRawTableName($table);

        return in_array($table, $this->getSchema()->getTableNames(), true);
    }

    /**
     * Checks if a column exists in a table.
     *
     * @param string $table
     * @param string $column
     * @param bool|null $refresh
     * @return bool
     * @throws NotSupportedException if there is no support for the current driver type
     */
    public function columnExists(string $table, string $column, bool $refresh = null): bool
    {
        // Default to refreshing the tables if Craft isn't installed yet
        if ($refresh || ($refresh === null && !Craft::$app->getIsInstalled())) {
            $this->getSchema()->refresh();
        }

        if (($tableSchema = $this->getTableSchema($table)) === null) {
            return false;
        }

        return ($tableSchema->getColumn($column) !== null);
    }

    /**
     * Returns a primary key name based on the table and column names.
     *
     * @param string $table
     * @param string|array $columns
     * @return string
     */
    public function getPrimaryKeyName(string $table, $columns): string
    {
        $table = $this->_getTableNameWithoutPrefix($table);
        if (is_string($columns)) {
            $columns = StringHelper::split($columns);
        }
        $name = $this->tablePrefix . $table . '_' . implode('_', $columns) . '_pk';

        return $this->trimObjectName($name);
    }

    /**
     * Returns a foreign key name based on the table and column names.
     *
     * @param string $table
     * @param string|array $columns
     * @return string
     */
    public function getForeignKeyName(string $table, $columns): string
    {
        $table = $this->_getTableNameWithoutPrefix($table);
        if (is_string($columns)) {
            $columns = StringHelper::split($columns);
        }
        $name = $this->tablePrefix . $table . '_' . implode('_', $columns) . '_fk';

        return $this->trimObjectName($name);
    }

    /**
     * Returns an index name based on the table, column names, and whether
     * it should be unique.
     *
     * @param string $table
     * @param string|array $columns
     * @param bool $unique
     * @param bool $foreignKey
     * @return string
     */
    public function getIndexName(string $table, $columns, bool $unique = false, bool $foreignKey = false): string
    {
        $table = $this->_getTableNameWithoutPrefix($table);
        if (is_string($columns)) {
            $columns = StringHelper::split($columns);
        }
        $name = $this->tablePrefix . $table . '_' . implode('_', $columns) . ($unique ? '_unq' : '') . ($foreignKey ? '_fk' : '_idx');

        return $this->trimObjectName($name);
    }

    /**
     * Ensures that an object name is within the schema's limit.
     *
     * @param string $name
     * @return string
     */
    public function trimObjectName(string $name): string
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
     * @return string
     */
    private function _getTableNameWithoutPrefix(string $table): string
    {
        $table = $this->getSchema()->getRawTableName($table);

        if ($this->tablePrefix) {
            if (strpos($table, $this->tablePrefix) === 0) {
                $table = substr($table, strlen($this->tablePrefix));
            }
        }

        return $table;
    }

    /**
     * Creates a shell command set to the given string
     *
     * @param string $command The command to be executed
     * @return ShellCommand
     */
    private function _createShellCommand(string $command): ShellCommand
    {
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
     * @param ShellCommand $shellCommand
     * @param string $file The path to the backup file
     * @return ShellCommand
     */
    private function _parseCommandTokens(ShellCommand $shellCommand, $file): ShellCommand
    {
        $command = $shellCommand->getCommand();

        // Swap out any tokens in the command
        $dbConfig = Craft::$app->getConfig()->getDb();
        $tokens = [
            '{file}' => $file,
            '{port}' => $dbConfig->port,
            '{server}' => $dbConfig->server,
            '{user}' => $dbConfig->user,
            '{password}' => addslashes($dbConfig->password),
            '{database}' => $dbConfig->database,
            '{schema}' => $dbConfig->schema,
        ];

        $command = str_replace(array_keys($tokens), array_values($tokens), $command);
        $shellCommand->setCommand($command);

        return $shellCommand;
    }

    /**
     * @param ShellCommand $command
     * @throws ShellCommandException
     */
    private function _executeDatabaseShellCommand(ShellCommand $command)
    {
        $success = $command->execute();

        // Nuke any temp connection files that might have been created.
        try {
            FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath(false));
        } catch (InvalidArgumentException $e) {
            // the directory doesn't exist
        }

        // PostgreSQL specific cleanup.
        if (Craft::$app->getDb()->getDriverName() === DbConfig::DRIVER_PGSQL) {
            if (Platform::isWindows()) {
                $envCommand = 'set PGPASSWORD=';
            } else {
                $envCommand = 'unset PGPASSWORD';
            }

            $cleanCommand = $this->_createShellCommand($envCommand);
            $cleanCommand->execute();
        }

        if (!$success) {
            throw ShellCommandException::createFromCommand($command);
        }
    }

    /**
     * TODO: remove this method after the next breakpoint and just use getInfo()->name directly.
     *
     * @return string
     */
    private function _getFixedSystemName(): string
    {
        try {
            return (new Query())
                ->select(['siteName'])
                ->from(['{{%info}}'])
                ->column()[0];
        } catch (\Throwable $e) {
            return Craft::$app->getInfo()->name ?: Craft::$app->getSites()->getPrimarySite()->name;
        }
    }
}
