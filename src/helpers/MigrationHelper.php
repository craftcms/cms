<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\db\Migration;

/**
 * Migration utility methods.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MigrationHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether a foreign key exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @return string|null The foreign key name, or null if it doesn't exist
     */
    public static function findForeignKey(string $tableName, $columns)
    {
        $db = Craft::$app->getDb();
        $schema = $db->getSchema();
        $tableName = $schema->getRawTableName($tableName);
        $schema->refreshTableSchema($tableName);
        if (is_string($columns)) {
            $columns = StringHelper::split($columns);
        }
        $table = $db->getTableSchema($tableName);

        foreach ($table->foreignKeys as $name => $fk) {
            $fkColumns = [];

            foreach ($fk as $count => $value) {
                if ($count !== 0) {
                    $fkColumns[] = $count;
                }
            }

            // Could be a composite key, so make sure all required values exist!
            if (count(array_intersect($fkColumns, $columns)) === count($columns)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Returns whether a foreign key exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @return bool
     */
    public static function doesForeignKeyExist(string $tableName, $columns): bool
    {
        return static::findForeignKey($tableName, $columns) !== null;
    }

    /**
     * Drops a foreign key if it exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param Migration|null $migration
     */
    public static function dropForeignKeyIfExists(string $tableName, $columns, Migration $migration = null)
    {
        if (static::doesForeignKeyExist($tableName, $columns)) {
            static::dropForeignKey($tableName, $columns, $migration);
        }
    }

    /**
     * Returns whether an index exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param bool $foreignKey
     * @return bool
     */
    public static function doesIndexExist(string $tableName, $columns, bool $unique = false, bool $foreignKey = false): bool
    {
        $db = Craft::$app->getDb();
        $schema = $db->getSchema();
        if (is_string($columns)) {
            $columns = StringHelper::split($columns);
        }

        $allIndexes = $schema->findIndexes($tableName);
        $needleIndex = $db->getIndexName($tableName, $columns, $unique, $foreignKey);

        if (array_key_exists($needleIndex, $allIndexes)) {
            return true;
        }

        return false;
    }

    /**
     * Drops an index if it exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param Migration|null $migration
     */
    public static function dropIndexIfExists(string $tableName, $columns, bool $unique = false, Migration $migration = null)
    {
        if (static::doesIndexExist($tableName, $columns, $unique)) {
            static::dropIndex($tableName, $columns, $unique, $migration);
        }
    }

    /**
     * Renames a table, while also updating its sequence, index, and FK names, as well as any other FK names pointing to the table.
     *
     * @param string $oldName
     * @param string $newName
     * @param Migration|null $migration
     */
    public static function renameTable(string $oldName, string $newName, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();

        $rawOldName = $schema->getRawTableName($oldName);
        $rawNewName = $schema->getRawTableName($newName);

        // Save this for restoring extended foreign key data later.
        $oldTableSchema = $db->getTableSchema($rawOldName);

        // Drop any foreign keys pointing to this table
        $fks = static::findForeignKeysTo($rawOldName);

        foreach ($fks as $sourceTable => $fk) {
            foreach ($fk as $num => $fkInfo) {
                // Skip if this FK is from *and* to this table
                if ($sourceTable === $rawOldName && $fkInfo[0] === $rawOldName) {
                    continue;
                }

                $columns = self::_getColumnsForFK($fkInfo, true);

                static::dropForeignKeyIfExists($sourceTable, $columns, $migration);
            }
        }

        // Drop all the FKs and indexes on the table
        $droppedExtendedForeignKeys = $oldTableSchema->getExtendedForeignKeys();
        $droppedForeignKeys = static::dropAllForeignKeysOnTable($oldName, $migration);
        $droppedIndexes = static::dropAllIndexesOnTable($oldName, $migration);

        // Rename the table
        if ($migration !== null) {
            $migration->renameTable($rawOldName, $rawNewName);
        } else {
            $db->createCommand()
                ->renameTable($rawOldName, $rawNewName)
                ->execute();
        }

        if ($db->getIsPgsql()) {
            // Rename the sequence (required for PostgreSQL - see https://www.postgresql.org/message-id/200308211224.06775.jgardner%40jonathangardner.net)
            $transaction = $db->beginTransaction();
            try {
                if ($migration !== null) {
                    $migration->renameSequence($rawOldName . '_id_seq', $rawNewName . '_id_seq');
                } else {
                    $db->createCommand()
                        ->renameSequence($rawOldName . '_id_seq', $rawNewName . '_id_seq')
                        ->execute();
                }
                $transaction->commit();
            } catch (\Throwable $e) {
                // Silently fail. The sequence probably doesn't exist
                $transaction->rollBack();
            }
        }

        // First pass, update any source tables that might use the old table name.
        foreach ($fks as $sourceTable => $fk) {
            if ($sourceTable === $rawOldName) {
                $oldValue = $fks[$sourceTable];
                unset($fks[$sourceTable]);
                $fks[$rawNewName] = $oldValue;
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
            foreach ($fk as $num => $row) {

                // Skip if this FK is from *and* to this table
                if ($sourceTable === $rawNewName && $row[0] === $rawNewName) {
                    continue;
                }

                $refColumns = self::_getColumnsForFK($row);
                $sourceColumns = self::_getColumnsForFK($row, true);

                $refTable = $row[0];

                $onUpdate = $row['updateType'];
                $onDelete = $row['deleteType'];

                static::restoreForeignKey($sourceTable, $sourceColumns, $refTable, $refColumns, $onUpdate, $onDelete, $migration);
            }
        }

        // Restore this table's indexes
        foreach ($droppedIndexes as $tableName => $indexInfo) {
            foreach ($indexInfo as $indexName => $columns) {
                // If it's a foreign key index, restoring the FK will restore it.
                if (!StringHelper::endsWith($indexName, '_fk')) {
                    $unique = StringHelper::contains($indexName, '_unq_');

                    if ($tableName === $rawOldName) {
                        $tableName = $rawNewName;
                    }

                    static::restoreIndex($tableName, $columns, $unique, $migration);
                }
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

                $refTableName = '';

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
        $schema->refreshTableSchema($newName);
    }

    /**
     * Renames a column, while also updating any index and FK names that use the column.
     *
     * @param string $tableName
     * @param string $oldName
     * @param string $newName
     * @param Migration|null $migration
     */
    public static function renameColumn(string $tableName, string $oldName, string $newName, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);
        $allOtherTableFks = static::findForeignKeysTo($tableName);

        // Temporarily drop any FKs and indexes that include this column
        $columnFks = [];

        // Drop all the FKs because any one of them might be relying on an index we're about to drop
        foreach ($table->foreignKeys as $key => $fkInfo) {

            $columns = self::_getColumnsForFK($fkInfo, true);

            // Save something to restore later.
            $columnFks[] = [$fkInfo, $key];

            // Kill it.
            static::dropForeignKeyIfExists($tableName, $columns, $migration);
        }

        $allIndexes = $schema->findIndexes($tableName);

        // Check on any indexes
        foreach ($allIndexes as $indexName => $indexColumns) {
            // Check if this was a unique index.
            $unique = StringHelper::contains($indexName, '_unq_');

            // Kill it.
            static::dropIndex($tableName, $indexColumns, $unique, $migration);
        }

        foreach ($allOtherTableFks as $refTableName => $fkInfo) {

            // Figure out the reference columns.
            foreach ($fkInfo as $number => $fk) {
                $columns = self::_getColumnsForFK($fk, true);

                static::dropForeignKeyIfExists($refTableName, $columns, $migration);
            }
        }

        // Rename the column
        if ($migration !== null) {
            $migration->renameColumn($tableName, $oldName, $newName);
        } else {
            $db->createCommand()
                ->renameColumn($rawTableName, $oldName, $newName)
                ->execute();
        }

        // Restore FKs linking to the column.
        foreach ($allOtherTableFks as $sourceTableName => $fkInfo) {

            $columns = [];
            $refColumns = [];
            $refTableName = '';
            $onUpdate = '';
            $onDelete = '';

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
                        }

                        // Save the ref column.
                        $refColumns[] = $row;
                    }
                }

                $onUpdate = $fk['updateType'];
                $onDelete = $fk['deleteType'];
            }

            static::restoreForeignKey($sourceTableName, $columns, $refTableName, $refColumns, $onUpdate, $onDelete, $migration);
        }

        // Restore indexes.
        foreach ($allIndexes as $indexName => $indexColumns) {
            $columns = [];

            foreach ($indexColumns as $key => $column) {
                if ($column === $oldName) {
                    $columns[$key] = $newName;
                } else {
                    $columns[$key] = $column;
                }
            }

            // Check if this was a unique index.
            $unique = StringHelper::contains($indexName, '_unq_');

            // Could have already been restored from a FK restoration
            if (!static::doesIndexExist($tableName, $columns, $unique, true)) {
                static::restoreIndex($tableName, $columns, $unique, $migration);
            }
        }

        // Restore FK's the column was linking to.
        foreach ($columnFks as $key => $fkInfo) {
            $fk = $fkInfo[0];

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

            // If this is a self referencing key, it might already exist.
            if (!static::doesForeignKeyExist($tableName, $columns)) {
                static::restoreForeignKey($tableName, $columns, $refTable, $refColumns, $onUpdate, $onDelete, $migration);
            }
        }


        // Refresh the cached version of the schema.
        $schema->refreshTableSchema($tableName);
    }

    /**
     * Returns a list of all the foreign keys that point to a given table/column.
     *
     * @param string $tableName The table the foreign keys should point to.
     * @param string $column The column the foreign keys should point to. Defaults to 'id'.
     * @return array A list of the foreign keys pointing to that table/column.
     */
    public static function findForeignKeysTo(string $tableName, string $column = 'id'): array
    {
        $schema = Craft::$app->getDb()->getSchema();
        $tableName = $schema->getRawTableName($tableName);
        $allTables = $schema->getTableSchemas();
        $fks = [];

        foreach ($allTables as $otherTable) {
            $counter = 0;

            foreach ($otherTable->foreignKeys as $fkName => $fk) {
                if ($fk[0] === $tableName && in_array($column, $fk, true) !== false) {
                    $fk['updateType'] = $otherTable->getExtendedForeignKeys()[$counter]['updateType'];
                    $fk['deleteType'] = $otherTable->getExtendedForeignKeys()[$counter]['deleteType'];
                    $fks[$otherTable->name][] = $fk;
                }

                $counter++;
            }
        }

        return $fks;
    }

    /**
     * Drops a table, its own foreign keys, and any foreign keys referencing it.
     *
     * @param string $tableName
     * @param Migration|null $migration
     */
    public static function dropTable(string $tableName, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $rawTableName = $schema->getRawTableName($tableName);

        static::dropAllForeignKeysOnTable($rawTableName, $migration);
        static::dropAllForeignKeysToTable($rawTableName, $migration);

        if ($migration !== null) {
            $migration->dropTable($rawTableName);
        } else {
            $db->createCommand()
                ->dropTable($rawTableName)
                ->execute();
        }

        // Refresh schema with new dropped table.
        $schema->refresh();
    }

    /**
     * Drops all the foreign keys on a table.
     *
     * @param string $tableName
     * @param Migration|null $migration
     * @return array An array of the foreign keys that were just dropped.
     */
    public static function dropAllForeignKeysOnTable(string $tableName, Migration $migration = null): array
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);
        $foreignKeys = [];

        foreach ($table->foreignKeys as $num => $fk) {
            $columns = [];

            foreach ($fk as $key => $value) {
                if ($key !== 0) {
                    $columns[] = $key;
                }
            }

            $foreignKeys[$rawTableName][] = $fk;
            static::dropForeignKeyIfExists($tableName, $columns, $migration);
        }

        return $foreignKeys;
    }

    /**
     * Drops all the foreign keys that reference a table.
     *
     * @param string $tableName
     * @param Migration|null $migration
     */
    public static function dropAllForeignKeysToTable(string $tableName, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $rawTableName = $schema->getRawTableName($tableName);
        $table = $db->getTableSchema($rawTableName);

        foreach ($table->getColumnNames() as $columnName) {
            $fks = static::findForeignKeysTo($rawTableName, $columnName);

            foreach ($fks as $otherTable => $row) {
                foreach ($row as $fk) {
                    $otherColumns = static::_getColumnsForFK($fk, true);
                    static::dropForeignKeyIfExists($otherTable, $otherColumns, $migration);
                }
            }
        }
    }

    /**
     * Drops a foreign key.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param Migration|null $migration
     */
    public static function dropForeignKey(string $tableName, $columns, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $tableName = $schema->getRawTableName($tableName);
        $foreignKeyName = static::findForeignKey($tableName, $columns);

        if ($migration !== null) {
            $migration->dropForeignKey($foreignKeyName, $tableName);
        } else {
            $db->createCommand()
                ->dropForeignKey($foreignKeyName, $tableName)
                ->execute();
        }
    }

    /**
     * Drops all the indexes on a table.
     *
     * @param string $tableName
     * @param Migration|null $migration
     * @return array An array of the indexes that were just dropped.
     */
    public static function dropAllIndexesOnTable(string $tableName, Migration $migration = null): array
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $rawTableName = $schema->getRawTableName($tableName);
        $indexes = [];
        $allIndexes = $schema->findIndexes($tableName);

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
     * @param string $tableName
     * @param Migration|null $migration
     */
    public static function dropAllUniqueIndexesOnTable(string $tableName, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $allIndexes = $db->getSchema()->findIndexes($tableName);

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
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param Migration|null $migration
     */
    public static function dropIndex(string $tableName, $columns, bool $unique = false, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $rawTableName = $db->getSchema()->getRawTableName($tableName);

        if (self::doesIndexExist($tableName, $columns, $unique)) {
            $indexName = $db->getIndexName($tableName, $columns, $unique);
        } else {
            // Maybe it's a FK index?
            $indexName = $db->getIndexName($tableName, $columns, $unique, true);
        }

        if ($migration !== null) {
            $migration->dropIndex($indexName, $rawTableName);
        } else {
            $db->createCommand()
                ->dropIndex($indexName, $rawTableName)
                ->execute();
        }
    }

    /**
     * Restores an index.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param Migration|null $migration
     */
    public static function restoreIndex(string $tableName, $columns, bool $unique = false, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
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
     * @param string $tableName
     * @param string|string[] $columns
     * @param string $refTable
     * @param array $refColumns
     * @param string $onUpdate
     * @param string $onDelete
     * @param Migration|null $migration
     */
    public static function restoreForeignKey(string $tableName, $columns, string $refTable, $refColumns, string $onUpdate, string $onDelete, Migration $migration = null)
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $rawTableName = $db->getSchema()->getRawTableName($tableName);
        $foreignKeyName = $db->getForeignKeyName($rawTableName, $columns);

        if ($migration !== null) {
            $migration->addForeignKey($foreignKeyName, $rawTableName, $columns, $refTable, $refColumns, $onDelete, $onUpdate);
        } else {
            $db->createCommand()
                ->addForeignKey($foreignKeyName, $rawTableName, $columns, $refTable, $refColumns, $onDelete, $onUpdate)
                ->execute();
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param array $foreignKey
     * @param bool $useKey
     * @return array
     */
    private static function _getColumnsForFK(array $foreignKey, bool $useKey = false): array
    {
        $columns = [];

        foreach ($foreignKey as $key => $fk) {
            if ($key !== 0 && $key !== 'updateType' && $key !== 'deleteType') {
                $columns[] = $useKey ? $key : $fk;
            }
        }

        return $columns;
    }
}
