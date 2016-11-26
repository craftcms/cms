<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db\pgsql;

use Craft;
use craft\db\TableSchema;
use craft\services\Config;
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
     * Returns the default backup command to execute.
     *
     * @return string|false The command to execute
     */
    public function getDefaultBackupCommand()
    {
        return 'pg_dump'.
            ' --dbname={database}'.
            ' --host={server}'.
            ' --port={port}'.
            ' --username={user}'.
            ' --no-password'.
            ' --if-exists'.
            ' --clean'.
            ' --file={file}'.
            ' --schema={schema}';
    }

    /**
     * Returns the default database restore command to execute.
     *
     * @return string The command to execute
     */
    public function getDefaultRestoreCommand()
    {
        return 'psql'.
            ' --dbname={database}'.
            ' --host={server}'.
            ' --port={port}'.
            ' --username={user}'.
            ' --no-password'.
            ' < {file}';
    }

    /**
     * Returns all indexes for the given table. Each array element is of the following structure:
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param string $tableName The name of the table to get the indexes for.
     *
     * @return array All indexes for the given table.
     */
    public function findIndexes($tableName)
    {
        $tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $table = Craft::$app->getDb()->getSchema()->getTableSchema($tableName);
        $indexes = [];

        $rows = $this->getIndexInformation($table);

        foreach ($rows as $row) {
            $column = $row['columnname'];

            if (!empty($column) && $column[0] === '"') {
                // postgres will quote names that are not lowercase-only
                // https://github.com/yiisoft/yii2/issues/10613
                $column = substr($column, 1, -1);
            }
            $indexes[$row['indexname']][] = $column;
        }

        return $indexes;
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name table name
     *
     * @return TableSchema|null driver dependent table metadata. Null if the table does not exist.
     */
    public function loadTableSchema($name)
    {
        $table = new TableSchema();
        $this->resolveTableNames($table, $name);
        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            return $table;
        } else {
            return null;
        }
    }

    /**
     * Gets information about given table indexes.
     *
     * @param TableSchema $table The table metadata
     *
     * @return array Index and column names
     */
    protected function getIndexInformation($table)
    {
        $sql = <<<SQL
SELECT
    i.relname as indexname,
    pg_get_indexdef(idx.indexrelid, k + 1, TRUE) AS columnname
FROM (
  SELECT *, generate_subscripts(indkey, 1) AS k
  FROM pg_index
) idx
INNER JOIN pg_class i ON i.oid = idx.indexrelid
INNER JOIN pg_class c ON c.oid = idx.indrelid
INNER JOIN pg_namespace ns ON c.relnamespace = ns.oid
WHERE c.relname = :tableName AND ns.nspname = :schemaName
AND idx.indisprimary = FALSE 
ORDER BY i.relname, k
SQL;

        return $this->db->createCommand($sql, [
            ':schemaName' => $table->schemaName,
            ':tableName' => $table->name,
        ])->queryAll();
    }
}
