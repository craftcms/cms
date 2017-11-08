<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db;

use craft\helpers\Db;
use craft\helpers\StringHelper;

/**
 * @inheritdoc
 *
 * @property Connection $db Connection the DB connection that this command is associated with.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Command extends \yii\db\Command
{
    // Public Methods
    // =========================================================================

    /**
     * Creates an INSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->insert('user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ])->execute();
     * ```
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table               The table that new rows will be inserted into.
     * @param array  $columns             The column data (name => value) to be inserted into the table.
     * @param bool   $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     *
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
     * Creates a batch INSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ])->execute();
     * ```
     *
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * Also note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table               The table that new rows will be inserted into.
     * @param array  $columns             The column names.
     * @param array  $rows                The rows to be batch inserted into the table.
     * @param bool   $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     *
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
     * Creates a command that will insert some given data into a table, or update an existing row
     * in the event of a key constraint violation.
     *
     * @param string $table                The table that the row will be inserted into, or updated.
     * @param array  $keyColumns           The key-constrained column data (name => value) to be inserted into the table
     *                                     in the event that a new row is getting created
     * @param array  $updateColumns        The non-key-constrained column data (name => value) to be inserted into the table
     *                                     or updated in the existing row.
     * @param bool   $includeAuditColumns  Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     *
     * @return Command The command object itself.
     */
    public function upsert(string $table, array $keyColumns, array $updateColumns, bool $includeAuditColumns = true): Command
    {
        if ($includeAuditColumns) {
            $now = Db::prepareDateForDb(new \DateTime());
            $updateColumns['dateCreated'] = $now;
            $updateColumns['dateUpdated'] = $now;
            $updateColumns['uid'] = StringHelper::UUID();
        }

        $params = [];
        $sql = $this->db->getQueryBuilder()->upsert($table, $keyColumns, $updateColumns, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates an UPDATE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->update('user', ['status' => 1], 'age > 30')->execute();
     * ```
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string       $table               The table to be updated.
     * @param array        $columns             The column data (name => value) to be updated.
     * @param string|array $condition           The condition that will be put in the WHERE part. Please
     *                                          refer to [[Query::where()]] on how to specify condition.
     * @param array        $params              The parameters to be bound to the command.
     * @param bool         $includeAuditColumns Whether the `dateUpdated` value should be added to $columns.
     *
     * @return static The command object itself.
     */
    public function update($table, $columns, $condition = '', $params = [], $includeAuditColumns = true)
    {
        if ($includeAuditColumns) {
            $columns['dateUpdated'] = Db::prepareDateForDb(new \DateTime());
        }

        parent::update($table, $columns, $condition, $params);

        return $this;
    }

    /**
     * Creates a SQL statement for replacing some text with other text in a given table column.
     *
     * @param string       $table     The table to be updated.
     * @param string       $column    The column to be searched.
     * @param string       $find      The text to be searched for.
     * @param string       $replace   The replacement text.
     * @param string|array $condition The condition that will be put in the WHERE part. Please
     *                                refer to [[Query::where()]] on how to specify condition.
     * @param array        $params    The parameters to be bound to the command.
     *
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
     *
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
     *
     * @return Command the command object itself
     */
    public function renameSequence(string $oldName, string $newName): Command
    {
        $sql = $this->db->getQueryBuilder()->renameSequence($oldName, $newName);

        return $this->setSql($sql);
    }
}
