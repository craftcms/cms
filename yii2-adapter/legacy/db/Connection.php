<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Craft;
use craft\db\mysql\QueryBuilder as MysqlQueryBuilder;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\QueryBuilder as PgsqlQueryBuilder;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\errors\DbConnectException;
use craft\errors\ShellCommandException;
use craft\events\BackupEvent;
use craft\events\RestoreEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Database\Events\BackupCreated;
use CraftCms\Cms\Database\Events\BackupCreating;
use CraftCms\Cms\Database\Events\BackupRestored;
use CraftCms\Cms\Database\Events\BackupRestoring;
use CraftCms\Cms\Database\Exceptions\CommandFailedException;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\DatabaseConnection;
use CraftCms\Yii2Adapter\LaravelTransaction;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;
use yii\base\Event;
use yii\base\Exception;
use yii\db\Exception as DbException;
use yii\db\Transaction;

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
class Connection extends DatabaseConnection
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

    public static function registerEvents(): void
    {
        EventFacade::listen(function(BackupCreating $event) {
            $db = Craft::$app->getDb();
            if ($event->connection->getName() !== $db->getLaravelConnection()->getName()) {
                return;
            }
            if (!$db->hasEventHandlers(self::EVENT_BEFORE_CREATE_BACKUP)) {
                return;
            }

            $yiiEvent = new BackupEvent([
                'file' => $event->file,
                'ignoreTables' => $event->ignoreTables,
            ]);
            $db->trigger(self::EVENT_BEFORE_CREATE_BACKUP, $yiiEvent);
            $event->ignoreTables = self::_normalizeLegacyTableNames($yiiEvent->ignoreTables ?? []);
        });

        EventFacade::listen(function(BackupCreated $event) {
            $db = Craft::$app->getDb();
            if ($event->connection->getName() !== $db->getLaravelConnection()->getName()) {
                return;
            }
            if (!$db->hasEventHandlers(self::EVENT_AFTER_CREATE_BACKUP)) {
                return;
            }

            $db->trigger(self::EVENT_AFTER_CREATE_BACKUP, new BackupEvent([
                'file' => $event->file,
            ]));
        });

        EventFacade::listen(function(BackupRestoring $event) {
            $db = Craft::$app->getDb();
            if ($event->connection->getName() !== $db->getLaravelConnection()->getName()) {
                return;
            }
            if (!$db->hasEventHandlers(self::EVENT_BEFORE_RESTORE_BACKUP)) {
                return;
            }

            $db->trigger(self::EVENT_BEFORE_RESTORE_BACKUP, new RestoreEvent([
                'file' => $event->file,
            ]));
        });

        EventFacade::listen(function(BackupRestored $event) {
            $db = Craft::$app->getDb();
            if ($event->connection->getName() !== $db->getLaravelConnection()->getName()) {
                return;
            }
            if (!$db->hasEventHandlers(self::EVENT_AFTER_RESTORE_BACKUP)) {
                return;
            }

            $db->trigger(self::EVENT_AFTER_RESTORE_BACKUP, new BackupEvent([
                'file' => $event->file,
            ]));
        });
    }

    /**
     * @var callable[]
     * @see onAfterTransaction()
     */
    private array $afterTransactionCallbacks = [];

    /**
     * @var bool|null whether this is MariaDB.
     * @see getIsMaria()
     */
    private ?bool $_isMaria = null;

    /**
     * @var bool|null whether the database supports 4+ byte characters
     * @see getSupportsMb4()
     * @see setSupportsMb4()
     */
    private ?bool $_supportsMb4 = null;

    /**
     * Returns whether this is a MySQL (or MySQL-like) connection.
     *
     * @return bool
     */
    public function getIsMysql(): bool
    {
        return $this->getDriverName() === Connection::DRIVER_MYSQL;
    }

    /**
     * Returns whether this is a MariaDB connection.
     *
     * @return bool
     * @since 5.0.0
     */
    public function getIsMaria(): bool
    {
        if (!isset($this->_isMaria)) {
            $this->_isMaria = $this->getIsMysql() && str_contains(strtolower($this->getSchema()->getServerVersion()), 'mariadb');
        }
        return $this->_isMaria;
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
     * Returns the human-facing driver label (MySQL, MariaDB, or PostgreSQL).
     *
     * @return string
     * @since 4.4.1
     */
    public function getDriverLabel(): string
    {
        return match (true) {
            $this->getIsMaria() => 'MariaDB',
            $this->getIsMysql() => 'MySQL',
            default => 'PostgreSQL',
        };
    }

    /**
     * Returns whether the database supports 4+ byte characters.
     *
     * @return bool
     */
    public function getSupportsMb4(): bool
    {
        if (!isset($this->_supportsMb4)) {
            if (!Cms::isInstalled()) {
                return false;
            }

            // SQLite has no charset restrictions, so it always supports mb4
            if (!$this->getIsMysql() && !$this->getIsPgsql()) {
                $this->_supportsMb4 = true;
            } else {
                // if elements_sites supports mb4, pretty good chance everything else does too
                $this->_supportsMb4 = $this->getSchema()->supportsMb4(Table::ELEMENTS_SITES);
            }
        }
        return $this->_supportsMb4;
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
        if (Env::normalizeBooleanValue(Env::get('CRAFT_NO_DB'))) {
            throw new DbConnectException('Craft CMS can’t connect to the database.');
        }

        try {
            parent::open();
        } catch (DbException $e) {
            Log::error($e->getMessage(), [__METHOD__]);

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

            Log::error($e->getMessage(), [__METHOD__]);
            throw new DbConnectException('Craft CMS can’t connect to the database.', 0, $e);
        } catch (Throwable $e) {
            Log::error($e->getMessage(), [__METHOD__]);
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
        return app(Backups::class)->getBackupFilePath(
            connection: $this->getLaravelConnection(),
            backupFormat: $this->getIsPgsql() ? $this->getSchema()->getBackupFormat() : null,
        );
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
            Table::CACHE,
            Table::IMAGETRANSFORMINDEX,
            Table::RESOURCEPATHS,
            Table::PHPSESSIONS,
            Table::SESSIONS,
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
        try {
            return app(Backups::class)->backup(
                connection: $this->getLaravelConnection(),
                backupFormat: $this->getIsPgsql() ? $this->getSchema()->getBackupFormat() : null,
                ignoreTables: self::_normalizeLegacyTableNames($this->getIgnoredBackupTables()),
            );
        } catch (CommandFailedException $e) {
            throw new ShellCommandException($e->command, $e->exitCode, $e->error, $e->getMessage());
        }
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
        try {
            app(Backups::class)->backupTo(
                filePath: $filePath,
                connection: $this->getLaravelConnection(),
                backupFormat: $this->getIsPgsql() ? $this->getSchema()->getBackupFormat() : null,
                ignoreTables: self::_normalizeLegacyTableNames($this->getIgnoredBackupTables()),
            );
        } catch (CommandFailedException $e) {
            throw new ShellCommandException($e->command, $e->exitCode, $e->error, $e->getMessage());
        } catch (RuntimeException $e) {
            throw new Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string[] $tables
     * @return string[]
     */
    private static function _normalizeLegacyTableNames(array $tables): array
    {
        return array_map(
            static fn(string $table) => Table::withoutYiiPlaceholder($table),
            $tables,
        );
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
        try {
            app(Backups::class)->restore(
                filePath: $filePath,
                connection: $this->getLaravelConnection(),
                restoreFormat: $this->getIsPgsql() ? $this->getSchema()->getRestoreFormat() : null,
            );
        } catch (CommandFailedException $e) {
            throw new ShellCommandException($e->command, $e->exitCode, $e->error, $e->getMessage());
        } catch (RuntimeException $e) {
            throw new Exception($e->getMessage(), 0, $e);
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
        if ($refresh || ($refresh === null && !Cms::isInstalled())) {
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
        if ($refresh || ($refresh === null && !Cms::isInstalled())) {
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
     * Invokes a callback function once the connection is no longer in a transaction.
     *
     * If no transaction is currently active, the callback will be invoked immediately.
     *
     * @param callable $callback
     * @since 4.5.12
     */
    public function onAfterTransaction(callable $callback): void
    {
        if ($this->getTransaction() === null) {
            $callback();
        } else {
            $this->afterTransactionCallbacks[] = $callback;
        }
    }

    /**
     * @inheritdoc
     */
    public function trigger($name, Event $event = null)
    {
        if (
            in_array($name, [self::EVENT_COMMIT_TRANSACTION, self::EVENT_ROLLBACK_TRANSACTION]) &&
            !$this->getTransaction()
        ) {
            while ($callback = array_shift($this->afterTransactionCallbacks)) {
                $callback();
            }
        }

        parent::trigger($name, $event);
    }

    /**
     * Generates a FK, index, or PK name.
     *
     * @param string $prefix
     * @return string
     */
    private function _objectName(string $prefix): string
    {
        return $this->tablePrefix . $prefix . '_' . Str::random(36);
    }

    /**
     * @return Transaction|null
     */
    public function getTransaction(): ?Transaction
    {
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            return new LaravelTransaction(['db' => $this]);
        }

        return null;
    }

    public function transaction(callable $callback, $isolationLevel = null)
    {
        return \Illuminate\Support\Facades\DB::transaction($callback);
    }

    public function beginTransaction($isolationLevel = null)
    {
        $this->open();

        $transaction = new LaravelTransaction(['db' => $this]);
        $transaction->begin($isolationLevel);

        return $transaction;
    }
}
