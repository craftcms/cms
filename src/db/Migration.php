<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\helpers\Db;
use Throwable;
use yii\db\ColumnSchemaBuilder;

/**
 * @inheritdoc
 * @property Connection $db the DB connection that this command is associated with
 * @method Connection getDb() returns the connection the DB connection that this command is associated with
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Migration extends \yii\db\Migration
{
    /**
     * @event \yii\base\Event The event that is triggered after the migration is executed
     * @since 3.0.6
     */
    public const EVENT_AFTER_UP = 'afterUp';

    /**
     * @event \yii\base\Event The event that is triggered after the migration is reverted
     * @since 3.0.6
     */
    public const EVENT_AFTER_DOWN = 'afterDown';

    // Execution Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @param bool $throwExceptions Whether exceptions should be thrown
     * @return bool Whether the operation was successful
     * @throws Throwable
     */
    public function up(bool $throwExceptions = false): bool
    {
        // Copied from \yii\db\Migration::up(), but with added $e param
        $transaction = $this->db->beginTransaction();
        try {
            if ($this->safeUp() === false) {
                $transaction->rollBack();
                return false;
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $this->_printException($e);
            $transaction->rollBack();
            if ($throwExceptions) {
                throw $e;
            }
            return false;
        }

        // Fire an 'afterUp' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UP)) {
            $this->trigger(self::EVENT_AFTER_UP);
        }

        return true;
    }

    /**
     * @inheritdoc
     * @param bool $throwExceptions Whether exceptions should be thrown
     * @return bool Whether the operation was successful
     * @throws Throwable
     */
    public function down(bool $throwExceptions = false): bool
    {
        // Copied from \yii\db\Migration::down(), but with added $e param
        $transaction = $this->db->beginTransaction();
        try {
            if ($this->safeDown() === false) {
                $transaction->rollBack();
                return false;
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $this->_printException($e);
            $transaction->rollBack();
            if ($throwExceptions) {
                throw $e;
            }
            return false;
        }

        // Fire an 'afterDown' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DOWN)) {
            $this->trigger(self::EVENT_AFTER_DOWN);
        }

        return true;
    }

    // Schema Builder Methods
    // -------------------------------------------------------------------------

    /**
     * Creates a tinytext column for MySQL, or text column for others.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function tinyText(): ColumnSchemaBuilder
    {
        if (Db::isTypeSupported('tinytext', $this->db)) {
            return $this->db->getSchema()->createColumnSchemaBuilder('tinytext');
        }

        return $this->text();
    }

    /**
     * Creates a mediumtext column for MySQL, or text column for others.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function mediumText(): ColumnSchemaBuilder
    {
        if (Db::isTypeSupported('mediumtext', $this->db)) {
            return $this->db->getSchema()->createColumnSchemaBuilder('mediumtext');
        }

        return $this->text();
    }

    /**
     * Creates a longtext column for MySQL, or text column for others.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function longText(): ColumnSchemaBuilder
    {
        if (Db::isTypeSupported('longtext', $this->db)) {
            return $this->db->getSchema()->createColumnSchemaBuilder('longtext');
        }

        return $this->text();
    }

    /**
     * Creates an enum column for MySQL and PostgreSQL, or a string column with a check constraint for others.
     *
     * @param string $columnName The column name
     * @param string[] $values The allowed column values
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function enum(string $columnName, array $values): ColumnSchemaBuilder
    {
        if (Db::isTypeSupported('enum', $this->db)) {
            return $this->db->getSchema()->createColumnSchemaBuilder('enum', $values);
        }

        $check = "[[$columnName]] in (";
        foreach ($values as $i => $value) {
            if ($i != 0) {
                $check .= ',';
            }
            $check .= $this->db->quoteValue($value);
        }
        $check .= ')';

        return $this->string()->check($check);
    }

    /**
     * Shortcut for creating a uid column
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function uid(): ColumnSchemaBuilder
    {
        return $this->char(36)->notNull()->defaultValue('0');
    }

    // CRUD Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     *
     * If the table contains `dateCreated`, `dateUpdated`, and/or `uid` columns, those values will be included
     * automatically, if not already set.
     */
    public function insert($table, $columns): void
    {
        parent::insert($table, $columns);
    }

    /**
     * @inheritdoc
     *
     * If the table contains `dateCreated`, `dateUpdated`, and/or `uid` columns, those values will be included
     * automatically, if not already set.
     */
    public function batchInsert($table, $columns, $rows): void
    {
        parent::batchInsert($table, $columns, $rows);
    }

    /**
     * @inheritdoc
     *
     * If the table contains `dateCreated`, `dateUpdated`, and/or `uid` columns, those values will be included
     * for new rows automatically, if not already set.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or instance
     * of [[Query]] to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params the parameters to be bound to the command.
     * @param bool $updateTimestamp Whether the `dateUpdated` column should be updated for existing rows, if the table has one.
     * @since 2.0.14
     */
    public function upsert($table, $insertColumns, $updateColumns = true, $params = [], bool $updateTimestamp = true): void
    {
        $time = $this->beginCommand("upsert into $table");
        $this->db->createCommand()->upsert($table, $insertColumns, $updateColumns, $params, $updateTimestamp)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes an `UPDATE` SQL statement.
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * @param string $table The table to be updated.
     * @param array $columns The column data (name => value) to be updated.
     * @param string|array $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @param bool $updateTimestamp Whether the `dateUpdated` column should be updated, if the table has one.
     */
    public function update($table, $columns, $condition = '', $params = [], bool $updateTimestamp = true): void
    {
        $time = $this->beginCommand("update in $table");
        $this->db->createCommand()
            ->update($table, $columns, $condition, $params, $updateTimestamp)
            ->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a DELETE SQL statement that will only delete duplicate rows from a table.
     *
     * @param string $table The table where the data will be deleted from
     * @param string[] $columns The column names that contain duplicate data
     * @param string $pk The primary key column name
     * @since 3.5.2
     */
    public function deleteDuplicates(string $table, array $columns, string $pk = 'id'): void
    {
        $time = $this->beginCommand("delete duplicates from $table");
        $this->db->createCommand()->deleteDuplicates($table, $columns, $pk)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a SQL statement for replacing some text with other text in a given table column.
     *
     * @param string $table The table to be updated.
     * @param string $column The column to be searched.
     * @param string $find The text to be searched for.
     * @param string $replace The replacement text.
     * @param array|string $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     */
    public function replace(string $table, string $column, string $find, string $replace, array|string $condition = '', array $params = []): void
    {
        $time = $this->beginCommand("replace \"$find\" with \"$replace\" in $table.$column");
        $this->db->createCommand()
            ->replace($table, $column, $find, $replace, $condition, $params)
            ->execute();
        $this->endCommand($time);
    }

    // Schema Manipulation Methods
    // -------------------------------------------------------------------------

    /**
     * Creates and executes a SQL statement for renaming a DB table to `*_old`, if it exists.
     *
     * @param string $table The table to be renamed. The name will be properly quoted by the method.
     * @since 4.0.0
     */
    public function archiveTableIfExists(string $table): void
    {
        if (!$this->db->tableExists($table)) {
            return;
        }

        $schema = $this->db->getSchema();
        $schema->maxObjectNameLength = 10;
        $table = $schema->getRawTableName($table);
        $tableLength = strlen($table);
        $i = 0;

        do {
            $suffix = sprintf('_old%s', $i !== 0 ? "$i" : '');
            $newNameLength = $tableLength + strlen($suffix);
            $i++;

            if ($newNameLength <= $schema->maxObjectNameLength) {
                $newName = $table . $suffix;
            } else {
                $overage = $newNameLength - $schema->maxObjectNameLength;
                $newName = substr($table, 0, $tableLength - $overage) . $suffix;
            }
        } while ($this->db->tableExists($newName));

        $this->renameTable($table, $newName);
    }

    /**
     * Creates and executes a SQL statement for dropping a DB table, if it exists.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     */
    public function dropTableIfExists(string $table): void
    {
        $time = $this->beginCommand("dropping $table if it exists");
        $this->db->createCommand()
            ->dropTableIfExists($table)
            ->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a SQL statement for dropping an index if it exists.
     *
     * @param string $table The table that the index was created for. The table name will be properly quoted by the method.
     * @param string|string[] $columns The column(s) that are included in the index. If there are multiple
     * columns, separate them by commas or use an array.
     * @param bool $unique Whether the index has a UNIQUE constraint.
     * @since 3.7.32
     */
    public function dropIndexIfExists(string $table, array|string $columns, bool $unique = false): void
    {
        $time = $this->beginCommand("dropping index on $table if it exists");
        Db::dropIndexIfExists($table, $columns, $unique, $this->db);
        $this->endCommand($time);
    }

    /**
     * Creates and executes a SQL statement for dropping a foreign key if it exists.
     *
     * @param string $table The table that the foreign key was created for. The table name will be properly quoted by the method.
     * @param string|string[] $columns The column(s) that are included in the foreign key. If there are multiple
     * columns, separate them by commas or use an array.
     * @since 4.0.0
     */
    public function dropForeignKeyIfExists(string $table, array|string $columns): void
    {
        $time = $this->beginCommand("dropping foreign key on $table if it exists");
        Db::dropForeignKeyIfExists($table, $columns, $this->db);
        $this->endCommand($time);
    }

    /**
     * Creates and executes a SQL statement for dropping all foreign keys to a table.
     *
     * @param string $table The table that the foreign keys should reference.
     * @since 4.0.0
     */
    public function dropAllForeignKeysToTable(string $table): void
    {
        $time = $this->beginCommand("dropping all foreign keys to $table");
        Db::dropAllForeignKeysToTable($table, $this->db);
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for renaming a DB table and its corresponding sequence (if PostgreSQL).
     *
     * @since 4.0.0
     */
    public function renameTable($table, $newName)
    {
        $time = $this->beginCommand("rename table $table to $newName");
        Db::renameTable($table, $newName);
        $this->endCommand($time);
    }

    /**
     * Creates and executes a SQL statement for renaming a DB sequence.
     *
     * @param string $oldName the sequence to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new sequence name. The name will be properly quoted by the method.
     */
    public function renameSequence(string $oldName, string $newName): void
    {
        $time = $this->beginCommand("rename sequence $oldName to $newName");
        $this->db->createCommand()
            ->renameSequence($oldName, $newName)
            ->execute();
        $this->endCommand($time);
    }

    /**
     * @inheritdoc
     * @param string|null $name the name of the primary key constraint. If null, a name will be automatically generated.
     * @param string $table the table that the primary key constraint will be added to.
     * @param string|array $columns comma separated string or array of columns that the primary key will consist of.
     */
    public function addPrimaryKey($name, $table, $columns): void
    {
        parent::addPrimaryKey($name ?? $this->db->getPrimaryKeyName(), $table, $columns);
    }

    /**
     * @inheritdoc
     * @param string|null $name the name of the foreign key constraint. If null, a name will be automatically generated.
     * @param string $table the table that the foreign key constraint will be added to.
     * @param string|array $columns the name of the column to that the constraint will be added on. If there are multiple columns, separate them with commas or use an array.
     * @param string $refTable the table that the foreign key references to.
     * @param string|array $refColumns the name of the column that the foreign key references to. If there are multiple columns, separate them with commas or use an array.
     * @param string $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @param string $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null): void
    {
        parent::addForeignKey($name ?? $this->db->getForeignKeyName(), $table, $columns, $refTable, $refColumns, $delete, $update);
    }

    /**
     * @inheritdoc
     * @param string|null $name the name of the index. The name will be properly quoted by the method. If null, a name will be automatically generated.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param string|array $columns the column(s) that should be included in the index. If there are multiple columns, please separate them
     * by commas or use an array. Each column name will be properly quoted by the method. Quoting will be skipped for column names that
     * include a left parenthesis "(".
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     */
    public function createIndex($name, $table, $columns, $unique = false): void
    {
        parent::createIndex($name ?? $this->db->getIndexName(), $table, $columns, $unique);
    }

    /**
     * Creates a new index if a similar one doesn’t already exist.
     *
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param array|string $columns the column(s) that should be included in the index. If there are multiple columns, please separate them
     * by commas or use an array.
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     * @since 3.7.32
     */
    public function createIndexIfMissing(string $table, array|string $columns, bool $unique = false): void
    {
        if (Db::findIndex($table, $columns, $unique, $this->db) === null) {
            $this->createIndex(null, $table, $columns, $unique);
        }
    }

    /**
     * Creates and executes a SQL statement for soft-deleting a row.
     *
     * @param string $table The table to be updated.
     * @param array|string $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @since 3.1.0
     */
    public function softDelete(string $table, array|string $condition = '', array $params = []): void
    {
        $time = $this->beginCommand("soft delete from $table");
        $this->db->createCommand()
            ->softDelete($table, $condition, $params)
            ->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a SQL statement for restoring a soft-deleted row.
     *
     * @param string $table The table to be updated.
     * @param array|string $condition The condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     * @since 3.1.0
     */
    public function restore(string $table, array|string $condition = '', array $params = []): void
    {
        $time = $this->beginCommand("restore from $table");
        $this->db->createCommand()
            ->restore($table, $condition, $params)
            ->execute();
        $this->endCommand($time);
    }

    /**
     * @param Throwable $e
     */
    private function _printException(Throwable $e): void
    {
        // Copied from \yii\db\Migration::printException(), only because it’s private
        echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
        echo $e->getTraceAsString() . "\n";
    }
}
