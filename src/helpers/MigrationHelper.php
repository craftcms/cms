<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\db\Migration;
use craft\db\Query;

/**
 * Migration utility methods.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MigrationHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether a foreign key exists.
     *
     * @param string       $tableName
     * @param string|array $columns
     *
     * @return boolean
     */
    public static function doesForeignKeyExist($tableName, $columns)
    {
        $tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $columns = ArrayHelper::toArray($columns);
        $table = Craft::$app->getDb()->getTableSchema($tableName);

        foreach ($table->foreignKeys as $num => $fk) {
            $fkColumns = [];

            foreach ($fk as $count => $value) {
                if ($count !== 0) {
                    $fkColumns[] = $count;
                }
            }

            // Could be a composite key, so make sure all required values exist!
            if (count(array_intersect($fkColumns, $columns)) === count($columns)) {
                return true;
            }
        }



        return false;
    }

    /**
     * Drops a foreign key if it exists.
     *
     * @param string       $tableName
     * @param string|array $columns
     * @param Migration    $migration
     *
     * @return void
     */
    public static function dropForeignKeyIfExists($tableName, $columns, Migration $migration = null)
    {
        if (static::doesForeignKeyExist($tableName, $columns)) {
            static::dropForeignKey($tableName, $columns, $migration);
        }
    }

    /**
     * Returns whether an index exists.
     *
     * @param string       $tableName
     * @param string|array $columns
     * @param boolean      $unique
     *
     * @return boolean
     */
    public static function doesIndexExist($tableName, $columns, $unique = false)
    {
        $columns = ArrayHelper::toArray($columns);

        $allIndexes = Craft::$app->getDb()->getSchema()->findIndexes($tableName);
        $needleIndex = Craft::$app->getDb()->getIndexName($tableName, $columns, $unique);

        if (array_key_exists($needleIndex, $allIndexes)) {
            return true;
        }

        return false;
    }

    /**
     * Drops an index if it exists.
     *
     * @param string       $tableName
     * @param string|array $columns
     * @param boolean      $unique
     * @param Migration    $migration
     *
     * @return void
     */
    public static function dropIndexIfExists($tableName, $columns, $unique = false, Migration $migration = null)
    {
        if (static::doesIndexExist($tableName, $columns, $unique)) {
            static::dropIndex($tableName, $columns, $unique, $migration);
        }
    }

    /**
     * Renames a table, while also updating its index and FK names, as well as any other FK names pointing to the table.
     *
     * @param string    $oldName
     * @param string    $newName
     * @param Migration $migration
     *
     * @return void
     */
    public static function renameTable($oldName, $newName, Migration $migration = null)
    {
        $rawOldName = Craft::$app->getDb()->getSchema()->getRawTableName($oldName);
        $rawNewName = Craft::$app->getDb()->getSchema()->getRawTableName($newName);

        // Save this for restoring extended foreign key data later.
        $oldTableSchema = Craft::$app->getDb()->getTableSchema($rawOldName);

        // Drop any foreign keys pointing to this table
        $fks = static::findForeignKeysTo($rawOldName);

        foreach ($fks as $sourceTable => $fk) {
            foreach ($fk as $num => $fkInfo) {
                // Skip if this FK is from *and* to this table
                if ($sourceTable === $rawOldName && $fkInfo[0] === $rawOldName) {
                    continue;
                }

                $columns = [];
                foreach ($fkInfo as $count => $value) {
                    if ($count !== 0) {
                        $columns[] = $count;
                    }
                }

                static::dropForeignKey($sourceTable, $columns, $migration);
            }
        }

        // Drop all the FKs and indexes on the table
        $droppedExtendedForeignKeys = $oldTableSchema->getExtendedForeignKeys();
        $droppedForeignKeys = static::dropAllForeignKeysOnTable($oldName, $migration);
        $droppedIndexes = static::dropAllIndexesOnTable($oldName, $migration);

        // Rename the table
        if ($migration !== null) {
            $migration->renameTable($oldName, $rawNewName);
        } else {
            Craft::$app->getDb()->createCommand()
                ->renameTable($rawOldName, $rawNewName)
                ->execute();
        }

        // First pass, update any source tables that might use the old table name.
        foreach ($fks as $sourceTable => $fk) {
            if ($sourceTable === $rawOldName) {
                $oldValue = $fks[$sourceTable];
                unset($fks[$sourceTable]);
                $fks[$newName] = $oldValue;
            }
        }

        // Second pass, update any ref tables that might use the old table name.
        foreach ($fks as $sourceTable => $fk) {
            foreach ($fk as $num => $row) {
                if ($row[0] === $rawOldName) {
                    $fks[$sourceTable][$num][0] = $rawNewName;
                }
            }
        }

        // Restore foreign keys pointing to this table.
        foreach ($fks as $sourceTable => $fk) {

            // Load up extended FK information for the source table.
            $sourceTableSchema = Craft::$app->getDb()->getSchema()->getTableSchema($sourceTable);

            foreach ($fk as $num => $row) {

                // Skip if this FK is from *and* to this table
                if ($sourceTable === $rawOldName && $row[0] === $rawOldName) {
                    continue;
                }

                foreach ($sourceTableSchema->foreignKeys as $index => $value) {
                    if ($value[0] === $rawOldName) {
                        $value[0] = $rawNewName;
                    }

                    if ($fk[$num] === $value) {
                        break;
                    }
                }

                $refColumns = static::_getColumnsForFK($row);

                $sourceColumns = [];
                foreach ($row as $key => $column) {
                    if ($key !== 0) {
                        $sourceColumns[] = $key;
                    }
                }

                $refTable = $row[0];

                $extendedForeignKeys = $sourceTableSchema->getExtendedForeignKeys();
                $onUpdate = $extendedForeignKeys[$index]['updateType'];
                $onDelete = $extendedForeignKeys[$index]['deleteType'];

                static::restoreForeignKey($sourceTable, $sourceColumns, $refTable, $refColumns, $onUpdate, $onDelete, $migration);
            }
        }

        // Restore this table's indexes
        foreach ($droppedIndexes as $tableName => $indexInfo) {
            foreach ($indexInfo as $indexName => $columns) {
                $unique = StringHelper::contains($indexName, '_unq_');

                if ($tableName === $rawOldName) {
                    $tableName = $rawNewName;
                }

                static::restoreIndex($tableName, $columns, $unique, $migration);
            }
        }

        // Restore this table's foreign keys
        foreach ($droppedForeignKeys as $sourceTableName => $fkInfo) {

            if ($sourceTableName === $rawOldName) {
                $sourceTableName = $rawNewName;
            }

            foreach ($fkInfo as $num => $fk) {
                $sourceColumns = [];
                $refColumns = [];
                $onUpdate = $droppedExtendedForeignKeys[$num]['updateType'];
                $onDelete = $droppedExtendedForeignKeys[$num]['deleteType'];

                foreach ($fk as $count => $value) {
                    if ($count === 0) {
                        $refTableName = $value;

                        if ($refTableName === $rawOldName) {
                            $refTableName = $rawNewName;
                        }
                    } else {
                        $sourceColumns[] = $count;
                        $refColumns[] = $value;
                    }
                }

                static::restoreForeignKey($sourceTableName, $sourceColumns, $refTableName, $refColumns, $onUpdate, $onDelete, $migration);
            }
        }

        // Refresh schema.
        Craft::$app->getDb()->getSchema()->refreshTableSchema($newName);
    }

    /**
     * Renames a column, while also updating any index and FK names that use the column.
     *
     * @param string    $tableName
     * @param string    $oldName
     * @param string    $newName
     * @param Migration $migration
     *
     * @return void
     */
    public static function renameColumn($tableName, $oldName, $newName, Migration $migration = null)
    {
        $rawTableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $table = Craft::$app->getDb()->getSchema()->getTableSchema($rawTableName);
        $allOtherTableFks = static::findForeignKeysTo($tableName);

        // Temporarily drop any FKs and indexes that include this column
        $columnFks = [];
        $columnIndexes = [];

        // Drop all the FKs because any one of them might be relying on an index we're about to drop
        foreach ($table->foreignKeys as $key => $fkInfo) {

            $columns = static::_getColumnsForFK($fkInfo);

            // Save something to restore later.
            $columnFks[] = [$fkInfo, $key];

            // Kill it.
            static::dropForeignKey($tableName, $columns, $migration);
        }

        $allIndexes = Craft::$app->getDb()->getSchema()->findIndexes($tableName);

        // Check on any indexes
        foreach ($allIndexes as $indexName => $indexColumns) {
            // Is there an index that references our old column name?
            $key = array_search($oldName, $indexColumns);

            // Found a match.
            if ($key !== false) {
                // Check if this was a unique index.
                $unique = StringHelper::contains($indexName, '_unq_');

                // Save something for later to restore.
                $columnIndexes[] = [$indexColumns, $unique];

                // Kill it.
                static::dropIndex($tableName, $indexColumns, $unique, $migration);
            }
        }

        foreach ($allOtherTableFks as $refTableName => $fkInfo) {

            // Load up extended FK information for the reference table.
            $refTable = Craft::$app->getDb()->getSchema()->getTableSchema($refTableName);

            // Figure out the reference columns.
            foreach ($fkInfo as $number => $fk) {

                foreach ($refTable->foreignKeys as $index => $value) {
                    if ($fkInfo[$number] === $value) {
                        break;
                    }
                }

                $extendedForeignKeys = $refTable->getExtendedForeignKeys();
                $allOtherTableFks[$refTableName][$number]['updateType'] = $extendedForeignKeys[$index]['updateType'];
                $allOtherTableFks[$refTableName][$number]['deleteType'] = $extendedForeignKeys[$index]['deleteType'];

                $columns = [];
                foreach ($fk as $count => $row) {
                    if ($count !== 0) {
                        $columns[] = $count;
                    }
                }

                static::dropForeignKey($refTableName, $columns, $migration);
            }
        }

        // Rename the column
        if ($migration !== null) {
            $migration->renameColumn($tableName, $oldName, $newName);
        } else {
            Craft::$app->getDb()->createCommand()
                ->renameColumn($rawTableName, $oldName, $newName)
                ->execute();
        }

        // Restore FKs linking to the column.
        foreach ($allOtherTableFks as $sourceTableName => $fkInfo) {

            // Figure out the reference columns.
            foreach ($fkInfo as $num => $fk) {
                $refColumns = [];
                $columns = [];

                foreach ($fk as $count => $row) {

                    if ($count === 0) {
                        $refTableName = $fk[$count];
                    }

                    if ($count !== 0 && $count !== 'updateType' && $count !== 'deleteType') {

                        // Save the source column
                        $columns[] = $count;

                        // Swap out the old column name with the new one.
                        if ($fk[$count] === $oldName) {
                            unset($fk[$count]);
                            $fk[$count] = $newName;
                            $count = $newName;
                        }

                        // Save the ref column.
                        $refColumns[] = $count;
                    }
                }

                $onUpdate = $fk['updateType'];
                $onDelete = $fk['deleteType'];
            }

            static::restoreForeignKey($sourceTableName, $columns, $refTableName, $refColumns, $onUpdate, $onDelete);
        }

        // Restore indexes.
        foreach ($columnIndexes as $indexData) {
            list($columns, $unique) = $indexData;

            foreach ($columns as $key => $column) {
                if ($column === $oldName) {
                    $columns[$key] = $newName;
                }
            }

            static::restoreIndex($tableName, $columns, $unique, $migration);
        }

        // Restore FK's the column was linking to.
        foreach ($columnFks as $fkData) {
            list($fk, $key) = $fkData;

            // Get the reference table.
            $refTable = $fk[0];

            $refColumns = [];
            $columns = [];

            // Figure out the reference columns.
            foreach ($fk as $count => $row) {
                if ($count !== 0) {
                    // Save the ref column.
                    $refColumns[] = $row;

                    // Swap out the old column name with the new one.
                    if ($count === $oldName) {
                        $oldValue = $fk[$count];
                        unset($fk[$count]);
                        $fk[$newName] = $oldValue;
                        $count = $newName;
                    }

                    // Save the source column.
                    $columns[] = $count;
                }
            }

            $extendedForeignKeys = $table->getExtendedForeignKeys();
            $onUpdate = $extendedForeignKeys[$key]['updateType'];
            $onDelete = $extendedForeignKeys[$key]['deleteType'];

            static::restoreForeignKey($tableName, $columns, $refTable, $refColumns, $onUpdate, $onDelete);
        }


        // Refresh the cached version of the schema.
        Craft::$app->getDb()->getSchema()->refreshTableSchema($tableName);
    }

    /**
     * Returns a list of all the foreign keys that point to a given table/column.
     *
     * @param string $tableName The table the foreign keys should point to.
     * @param string $column    The column the foreign keys should point to. Defaults to 'id'.
     *
     * @return array A list of the foreign keys pointing to that table/column.
     */
    public static function findForeignKeysTo($tableName, $column = 'id')
    {
        $tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $allTables = Craft::$app->getDb()->getSchema()->getTableSchemas();
        $fks = [];

        foreach ($allTables as $otherTable) {
            foreach ($otherTable->foreignKeys as $fk) {
                if ($fk[0] === $tableName && in_array($column, $fk) !== false) {
                    $fks[$otherTable->name][] = $fk;
                }
            }
        }

        return $fks;
    }

    /**
     * Drops a table, its own foreign keys, and any foreign keys referencing it.
     *
     * @param string    $tableName
     * @param Migration $migration
     */
    public static function dropTable($tableName, Migration $migration = null)
    {
        $rawTableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);

        static::dropAllForeignKeysOnTable($rawTableName, $migration);
        static::dropAllForeignKeysToTable($rawTableName, $migration);

        if ($migration !== null) {
            $migration->dropTable($rawTableName);
        } else {
            Craft::$app->getDb()->createCommand()
                ->dropTable($rawTableName)
                ->execute();
        }

        // Refresh schema with new dropped table.
        Craft::$app->getDb()->getSchema()->refresh();
    }

    /**
     * Drops all the foreign keys on a table.
     *
     * @param string    $tableName
     * @param Migration $migration
     *
     * @return array An array of the foreign keys that were just dropped.
     */
    public static function dropAllForeignKeysOnTable($tableName, Migration $migration = null)
    {
        $rawTableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $table = Craft::$app->getDb()->getSchema()->getTableSchema($rawTableName);
        $foreignKeys = [];

        foreach ($table->foreignKeys as $num => $fk) {
            $columns = [];

            foreach ($fk as $key => $value) {
                if ($key !== 0) {
                    $columns[] = $key;
                }
            }

            $foreignKeys[$rawTableName][] = $fk;
            static::dropForeignKey($tableName, $columns, $migration);
        }

        return $foreignKeys;
    }

    /**
     * Drops all the foreign keys that reference a table.
     *
     * @param string    $tableName
     * @param Migration $migration
     *
     * @return void
     */
    public static function dropAllForeignKeysToTable($tableName, Migration $migration = null)
    {
        $rawTableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $table = Craft::$app->getDb()->getTableSchema($rawTableName);

        foreach ($table->getColumnNames() as $columnName) {
            $fks = static::findForeignKeysTo($rawTableName, $columnName);

            foreach ($fks as $otherTable => $row) {
                foreach ($row as $columnInfo) {
                    $otherColumns = [];

                    foreach ($columnInfo as $count => $value) {
                        if ($count !== 0) {
                            $otherColumns[] = $count;
                        }
                    }
                }

                static::dropForeignKey($otherTable, $otherColumns, $migration);
            }
        }
    }

    /**
     * Drops a foreign key.
     *
     * @param           $tableName
     * @param           $columns
     * @param Migration $migration

     */
    public static function dropForeignKey($tableName, $columns, Migration $migration = null)
    {
        $tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $foreignKeyName = Craft::$app->getDb()->getForeignKeyName($tableName, $columns);

        if ($migration !== null) {
            $migration->dropForeignKey($foreignKeyName, $tableName);
        } else {
            Craft::$app->getDb()->createCommand()
                ->dropForeignKey($foreignKeyName, $tableName)
                ->execute();
        }
    }

    /**
     * Drops all the indexes on a table.
     *
     * @param string    $tableName
     * @param Migration $migration
     *
     * @return array An array of the indexes that were just dropped.
     */
    public static function dropAllIndexesOnTable($tableName, Migration $migration = null)
    {
        $rawTableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $indexes = [];
        $allIndexes = Craft::$app->getDb()->getSchema()->findIndexes($tableName);

        foreach ($allIndexes as $indexName => $indexColumns) {
            $indexes[$rawTableName][$indexName] = $indexColumns;
            $unique = StringHelper::contains($indexName, '_unq_');

            static::dropIndex($tableName, $indexColumns, $unique, $migration);
        }

        return $indexes;
    }

    /**
     * Drops all the unique indexes on a table.
     *
     * @param string    $tableName
     * @param Migration $migration
     *
     * @return void
     */
    public static function dropAllUniqueIndexesOnTable($tableName, Migration $migration = null)
    {
        $allIndexes = Craft::$app->getDb()->getSchema()->findIndexes($tableName);

        foreach ($allIndexes as $indexName => $indexColumns) {
            $unique = StringHelper::contains($indexName, '_unq_');

            if ($unique) {
                static::dropIndex($tableName, $indexColumns, $unique, $migration);
            }
        }
    }

    /**
     * Drops an index.
     *
     * @param           $tableName
     * @param           $columns
     * @param bool      $unique
     * @param Migration $migration
     */
    public static function dropIndex($tableName, $columns, $unique = false, Migration $migration = null)
    {
        $rawTableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
        $indexName = Craft::$app->getDb()->getIndexName($tableName, $columns, $unique);

        if ($migration !== null) {
            $migration->dropIndex($indexName, $rawTableName);
        } else {
            Craft::$app->getDb()->createCommand()
                ->dropIndex($indexName, $rawTableName)
                ->execute();
        }
    }

    /**
     * Restores an index.
     *
     * @param string    $tableName
     * @param array     $columns
     * @param bool      $unique
     * @param Migration $migration
     */
    public static function restoreIndex($tableName, $columns, $unique = false, Migration $migration = null)
    {
        $db = Craft::$app->getDb();
        $rawTableName = $db->getSchema()->getRawTableName($tableName);
        $indexName = $db->getIndexName($rawTableName, $columns, $unique);

        if ($migration !== null) {
            $migration->createIndex($indexName, $rawTableName, $columns, $unique);
        } else {
            $db->createCommand()
                ->createIndex($indexName, $rawTableName, $columns, $unique)
                ->execute();
        }
    }

    /**
     * Restores a foreign key.
     *
     * @param string    $tableName
     * @param array     $columns
     * @param string    $refTable
     * @param array     $refColumns
     * @param string    $onUpdate
     * @param string    $onDelete
     * @param Migration $migration
     */
    public static function restoreForeignKey($tableName, $columns, $refTable, $refColumns, $onUpdate, $onDelete, Migration $migration = null)
    {
        $db = Craft::$app->getDb();
        $rawTableName = $db->getSchema()->getRawTableName($tableName);
        $foreignKeyName = $db->getForeignKeyName($rawTableName, $columns);
        $columnsStr = implode(',', $columns);
        $refColumnsStr = implode(',', $refColumns);

        if ($migration !== null) {
            $migration->addForeignKey($foreignKeyName, $rawTableName, $columnsStr, $refTable, $refColumnsStr, $onDelete, $onUpdate);
        } else {
            $db->createCommand()
                ->addForeignKey($foreignKeyName, $rawTableName, $columnsStr, $refTable, $refColumnsStr, $onDelete, $onUpdate)
                ->execute();
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $foreignKey
     *
     * @return array
     */
    private static function _getColumnsForFK($foreignKey)
    {
        $columns = [];

        foreach ($foreignKey as $key => $fk) {
            if ($key !== 0 && $key !== 'updateType' && $key !== 'deleteType') {
                $columns[] = $fk;
            }
        }

        return $columns;
    }
}
