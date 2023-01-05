<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Composer\Util\Platform;
use Craft;
use craft\db\mysql\QueryBuilder as MysqlQueryBuilder;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\QueryBuilder as PgsqlQueryBuilder;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\errors\DbConnectException;
use craft\errors\ShellCommandException;
use craft\events\BackupEvent;
use craft\events\RestoreEvent;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use mikehaertl\shellcommand\Command as ShellCommand;
use Throwable;
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
 * @method TableSchema|null getTableSchema($name, $refresh = false) Obtains the schema information for the named table.
 * @method Command createCommand($sql = null, $params = []) Creates a command for execution.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Connection extends \yii\db\Connection
{
    use PrimaryReplicaTrait;

    public const DRIVER_MYSQL = 'mysql';
    public const DRIVER_PGSQL = 'pgsql';

    /**
     * @event BackupEvent The event that is triggered before the backup is created.
     */
    public const EVENT_BEFORE_CREATE_BACKUP = 'beforeCreateBackup';

    /**
     * @event BackupEvent The event that is triggered after the backup is created.
     */
    public const EVENT_AFTER_CREATE_BACKUP = 'afterCreateBackup';

    /**
     * @event RestoreEvent The event that is triggered before the restore is started.
     */
    public const EVENT_BEFORE_RESTORE_BACKUP = 'beforeRestoreBackup';

    /**
     * @event RestoreEvent The event that is triggered after the restore occurred.
     */
    public const EVENT_AFTER_RESTORE_BACKUP = 'afterRestoreBackup';

    /**
     * @var bool|null whether the database supports 4+ byte characters
     * @see getSupportsMb4()
     * @see setSupportsMb4()
     */
    private ?bool $_supportsMb4 = null;

    /**
     * Returns whether this is a MySQL connection.
     *
     * @return bool
     */
    public function getIsMysql(): bool
    {
        return $this->getDriverName() === Connection::DRIVER_MYSQL;
    }

    /**
     * Returns whether this is a PostgreSQL connection.
     *
     * @return bool
     */
    public function getIsPgsql(): bool
    {
        return $this->getDriverName() === Connection::DRIVER_PGSQL;
    }

    /**
     * Returns whether the database supports 4+ byte characters.
     *
     * @return bool
     */
    public function getSupportsMb4(): bool
    {
        if (isset($this->_supportsMb4)) {
            return $this->_supportsMb4;
        }
        return $this->_supportsMb4 = $this->getIsPgsql();
    }

    /**
     * Sets whether the database supports 4+ byte characters.
     *
     * @param bool $supportsMb4
     */
    public function setSupportsMb4(bool $supportsMb4): void
    {
        $this->_supportsMb4 = $supportsMb4;
    }

    /**
     * @inheritdoc
     * @throws DbConnectException if there are any issues
     * @throws Throwable
     */
    public function open(): void
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
            throw new DbConnectException('Craft CMS can’t connect to the database.', 0, $e);
        } catch (Throwable $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new DbConnectException('Craft CMS can’t connect to the database.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     * @since 3.4.11
     */
    public function close(): void
    {
        parent::close();
        $this->_supportsMb4 = null;
    }

    /**
     * Returns the path for a new backup file.
     *
     * @return string
     * @since 3.0.38
     */
    public function getBackupFilePath(): string
    {
        // Determine the backup file path
        $systemName = mb_strtolower(FileHelper::sanitizeFilename(Craft::$app->getSystemName(), [
            'asciiOnly' => true,
        ]));
        $version = Craft::$app->getInfo()->version ?? Craft::$app->getVersion();
        $filename = ($systemName ? "$systemName--" : '') . gmdate('Y-m-d-His') . "--v$version";
        $backupPath = Craft::$app->getPath()->getDbBackupPath();
        $path = $backupPath . DIRECTORY_SEPARATOR . $filename . '.sql';
        $i = 0;
        while (file_exists($path)) {
            $path = $backupPath . DIRECTORY_SEPARATOR . $filename . '--' . ++$i . '.sql';
        }
        return $path;
    }

    /**
     * Returns the core table names whose data should be excluded from database backups.
     *
     * @return string[]
     */
    public function getIgnoredBackupTables(): array
    {
        return [
            Table::ASSETINDEXDATA,
            Table::IMAGETRANSFORMINDEX,
            Table::RESOURCEPATHS,
            Table::SESSIONS,
            '{{%cache}}',
        ];
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
        $file = $this->getBackupFilePath();
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
    public function backupTo(string $filePath): void
    {
        // Fire a 'beforeCreateBackup' event
        $event = new BackupEvent([
            'file' => $filePath,
            'ignoreTables' => $this->getIgnoredBackupTables(),
        ]);
        $this->trigger(self::EVENT_BEFORE_CREATE_BACKUP, $event);

        // Determine the command that should be executed
        $backupCommand = Craft::$app->getConfig()->getGeneral()->backupCommand;

        if ($backupCommand === null) {
            $backupCommand = $this->getSchema()->getDefaultBackupCommand($event->ignoreTables);
        }

        if ($backupCommand === false) {
            throw new Exception('Database not backed up because the backup command is false.');
        }

        // Create the shell command
        $backupCommand = $this->_parseCommandTokens($backupCommand, $filePath);
        $command = $this->_createShellCommand($backupCommand);

        $this->_executeDatabaseShellCommand($command);

        // Fire an 'afterCreateBackup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_BACKUP)) {
            $this->trigger(self::EVENT_AFTER_CREATE_BACKUP, new BackupEvent([
                'file' => $filePath,
            ]));
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->maxBackups) {
            $backupPath = Craft::$app->getPath()->getDbBackupPath();

            // Grab all .sql files in the backup folder.
            $files = array_merge(
                glob($backupPath . DIRECTORY_SEPARATOR . '*.sql'),
                glob($backupPath . DIRECTORY_SEPARATOR . '*.sql.zip'),
            );

            // Sort them by file modified time descending (newest first).
            usort($files, static function($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });

            if (count($files) >= $generalConfig->maxBackups) {
                $backupsToDelete = array_slice($files, $generalConfig->maxBackups);

                foreach ($backupsToDelete as $backupToDelete) {
                    FileHelper::unlink($backupToDelete);
                }
            }
        }
    }

    /**
     * Restores a database at the given file path.
     *
     * @param string $filePath The path of the database backup to restore.
     * @throws Exception if the restoreCommand config setting is false
     * @throws ShellCommandException in case of failure
     */
    public function restore(string $filePath): void
    {
        // Fire a 'beforeRestoreBackup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RESTORE_BACKUP)) {
            $this->trigger(self::EVENT_BEFORE_RESTORE_BACKUP, new RestoreEvent([
                'file' => $filePath,
            ]));
        }

        // Determine the command that should be executed
        $restoreCommand = Craft::$app->getConfig()->getGeneral()->restoreCommand;

        if ($restoreCommand === null) {
            $restoreCommand = $this->getSchema()->getDefaultRestoreCommand();
        }

        if ($restoreCommand === false) {
            throw new Exception('Database not restored because the restore command is false.');
        }

        // Create the shell command
        $restoreCommand = $this->_parseCommandTokens($restoreCommand, $filePath);
        $command = $this->_createShellCommand($restoreCommand);

        $this->_executeDatabaseShellCommand($command);

        // Fire an 'afterRestoreBackup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RESTORE_BACKUP)) {
            $this->trigger(self::EVENT_AFTER_RESTORE_BACKUP, new BackupEvent([
                'file' => $filePath,
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
    public function tableExists(string $table, ?bool $refresh = null): bool
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
    public function columnExists(string $table, string $column, ?bool $refresh = null): bool
    {
        // Default to refreshing the tables if Craft isn't installed yet
        if ($refresh || ($refresh === null && !Craft::$app->getIsInstalled())) {
            $this->getSchema()->refresh();
        }

        return isset($this->getTableSchema($table)->columns[$column]);
    }

    /**
     * Generates a primary key name.
     *
     * @return string
     */
    public function getPrimaryKeyName(): string
    {
        return $this->_objectName('pk');
    }

    /**
     * Generates a foreign key name.
     *
     * @return string
     */
    public function getForeignKeyName(): string
    {
        return $this->_objectName('fk');
    }

    /**
     * Generates an index name.
     *
     * @return string
     */
    public function getIndexName(): string
    {
        return $this->_objectName('idx');
    }

    /**
     * Generates a FK, index, or PK name.
     *
     * @param string $prefix
     * @return string
     */
    private function _objectName(string $prefix): string
    {
        return $this->tablePrefix . $prefix . '_' . StringHelper::randomString();
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
     * Parses a database backup/restore command for config tokens
     *
     * @param string $command The command to parse tokens in
     * @param string $file The path to the backup file
     * @return string
     */
    private function _parseCommandTokens(string $command, string $file): string
    {
        $parsed = Db::parseDsn($this->dsn);
        $username = $this->getIsPgsql() && !empty($parsed['user']) ? $parsed['user'] : $this->username;
        $password = $this->getIsPgsql() && !empty($parsed['password']) ? $parsed['password'] : $this->password;
        $tokens = [
            '{file}' => $file,
            '{port}' => $parsed['port'] ?? '',
            '{server}' => $parsed['host'] ?? '',
            '{user}' => $username,
            '{password}' => str_replace('$', '\\$', addslashes($password)),
            '{database}' => $parsed['dbname'] ?? '',
            '{schema}' => $this->getSchema()->defaultSchema ?? '',
        ];

        return str_replace(array_keys($tokens), $tokens, $command);
    }

    /**
     * @param ShellCommand $command
     * @throws ShellCommandException
     */
    private function _executeDatabaseShellCommand(ShellCommand $command): void
    {
        $success = $command->execute();

        // Nuke any temp connection files that might have been created.
        try {
            if ($this->getIsMysql()) {
                $schema = $this->getSchema();
                @unlink($schema->tempMyCnfPath);
            }
        } catch (InvalidArgumentException) {
            // the directory doesn't exist
        }

        // PostgreSQL specific cleanup.
        if ($this->getIsPgsql()) {
            if (Platform::isWindows()) {
                $envCommand = 'set PGPASSWORD=';
            } else {
                $envCommand = 'unset PGPASSWORD';
            }

            $cleanCommand = $this->_createShellCommand($envCommand);
            $cleanCommand->execute();
        }

        if (!$success) {
            $execCommand = $command->getExecCommand();

            // Redact the PGPASSWORD
            if ($this->getIsPgsql()) {
                $execCommand = preg_replace_callback('/(PGPASSWORD=")([^"]+)"/i', function($match) {
                    return $match[1] . str_repeat('•', strlen($match[2])) . '"';
                }, $execCommand);
            }

            throw new ShellCommandException($execCommand, $command->getExitCode(), $command->getStdErr());
        }
    }
}
