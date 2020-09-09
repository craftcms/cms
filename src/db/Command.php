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
     * @param string $table The table that new rows will be inserted into.
     * @param array $columns The column data (name => value) to be inserted into the table.
     * @param bool $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     * @return static the command object itself
     */
    public function insert($table, $columns, $includeAuditColumns = true)
    {
        if ($includeAuditColumns) {
            $now = Db::prepareDateForDb(new \DateTime());

            if (empty($columns['dateCreated'])) {
                $columns['dateCreated'] = $now;
            }
            if (empty($columns['dateUpdated'])) {
                $columns['dateUpdated'] = $now;
            }
            if (empty($columns['uid'])) {
                $columns['uid'] = StringHelper::UUID();
            }
        }

        parent::insert($table, $columns);

        return $this;
    }

    /**
     * @inheritdoc
     * @param string $table The table that new rows will be inserted into.
     * @param array $columns The column names.
     * @param array $rows The rows to be batch inserted into the table.
     * @param bool $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     * @return static The command object itself.
     */
    public function batchInsert($table, $columns, $rows, $includeAuditColumns = true)
    {
        if (empty($rows)) {
            return $this;
        }

        if ($includeAuditColumns) {
            $columns[] = 'dateCreated';
            $columns[] = 'dateUpdated';
            $columns[] = 'uid';

            $date = Db::prepareDateForDb(new \DateTime());

            foreach ($rows as &$row) {
                $row[] = $date;
                $row[] = $date;
                $row[] = StringHelper::UUID();
            }
            unset($row);
        }

        parent::batchInsert($table, $columns, $rows);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or instance
     * of [[Query]] to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params the parameters to be bound to the command.
     * @param bool $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     * @return $this the command object itself.
     */
    public function upsert($table, $insertColumns, $updateColumns = true, $params = [], bool $includeAuditColumns = true): Command
    {
        if (is_bool($params)) {
            $includeAuditColumns = $params;
            $params = [];
            Craft::$app->getDeprecator()->log('craft\\db\\Command::upsert($includeAuditColumns)', 'The `$includeAuditColumns` argument on `craft\\db\\Command::upsert()` has been moved to the 5th position');
        }

        if ($includeAuditColumns && $updateColumns !== false) {
            if ($updateColumns === true) {
                $updateColumns = array_merge($insertColumns);
            }
            $now = Db::prepareDateForDb(new \DateTime());
            $updateColumns['dateCreated'] = $now;
            $updateColumns['dateUpdated'] = $now;
            $updateColumns['uid'] = StringHelper::UUID();
        }

        // todo: hack for BC with our old upsert() method. Remove in Craft 4
        // Merge any updateColumn data into insertColumns
        if (is_array($updateColumns)) {
            $insertColumns = array_merge($updateColumns, $insertColumns);
        }

        parent::upsert($table, $insertColumns, $updateColumns, $params);
        return $this;
    }

    /**
     * @inheritdoc
     * @param string $table The table to be updated.
     * @param array $columns The column data (name => value) to be updated.
     * @param string|array $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @param bool $includeAuditColumns Whether the `dateUpdated` value should be added to $columns.
     * @return static The command object itself.
     */
    public function update($table, $columns, $condition = '', $params = [], $includeAuditColumns = true)
    {
        if ($includeAuditColumns && !isset($columns['dateUpdated'])) {
            $columns['dateUpdated'] = Db::prepareDateForDb(new \DateTime());
        }

        parent::update($table, $columns, $condition, $params);

        return $this;
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
     * @param string|array $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @return Command The command object itself.
     */
    public function replace(string $table, string $column, string $find, string $replace, $condition = '', array $params = []): Command
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
     * @param string|array $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @return static The command object itself.
     * @since 3.1.0
     */
    public function softDelete(string $table, $condition = '', array $params = []): Command
    {
        return $this->update($table, [
            'dateDeleted' => Db::prepareDateForDb(new \DateTime()),
        ], $condition, $params, false);
    }

    /**
     * Creates a SQL statement for restoring a soft-deleted row.
     *
     * @param string $table The table to be updated.
     * @param string|array $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @return static The command object itself.
     * @since 3.1.0
     */
    public function restore(string $table, $condition = '', array $params = []): Command
    {
        return $this->update($table, [
            'dateDeleted' => null,
        ], $condition, $params, false);
    }
}
