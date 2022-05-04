<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Craft;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use DateTime;
use yii\db\Query as YiiQuery;

/**
 * @inheritdoc
 * @property Connection $db Connection the DB connection that this command is associated with.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Command extends \yii\db\Command
{
    /**
     * @inheritdoc
     *
     * If the table contains `dateCreated`, `dateUpdated`, and/or `uid` columns, those values will be included
     * automatically, if not already set.
     */
    public function insert($table, $columns): Command
    {
        if (!isset($columns['dateCreated']) && $this->db->columnExists($table, 'dateCreated')) {
            $now = Db::prepareDateForDb(new DateTime());
            $columns['dateCreated'] = $now;
        }

        if (!isset($columns['dateUpdated']) && $this->db->columnExists($table, 'dateUpdated')) {
            $columns['dateUpdated'] = $now ?? Db::prepareDateForDb(new DateTime());
        }

        if (!isset($columns['uid']) && $this->db->columnExists($table, 'uid')) {
            $columns['uid'] = StringHelper::UUID();
        }

        return parent::insert($table, $columns);
    }

    /**
     * @inheritdoc
     *
     * If the table contains `dateCreated`, `dateUpdated`, and/or `uid` columns, those values will be included
     * automatically, if not already set.
     */
    public function batchInsert($table, $columns, $rows): Command
    {
        if (empty($rows)) {
            return $this;
        }

        if (!in_array('dateCreated', $columns) && $this->db->columnExists($table, 'dateCreated')) {
            $columns[] = 'dateCreated';
            $now = Db::prepareDateForDb(new DateTime());
            foreach ($rows as &$row) {
                $row[] = $now;
            }
        }

        if (!in_array('dateUpdated', $columns) && $this->db->columnExists($table, 'dateUpdated')) {
            $columns[] = 'dateUpdated';
            $now = $now ?? Db::prepareDateForDb(new DateTime());
            foreach ($rows as &$row) {
                $row[] = $now;
            }
        }

        if (!in_array('uid', $columns) && $this->db->columnExists($table, 'uid')) {
            $columns[] = 'uid';
            foreach ($rows as &$row) {
                $row[] = StringHelper::UUID();
            }
        }

        return parent::batchInsert($table, $columns, $rows);
    }

    /**
     * @inheritdoc
     *
     * If the table contains `dateCreated`, `dateUpdated`, and/or `uid` columns, those values will be included
     * for new rows automatically, if not already set.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|YiiQuery $insertColumns the column data (name => value) to be inserted into the table or instance
     * of [[YiiQuery]] to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params the parameters to be bound to the command.
     * @param bool $updateTimestamp Whether the `dateUpdated` column should be updated for existing rows, if the table has one.
     * @return $this the command object itself.
     */
    public function upsert($table, $insertColumns, $updateColumns = true, $params = [], bool $updateTimestamp = true): Command
    {
        if (is_array($insertColumns)) {
            if (!isset($insertColumns['dateCreated']) && $this->db->columnExists($table, 'dateCreated')) {
                $now = Db::prepareDateForDb(new DateTime());
                $insertColumns['dateCreated'] = $now;
            }

            if (!isset($insertColumns['dateUpdated']) && $this->db->columnExists($table, 'dateUpdated')) {
                $now = $now ?? Db::prepareDateForDb(new DateTime());
                $insertColumns['dateUpdated'] = $now;
            }

            if (!isset($insertColumns['uid']) && $this->db->columnExists($table, 'uid')) {
                $insertColumns['uid'] = StringHelper::UUID();
            }

            if (
                $updateColumns !== false &&
                !isset($updateColumns['dateUpdated']) &&
                $this->_updateTimestamp($updateTimestamp, $table)
            ) {
                if ($updateColumns === true) {
                    $updateColumns = array_merge($insertColumns);
                    unset($updateColumns['dateCreated'], $updateColumns['uid']);
                }

                $updateColumns['dateUpdated'] = $now ?? Db::prepareDateForDb(new DateTime());
            }
        }

        return parent::upsert($table, $insertColumns, $updateColumns, $params);
    }

    /**
     * @inheritdoc
     * @param string $table The table to be updated.
     * @param array $columns The column data (name => value) to be updated.
     * @param string|array $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @param bool $updateTimestamp Whether the `dateUpdated` column should be updated, if the table has one.
     * @return static The command object itself.
     */
    public function update($table, $columns, $condition = '', $params = [], bool $updateTimestamp = true): Command
    {
        if (!isset($columns['dateUpdated']) && $this->_updateTimestamp($updateTimestamp, $table)) {
            $columns['dateUpdated'] = Db::prepareDateForDb(new DateTime());
        }

        return parent::update($table, $columns, $condition, $params);
    }

    /**
     * Returns whether a table’s `dateUpdated` column should be updated.
     *
     * @param bool $updateTimestamp
     * @param string $table
     * @return bool
     */
    private function _updateTimestamp(bool $updateTimestamp, string $table): bool
    {
        return $updateTimestamp && $this->db->columnExists($table, 'dateUpdated');
    }

    /**
     * Creates a DELETE command that will only delete duplicate rows from a table.
     *
     * For example,
     *
     * ```php
     * $connection->createCommand()->deleteDuplicates('user', ['email'])->execute();
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table The table where the data will be deleted from
     * @param string[] $columns The column names that contain duplicate data
     * @param string $pk The primary key column name
     * @return $this the command object itself
     * @since 3.5.2
     */
    public function deleteDuplicates(string $table, array $columns, string $pk = 'id'): self
    {
        $sql = $this->db->getQueryBuilder()->deleteDuplicates($table, $columns, $pk);
        return $this->setSql($sql);
    }

    /**
     * Creates a SQL statement for replacing some text with other text in a given table column.
     *
     * @param string $table The table to be updated.
     * @param string $column The column to be searched.
     * @param string $find The text to be searched for.
     * @param string $replace The replacement text.
     * @param array|string $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @return Command The command object itself.
     */
    public function replace(string $table, string $column, string $find, string $replace, array|string $condition = '', array $params = []): Command
    {
        $sql = $this->db->getQueryBuilder()->replace($table, $column, $find, $replace, $condition, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a SQL statement for dropping a DB table, if it exists.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     * @return Command the command object itself
     */
    public function dropTableIfExists(string $table): Command
    {
        $sql = $this->db->getQueryBuilder()->dropTableIfExists($table);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL statement for renaming a DB sequence.
     *
     * @param string $oldName the sequence to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new sequence name. The name will be properly quoted by the method.
     * @return Command the command object itself
     */
    public function renameSequence(string $oldName, string $newName): Command
    {
        $sql = $this->db->getQueryBuilder()->renameSequence($oldName, $newName);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL statement for soft-deleting a row.
     *
     * @param string $table The table to be updated.
     * @param array|string $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @return static The command object itself.
     * @since 3.1.0
     */
    public function softDelete(string $table, array|string $condition = '', array $params = []): Command
    {
        return $this->update($table, [
            'dateDeleted' => Db::prepareDateForDb(new DateTime()),
        ], $condition, $params, false);
    }

    /**
     * Creates a SQL statement for restoring a soft-deleted row.
     *
     * @param string $table The table to be updated.
     * @param array|string $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @return static The command object itself.
     * @since 3.1.0
     */
    public function restore(string $table, array|string $condition = '', array $params = []): Command
    {
        return $this->update($table, [
            'dateDeleted' => null,
        ], $condition, $params, false);
    }

    /**
     * Logs the current database query if query logging is enabled and returns
     * the profiling token if profiling is enabled.
     * @param string $category the log category.
     * @return array array of two elements, the first is boolean of whether profiling is enabled or not.
     * The second is the rawSql if it has been created.
     */
    protected function logQuery($category): array
    {
        if ($this->db->enableLogging) {
            $rawSql = $this->getRawSql();
            Craft::debug("SQL query:\n" . $rawSql, $category);
        }
        if (!$this->db->enableProfiling) {
            return [false, isset($rawSql) ? $rawSql : null];
        }

        return [true, isset($rawSql) ? $rawSql : $this->getRawSql()];
    }
}
