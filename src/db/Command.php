<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db;

use craft\app\helpers\Db;
use craft\app\helpers\StringHelper;

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
     * @inheritdoc
     *
     * @param string  $table               The table that new rows will be inserted into.
     * @param array   $columns             The column data (name => value) to be inserted into the table.
     * @param boolean $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     *
     * @return $this the command object itself
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
     *
     * @param string  $table               The table that new rows will be inserted into.
     * @param array   $columns             The column names.
     * @param array   $rows                The rows to be batch inserted into the table.
     * @param boolean $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     *
     * @return $this The command object itself.
     */
    public function batchInsert($table, $columns, $rows, $includeAuditColumns = true)
    {
        if (!$rows) {
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
        }

        parent::batchInsert($table, $columns, $rows);

        return $this;
    }

    /**
     * Creates a command that will insert some given data into a table, or update an existing row
     * in the event of a key constraint violation.
     *
     * @param string  $table               The table that the row will be inserted into, or updated.
     * @param array   $keyColumns          The key-constrained column data (name => value) to be inserted into the table
     *                                     in the event that a new row is getting created
     * @param array   $updateColumns       The non-key-constrained column data (name => value) to be inserted into the table
     *                                     or updated in the existing row.
     * @param boolean $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns.
     *
     * @return Command The command object itself.
     */
    public function insertOrUpdate($table, $keyColumns, $updateColumns, $includeAuditColumns = true)
    {
        if ($includeAuditColumns) {
            $now = Db::prepareDateForDb(new \DateTime());
            $updateColumns['dateCreated'] = $now;
            $updateColumns['dateUpdated'] = $now;
            $updateColumns['uid'] = StringHelper::UUID();
        }

        $params = [];
        $sql = $this->db->getQueryBuilder()->insertOrUpdate($table, $keyColumns, $updateColumns, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * @inheritdoc
     *
     * @param string       $table               The table to be updated.
     * @param array        $columns             The column data (name => value) to be updated.
     * @param string|array $conditions          The condition that will be put in the WHERE part. Please
     *                                          refer to [[Query::where()]] on how to specify condition.
     * @param array        $params              The parameters to be bound to the command.
     * @param boolean      $includeAuditColumns Whether the `dateUpdated` value should be added to $columns.
     *
     * @return $this The command object itself.
     */
    public function update($table, $columns, $conditions = '', $params = [], $includeAuditColumns = true)
    {
        if ($includeAuditColumns) {
            $columns['dateUpdated'] = Db::prepareDateForDb(new \DateTime());
        }

        parent::update($table, $columns, $conditions, $params);

        return $this;
    }

    /**
     * Creates a SQL statement for replacing some text with other text in a given table column.
     *
     * @param string $table   The table to be updated.
     * @param string $column  The column to be searched.
     * @param string $find    The text to be searched for.
     * @param string $replace The replacement text.
     *
     * @return Command The command object itself.
     */
    public function replace($table, $column, $find, $replace)
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->replace($table, $column, $find, $replace, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a SQL statement for dropping a DB table, if it exists.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     *
     * @return Command the command object itself
     */
    public function dropTableIfExists($table)
    {
        $sql = $this->db->getQueryBuilder()->dropTableIfExists($table);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL statement for adding a new DB column at the beginning of a table.
     *
     * @param string $table  The table that the new column will be added to. The table name will be properly quoted by the method.
     * @param string $column The name of the new column. The name will be properly quoted by the method.
     * @param string $type   The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     *                       to convert the give column type to the physical one. For example, `string` will be converted
     *                       as `varchar(255)`, and `string not null` becomes `varchar(255) not null`.
     *
     * @return Command the command object itself
     */
    public function addColumnFirst($table, $column, $type)
    {
        $sql = $this->db->getQueryBuilder()->addColumnFirst($table, $column, $type);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL statement for adding a new DB column before another column in a table.
     *
     * @param string $table  The table that the new column will be added to. The table name will be properly quoted by the method.
     * @param string $column The name of the new column. The name will be properly quoted by the method.
     * @param string $type   The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     *                       to convert the give column type to the physical one. For example, `string` will be converted
     *                       as `varchar(255)`, and `string not null` becomes `varchar(255) not null`.
     * @param string $before The name of the column that the new column should be placed before.
     *
     * @return Command the command object itself
     */
    public function addColumnBefore($table, $column, $type, $before)
    {
        $sql = $this->db->getQueryBuilder()->addColumnBefore($table, $column, $type, $before);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL statement for adding a new DB column after another column in a table.
     *
     * @param string $table  The table that the new column will be added to. The table name will be properly quoted by the method.
     * @param string $column The name of the new column. The name will be properly quoted by the method.
     * @param string $type   The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     *                       to convert the give column type to the physical one. For example, `string` will be converted
     *                       as `varchar(255)`, and `string not null` becomes `varchar(255) not null`.
     * @param string $after  The name of the column that the new column should be placed after.
     *
     * @return Command the command object itself
     */
    public function addColumnAfter($table, $column, $type, $after)
    {
        $sql = $this->db->getQueryBuilder()->addColumnAfter($table, $column, $type, $after);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL statement for changing the definition of a column.
     *
     * @param string      $table   The table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string      $column  The name of the column to be changed. The name will be properly quoted by the method.
     * @param string      $type    The new column type. The [[getColumnType()]] method will be invoked to convert abstract
     *                             column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
     *                             in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null'
     *                             will become 'varchar(255) not null'.
     * @param string|null $newName The new column name, if any.
     * @param string|null $after   The column that this column should be placed after, if it should be moved.
     *
     * @return Command the command object itself
     */
    public function alterColumn($table, $column, $type, $newName = null, $after = null)
    {
        $sql = $this->db->getQueryBuilder()->alterColumn($table, $column, $type, $newName, $after);

        return $this->setSql($sql);
    }
}
