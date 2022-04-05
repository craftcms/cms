<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\db\Connection;
use craft\db\Migration;
use craft\db\TableSchema;

/**
 * Migration utility methods.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 4.0.0. Use [[Db]] instead.
 */
class MigrationHelper
{
    /**
     * Returns whether a foreign key exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @return string|null The foreign key name, or null if it doesn't exist
     * @since 3.0.27
     */
    public static function findForeignKey(string $tableName, array|string $columns): ?string
    {
        return Db::findForeignKey($tableName, $columns);
    }

    /**
     * Returns whether a foreign key exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @return bool
     */
    public static function doesForeignKeyExist(string $tableName, array|string $columns): bool
    {
        return Db::findForeignKey($tableName, $columns) !== null;
    }

    /**
     * Drops a foreign key if it exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param Migration|null $migration
     */
    public static function dropForeignKeyIfExists(string $tableName, array|string $columns, ?Migration $migration = null): void
    {
        if ($migration) {
            $migration->dropForeignKeyIfExists($tableName, $columns);
        } else {
            Db::dropForeignKeyIfExists($tableName, $columns);
        }
    }

    /**
     * Returns whether an index exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param Connection|null $db
     * @return bool
     */
    public static function doesIndexExist(string $tableName, array|string $columns, bool $unique = false, ?Connection $db = null): bool
    {
        return Db::findIndex($tableName, $columns, $unique, $db) !== null;
    }

    /**
     * Drops an index if it exists.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param Migration|null $migration
     */
    public static function dropIndexIfExists(string $tableName, array|string $columns, bool $unique = false, ?Migration $migration = null): void
    {
        if ($migration) {
            $migration->dropIndexIfExists($tableName, $columns, $unique);
        } else {
            Db::dropIndexIfExists($tableName, $columns, $unique);
        }
    }

    /**
     * Renames a table, while also updating its sequence, index, and FK names, as well as any other FK names pointing to the table.
     *
     * @param string $oldName
     * @param string $newName
     * @param Migration|null $migration
     */
    public static function renameTable(string $oldName, string $newName, ?Migration $migration = null): void
    {
        if ($migration) {
            $migration->renameTable($oldName, $newName);
        } else {
            Db::renameTable($oldName, $newName);
        }
    }

    /**
     * Renames a column, while also updating any index and FK names that use the column.
     *
     * @param string $tableName
     * @param string $oldName
     * @param string $newName
     * @param Migration|null $migration
     */
    public static function renameColumn(string $tableName, string $oldName, string $newName, ?Migration $migration = null): void
    {
        if ($migration) {
            $migration->renameColumn($tableName, $oldName, $newName);
        } else {
            Craft::$app->getDb()->createCommand()
                ->renameColumn($tableName, $oldName, $newName)
                ->execute();
        }
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
            /** @var TableSchema $otherTable */
            $counter = 0;

            foreach ($otherTable->foreignKeys as $fk) {
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
    public static function dropTable(string $tableName, ?Migration $migration = null): void
    {
        if ($migration !== null) {
            $migration->dropAllForeignKeysToTable($tableName);
            $migration->dropTable($tableName);
        } else {
            Db::dropAllForeignKeysToTable($tableName);
            Craft::$app->getDb()->createCommand()
                ->dropTable($tableName)
                ->execute();
        }
    }

    /**
     * Drops all the foreign keys on a table.
     *
     * @param string $tableName
     * @param Migration|null $migration
     * @return array An array of the foreign keys that were just dropped.
     */
    public static function dropAllForeignKeysOnTable(string $tableName, ?Migration $migration = null): array
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);
        $foreignKeys = [];

        foreach ($table->foreignKeys as $fk) {
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
    public static function dropAllForeignKeysToTable(string $tableName, ?Migration $migration = null): void
    {
        if ($migration) {
            $migration->dropAllForeignKeysToTable($tableName);
        } else {
            Db::dropAllForeignKeysToTable($tableName);
        }
    }

    /**
     * Drops a foreign key.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param Migration|null $migration
     */
    public static function dropForeignKey(string $tableName, array|string $columns, ?Migration $migration = null): void
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $tableName = $schema->getRawTableName($tableName);
        $foreignKeyName = Db::findForeignKey($tableName, $columns);

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
     */
    public static function dropAllIndexesOnTable(string $tableName, ?Migration $migration = null): void
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $schema = $db->getSchema();
        $allIndexes = $schema->findIndexes($tableName);

        foreach ($allIndexes as $indexName => $index) {
            self::_dropIndex($tableName, $indexName, $migration);
        }
    }

    /**
     * Drops all the unique indexes on a table.
     *
     * @param string $tableName
     * @param Migration|null $migration
     */
    public static function dropAllUniqueIndexesOnTable(string $tableName, ?Migration $migration = null): void
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $allIndexes = $db->getSchema()->findIndexes($tableName);

        foreach ($allIndexes as $indexName => $index) {
            if ($index['unique']) {
                self::_dropIndex($tableName, $indexName, $migration);
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
    public static function dropIndex(string $tableName, array|string $columns, bool $unique = false, ?Migration $migration = null): void
    {
        static::dropIndexIfExists($tableName, $columns, $unique, $migration);
    }

    /**
     * Restores an index.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param Migration|null $migration
     */
    public static function restoreIndex(string $tableName, array|string $columns, bool $unique = false, ?Migration $migration = null): void
    {
        self::_createIndex($tableName, $columns, $unique, $migration);
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
    public static function restoreForeignKey(string $tableName, array|string $columns, string $refTable, array $refColumns, string $onUpdate, string $onDelete, ?Migration $migration = null): void
    {
        self::_addForeignKey($tableName, $columns, $refTable, $refColumns, $onUpdate, $onDelete, $migration);
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
    private static function _addForeignKey(string $tableName, array|string $columns, string $refTable, array $refColumns, string $onUpdate, string $onDelete, ?Migration $migration = null): void
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $foreignKeyName = $db->getForeignKeyName();

        if ($migration !== null) {
            $migration->addForeignKey($foreignKeyName, $tableName, $columns, $refTable, $refColumns, $onDelete, $onUpdate);
        } else {
            $db->createCommand()
                ->addForeignKey($foreignKeyName, $tableName, $columns, $refTable, $refColumns, $onDelete, $onUpdate)
                ->execute();
        }
    }

    /**
     * Creates an index.
     *
     * @param string $tableName
     * @param string|string[] $columns
     * @param bool $unique
     * @param Migration|null $migration
     */
    private static function _createIndex(string $tableName, array|string $columns, bool $unique = false, ?Migration $migration = null): void
    {
        $db = $migration ? $migration->db : Craft::$app->getDb();
        $indexName = $db->getIndexName();

        if ($migration !== null) {
            $migration->createIndex($indexName, $tableName, $columns, $unique);
        } else {
            $db->createCommand()
                ->createIndex($indexName, $tableName, $columns, $unique)
                ->execute();
        }
    }

    /**
     * Drops an index by its name.
     *
     * @param string $tableName
     * @param string $indexName
     * @param Migration|null $migration
     */
    private static function _dropIndex(string $tableName, string $indexName, ?Migration $migration = null): void
    {
        if ($migration !== null) {
            $migration->dropIndex($indexName, $tableName);
        } else {
            Craft::$app->getDb()->createCommand()
                ->dropIndex($indexName, $tableName)
                ->execute();
        }
    }
}
