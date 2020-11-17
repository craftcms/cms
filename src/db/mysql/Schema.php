<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db\mysql;

use Composer\Util\Platform;
use Craft;
use craft\db\Connection;
use craft\db\TableSchema;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use mikehaertl\shellcommand\Command as ShellCommand;
use yii\base\ErrorException;
use yii\db\Exception;

/**
 * @inheritdoc
 * @method TableSchema getTableSchema($name, $refresh = false) Obtains the schema information for the named table.
 * @property Connection $db
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Schema extends \yii\db\mysql\Schema
{
    const TYPE_TINYTEXT = 'tinytext';
    const TYPE_MEDIUMTEXT = 'mediumtext';
    const TYPE_LONGTEXT = 'longtext';
    const TYPE_ENUM = 'enum';

    /**
     * @inheritdoc
     */
    public $columnSchemaClass = ColumnSchema::class;

    /**
     * @var int The maximum length that objects' names can be.
     */
    public $maxObjectNameLength = 64;

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
     *
     * This method may be overridden by child classes to create a DBMS-specific query builder.
     *
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->db, [
            'separator' => "\n"
        ]);
    }

    /**
     * Quotes a database name for use in a query.
     *
     * @param string $name
     * @return string
     */
    public function quoteDatabaseName(string $name): string
    {
        return '`' . $name . '`';
    }

    /**
     * Releases an existing savepoint.
     *
     * @param string $name The savepoint name.
     * @throws Exception
     */
    public function releaseSavepoint($name)
    {
        try {
            parent::releaseSavepoint($name);
        } catch (Exception $e) {
            // Specifically look for a "SAVEPOINT does not exist" error.
            if ($e->getCode() == 42000 && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1305) {
                Craft::warning('Tried to release a savepoint, but it does not exist: ' . $e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name The savepoint name.
     * @throws Exception
     */
    public function rollBackSavepoint($name)
    {
        try {
            parent::rollBackSavepoint($name);
        } catch (Exception $e) {
            // Specifically look for a "SAVEPOINT does not exist" error.
            if ($e->getCode() == 42000 && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1305) {
                Craft::warning('Tried to roll back a savepoint, but it does not exist: ' . $e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See [[ColumnSchemaBuilder::$type]].
     * @param int|string|array $length length or precision of the column. See [[ColumnSchemaBuilder::$length]].
     * @return ColumnSchemaBuilder column schema builder instance
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->db);
    }

    /**
     * Returns the default backup command to execute.
     *
     * @param string[]|null The table names whose data should be excluded from the backup
     * @return string The command to execute
     * @throws ErrorException
     */
    public function getDefaultBackupCommand(array $ignoreTables = null): string
    {
        $defaultArgs =
            ' --defaults-extra-file="' . $this->_createDumpConfigFile() . '"' .
            ' --add-drop-table' .
            ' --comments' .
            ' --create-options' .
            ' --dump-date' .
            ' --no-autocommit' .
            ' --routines' .
            ' --default-character-set=' . Craft::$app->getConfig()->getDb()->charset .
            ' --set-charset' .
            ' --triggers' .
            ' --no-tablespaces';

        // If the server is MySQL 5.x, we need to see what version of mysqldump is installed (5.x or 8.x)
        if (version_compare(App::normalizeVersion(Craft::$app->getDb()->getSchema()->getServerVersion()), "8", "<")) {
            // Find out if the db supports column-statistics
            $shellCommand = new ShellCommand();

            if (Platform::isWindows()) {
                $shellCommand->setCommand('mysqldump --help | findstr "column-statistics"');
            } else {
                $shellCommand->setCommand('mysqldump --help | grep "column-statistics"');
            }

            // If we don't have proc_open, maybe we've got exec
            if (!function_exists('proc_open') && function_exists('exec')) {
                $shellCommand->useExec = true;
            }

            $success = $shellCommand->execute();

            // if there was output, then they're running mysqldump 8.x against a 5.x database.
            if ($success && $shellCommand->getOutput()) {
                $defaultArgs .= ' --skip-column-statistics';
            }
        }

        if ($ignoreTables === null) {
            $ignoreTables = $this->db->getIgnoredBackupTables();
        }
        $ignoreTableArgs = [];
        foreach ($ignoreTables as $table) {
            $table = $this->getRawTableName($table);
            $ignoreTableArgs[] = "--ignore-table={database}.{$table}";
        }

        $schemaDump = 'mysqldump' .
            $defaultArgs .
            ' --single-transaction' .
            ' --no-data' .
            ' --result-file="{file}"' .
            ' {database}';

        $dataDump = 'mysqldump' .
            $defaultArgs .
            ' --no-create-info' .
            ' ' . implode(' ', $ignoreTableArgs) .
            ' {database}' .
            ' >> "{file}"';

        return $schemaDump . ' && ' . $dataDump;
    }

    /**
     * Returns the default database restore command to execute.
     *
     * @return string The command to execute
     * @throws ErrorException
     */
    public function getDefaultRestoreCommand(): string
    {
        return 'mysql' .
            ' --defaults-extra-file="' . $this->_createDumpConfigFile() . '"' .
            ' {database}' .
            ' < "{file}"';
    }

    /**
     * Returns all indexes for the given table. Each array element is of the following structure:
     *
     * ```php
     * [
     *     'IndexName' => [
     *         'columns' => ['col1' [, ...]],
     *         'unique' => false
     *     ],
     * ]
     * ```
     *
     * @param string $tableName The name of the table to get the indexes for.
     * @return array All indexes for the given table.
     * @throws \yii\base\NotSupportedException
     */
    public function findIndexes(string $tableName): array
    {
        $tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $table = Craft::$app->getDb()->getSchema()->getTableSchema($tableName);
        $sql = $this->getCreateTableSql($table);
        $indexes = [];

        $regexp = '/(UNIQUE\s+)?KEY\s+([^\(\s]+)\s*\(([^\(\)]+)\)/mi';
        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexName = str_replace('`', '', $match[2]);
                $indexColumns = array_map('trim', explode(',', str_replace('`', '', $match[3])));
                $indexes[$indexName] = [
                    'columns' => $indexColumns,
                    'unique' => !empty($match[1]),
                ];
            }
        }

        return $indexes;
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name table name
     * @return TableSchema|null driver dependent table metadata. Null if the table does not exist.
     * @throws \Exception
     */
    protected function loadTableSchema($name)
    {
        $table = new TableSchema;
        $this->resolveTableNames($table, $name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            return $table;
        }

        return null;
    }

    /**
     * Collects extra foreign key information details for the given table.
     *
     * @param TableSchema $table the table metadata
     * @throws Exception
     */
    protected function findConstraints($table)
    {
        // This is almost directly copied from yii\db\mysql\Schema::findConstraints() (Yii 2.0.37) except:
        // - addition of DELETE_RULE & UPDATE_RULE in the SELECT clause
        // - addition of ON DELETE & ON UPDATE subpatterns in fallback regex
        // - calls to $table->addExtendedForeignKey()

        $sql = <<<SQL
SELECT
    `kcu`.`CONSTRAINT_NAME` AS `constraint_name`,
    `kcu`.`COLUMN_NAME` AS `column_name`,
    `kcu`.`REFERENCED_TABLE_NAME` AS `referenced_table_name`,
    `kcu`.`REFERENCED_COLUMN_NAME` AS `referenced_column_name`,
    `rc`.`DELETE_RULE`,
    `rc`.`UPDATE_RULE`
FROM `information_schema`.`REFERENTIAL_CONSTRAINTS` AS `rc`
JOIN `information_schema`.`KEY_COLUMN_USAGE` AS `kcu` ON
    (
        `kcu`.`CONSTRAINT_CATALOG` = `rc`.`CONSTRAINT_CATALOG` OR
        (`kcu`.`CONSTRAINT_CATALOG` IS NULL AND `rc`.`CONSTRAINT_CATALOG` IS NULL)
    ) AND
    `kcu`.`CONSTRAINT_SCHEMA` = `rc`.`CONSTRAINT_SCHEMA` AND
    `kcu`.`CONSTRAINT_NAME` = `rc`.`CONSTRAINT_NAME`
WHERE `rc`.`CONSTRAINT_SCHEMA` = database() AND `kcu`.`TABLE_SCHEMA` = database()
AND `rc`.`TABLE_NAME` = :tableName AND `kcu`.`TABLE_NAME` = :tableName1
SQL;

        try {
            $rows = $this->db->createCommand($sql, [':tableName' => $table->name, ':tableName1' => $table->name])->queryAll();
            $constraints = [];

            foreach ($rows as $i => $row) {
                $constraints[$row['constraint_name']]['referenced_table_name'] = $row['referenced_table_name'];
                $constraints[$row['constraint_name']]['columns'][$row['column_name']] = $row['referenced_column_name'];

                $table->addExtendedForeignKey($i, [
                    'deleteType' => $row['DELETE_RULE'],
                    'updateType' => $row['UPDATE_RULE'],
                ]);
            }

            $table->foreignKeys = [];
            foreach ($constraints as $name => $constraint) {
                $table->foreignKeys[$name] = array_merge(
                    [$constraint['referenced_table_name']],
                    $constraint['columns']
                );
            }
        } catch (\Exception $e) {
            $previous = $e->getPrevious();
            if (!$previous instanceof \PDOException || strpos($previous->getMessage(), 'SQLSTATE[42S02') === false) {
                throw $e;
            }

            // table does not exist, try to determine the foreign keys using the table creation sql
            $sql = $this->getCreateTableSql($table);
            $regexp = '/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)(?:\s+ON DELETE (\w+))?(?:\s+ON UPDATE (\w+))?/mi';
            if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $i => $match) {
                    $fks = array_map('trim', explode(',', str_replace('`', '', $match[1])));
                    $pks = array_map('trim', explode(',', str_replace('`', '', $match[3])));
                    $constraint = [str_replace('`', '', $match[2])];
                    foreach ($fks as $k => $name) {
                        $constraint[$name] = $pks[$k];
                    }
                    $table->foreignKeys[md5(serialize($constraint))] = $constraint;

                    $table->addExtendedForeignKey($i, [
                        'deleteType' => $match[4] ?? 'RESTRICT',
                        'updateType' => $match[5] ?? 'RESTRICT',
                    ]);
                }
                $table->foreignKeys = array_values($table->foreignKeys);
            }
        }
    }

    /**
     * Creates a temporary my.cnf file based on the DB config settings.
     *
     * @return string The path to the my.cnf file
     * @throws ErrorException
     */
    private function _createDumpConfigFile(): string
    {
        $filePath = FileHelper::normalizePath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'my.cnf';

        $parsed = Db::parseDsn($this->db->dsn);
        $username = $this->db->getIsPgsql() && !empty($parsed['user']) ? $parsed['user'] : $this->db->username;
        $password = $this->db->getIsPgsql() && !empty($parsed['password']) ? $parsed['password'] : $this->db->password;
        $contents = '[client]' . PHP_EOL .
            'user=' . $username . PHP_EOL .
            'password="' . addslashes($password) . '"';

        if (isset($parsed['unix_socket'])) {
            $contents .= PHP_EOL . 'socket=' . $parsed['unix_socket'];
        } else {
            $contents .= PHP_EOL . 'host=' . ($parsed['host'] ?? '') .
                PHP_EOL . 'port=' . ($parsed['port'] ?? '');
        }

        FileHelper::writeToFile($filePath, $contents);

        // Avoid a “world-writable config file 'my.cnf' is ignored” warning
        chmod($filePath, 0644);

        return $filePath;
    }
}
