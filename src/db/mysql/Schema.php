<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db\mysql;

use Craft;
use craft\db\TableSchema;
use craft\helpers\FileHelper;
use yii\db\Exception;

/**
 * @inheritdoc
 * @method TableSchema getTableSchema($name, $refresh = false) Obtains the schema information for the named table.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
     * @inheritdoc
     */
    public $columnSchemaClass = ColumnSchema::class;

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
     * @return string The command to execute
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function getDefaultBackupCommand(): string
    {
        $defaultArgs =
            ' --defaults-extra-file="' . $this->_createDumpConfigFile() . '"' .
            ' --add-drop-table' .
            ' --comments' .
            ' --create-options' .
            ' --dump-date' .
            ' --no-autocommit' .
            ' --routines' .
            ' --set-charset' .
            ' --triggers';

        $ignoreTableArgs = [];
        foreach (Craft::$app->getDb()->getIgnoredBackupTables() as $table) {
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
     * @throws \yii\base\ErrorException
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

    // Protected Methods
    // =========================================================================

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
        parent::findConstraints($table);

        // Modified from parent to get extended FK information.
        $tableName = $this->quoteValue($table->name);

        $sql = <<<SQL
SELECT
    kcu.constraint_name,
    kcu.column_name,
    kcu.referenced_table_name,
    kcu.referenced_column_name,
    rc.UPDATE_RULE,
    rc.DELETE_RULE
FROM information_schema.referential_constraints AS rc
JOIN information_schema.key_column_usage AS kcu ON
    (
        kcu.constraint_catalog = rc.constraint_catalog OR
        (kcu.constraint_catalog IS NULL AND rc.constraint_catalog IS NULL)
    ) AND
    kcu.constraint_schema = rc.constraint_schema AND
    kcu.constraint_name = rc.constraint_name
WHERE rc.constraint_schema = database() AND kcu.table_schema = database()
AND rc.table_name = {$tableName} AND kcu.table_name = {$tableName}
SQL;

        $extendedConstraints = $this->db->createCommand($sql)->queryAll();

        foreach ($extendedConstraints as $key => $extendedConstraint) {
            $table->addExtendedForeignKey($key, [
                'updateType' => $extendedConstraint['UPDATE_RULE'],
                'deleteType' => $extendedConstraint['DELETE_RULE']
            ]);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Creates a temporary my.cnf file based on the DB config settings.
     *
     * @return string The path to the my.cnf file
     * @throws \yii\base\ErrorException
     */
    private function _createDumpConfigFile(): string
    {
        $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'my.cnf';

        $dbConfig = Craft::$app->getConfig()->getDb();
        $contents = '[client]' . PHP_EOL .
            'user=' . $dbConfig->user . PHP_EOL .
            'password="' . addslashes($dbConfig->password) . '"' . PHP_EOL .
            'host=' . $dbConfig->server . PHP_EOL .
            'port=' . $dbConfig->port;

        if ($dbConfig->unixSocket) {
            $contents .= PHP_EOL . 'socket=' . $dbConfig->unixSocket;
        }

        FileHelper::writeToFile($filePath, $contents);

        // Avoid a “world-writable config file 'my.cnf' is ignored” warning
        chmod($filePath, 0644);

        return $filePath;
    }
}
