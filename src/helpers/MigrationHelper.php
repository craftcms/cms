<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\db\Query;
use craft\app\enums\ColumnType;

/**
 * Migration utility methods.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MigrationHelper
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private static $_tables;

	/**
	 * @var array
	 */
	private static $_idColumnType = ['column' => ColumnType::Int, 'required' => true];

	/**
	 * @var string
	 */
	private static $_fkRefActions = 'RESTRICT|CASCADE|NO ACTION|SET DEFAULT|SET NULL';

	// Public Methods
	// =========================================================================

	/**
	 * Refreshes our record of everything.
	 *
	 * @return null
	 */
	public static function refresh()
	{
		static::$_tables = null;
		Craft::$app->getDb()->getSchema()->refresh();
	}

	/**
	 * Drops a foreign key if it exists.
	 *
	 * @param string $tableName
	 * @param array  $columns
	 *
	 * @return null
	 */
	public static function dropForeignKeyIfExists($tableName, $columns)
	{
		$tableName = Craft::$app->db->getSchema()->getRawTableName($tableName);
		$table = static::getTable($tableName);

		foreach ($table->fks as $i => $fk)
		{
			if ($columns == $fk->columns)
			{
				static::dropForeignKey($fk);
				unset($table->fks[$i]);
				break;
			}
		}
	}

	/**
	 * Drops an index if it exists.
	 *
	 * @param string $tableName
	 * @param array  $columns
	 * @param bool   $unique
	 *
	 * @return false
	 */
	public static function dropIndexIfExists($tableName, $columns, $unique = false)
	{
		$tableName = Craft::$app->db->getSchema()->getRawTableName($tableName);
		$table = static::getTable($tableName);

		foreach ($table->indexes as $i => $index)
		{
			if ($columns == $index->columns && $unique == $index->unique)
			{
				static::dropIndex($index);
				unset($table->indexes[$i]);
				break;
			}
		}
	}

	/**
	 * Renames a table, while also updating its index and FK names, as well as any other FK names pointing to the table.
	 *
	 * @param string $oldName
	 * @param string $newName
	 *
	 * @return false
	 */
	public static function renameTable($oldName, $newName)
	{
		$oldName = Craft::$app->db->getSchema()->getRawTableName($oldName);
		$newName = Craft::$app->db->getSchema()->getRawTableName($newName);

		// Drop any foreign keys pointing to this table
		$fks = static::findForeignKeysTo($oldName);

		foreach ($fks as $fk)
		{
			static::dropForeignKey($fk->fk);
		}

		// Drop all the FKs and indexes on the table
		$table = static::getTable($oldName);
		static::dropAllForeignKeysOnTable($table);
		static::dropAllIndexesOnTable($table);

		// Rename the table
		Craft::$app->getDb()->createCommand()->renameTable($oldName, $newName)->execute();

		// Update our internal records
		static::$_tables[$newName] = $table;
		static::$_tables[$newName]->name = $newName;
		unset(static::$_tables[$oldName]);

		static::restoreAllIndexesOnTable($table);
		static::restoreAllForeignKeysOnTable($table);

		foreach ($fks as $fk)
		{
			$fk->fk->refTable = $newName;
			static::restoreForeignKey($fk->fk);
		}
	}

	/**
	 * Renames a column, while also updating any index and FK names that use the column.
	 *
	 * @param string $tableName
	 * @param string $oldName
	 * @param string $newName
	 *
	 * @return null
	 */
	public static function renameColumn($tableName, $oldName, $newName)
	{
		$tableName = Craft::$app->db->getSchema()->getRawTableName($tableName);
		$table = static::getTable($tableName);
		$allOtherTableFks = static::findForeignKeysTo($tableName);

		// Temporarily drop any FKs and indexes that include this column
		$columnFks = [];
		$columnIndexes = [];
		$otherTableFks = [];

		// Drop all the FKs because any one of them might be relying on an index we're about to drop
		foreach ($table->fks as $fk)
		{
			$key = array_search($oldName, $fk->columns);
			$columnFks[] = [$fk, $key];
			static::dropForeignKey($fk);
		}

		foreach ($table->indexes as $index)
		{
			$key = array_search($oldName, $index->columns);
			if ($key !== false)
			{
				$columnIndexes[] = [$index, $key];
				static::dropIndex($index);
			}
		}

		foreach ($allOtherTableFks as $fkData)
		{
			$key = array_search($oldName, $fkData->fk->refColumns);
			if ($key !== false)
			{
				$otherTableFks[] = [$fkData->fk, $key];
				static::dropForeignKey($fkData->fk);
			}
		}

		// Rename the column
		Craft::$app->getDb()->createCommand()->renameColumn($tableName, $oldName, $newName)->execute();

		// Update the table records
		$table->columns[$newName] = $table->columns[$oldName];
		$table->columns[$newName]->name = $newName;
		unset($table->columns[$oldName]);

		// Restore the FKs and indexes, and update our records
		foreach ($otherTableFks as $fkData)
		{
			list($fk, $key) = $fkData;
			$fk->refColumns[$key] = $newName;
			static::restoreForeignKey($fk);
		}

		foreach ($columnIndexes as $indexData)
		{
			list($index, $key) = $indexData;
			$index->columns[$key] = $newName;
			static::restoreIndex($index);
		}

		foreach ($columnFks as $fkData)
		{
			list($fk, $key) = $fkData;

			if ($key !== false)
			{
				$fk->columns[$key] = $newName;
			}

			static::restoreForeignKey($fk);
		}
	}

	/**
	 * Creates elements for all rows in a given table, swaps its 'id' PK for 'elementId', and updates the names of any
	 * FK's in other tables.
	 *
	 * @param string     $tableName   The existing table name used to store records of this element.
	 * @param string     $elementType The element type handle (e.g. "Entry", "Asset", etc.).
	 * @param bool       $hasContent  Whether this element type has content.
	 * @param bool       $isLocalized Whether this element type stores data in multiple locales.
	 * @param array|null $locales     Which locales the elements should store content in. Defaults to the primary site
	 *                                locale if the element type is not localized, otherwise all locales.
	 *
	 * @return null
	 */
	public static function makeElemental($tableName, $elementType, $hasContent = false, $isLocalized = false, $locales = null)
	{
		$tableName = Craft::$app->db->getSchema()->getRawTableName($tableName);
		$db = Craft::$app->getDb();
		$fks = static::findForeignKeysTo($tableName);

		foreach ($fks as $fk)
		{
			// Drop all FKs and indexes on this table
			static::dropAllForeignKeysOnTable($fk->table);
			static::dropAllUniqueIndexesOnTable($fk->table);

			// Rename the old id column and add the new one
			$db->createCommand()->renameColumn($fk->table->name, $fk->column, $fk->column.'_old')->execute();
			$db->createCommand()->addColumnAfter($fk->table->name, $fk->column, $fk->columnType, $fk->column.'_old')->execute();
		}

		// Rename the old id column and add the new one
		$db->createCommand()->renameColumn($tableName, 'id', 'id_old')->execute();
		$db->createCommand()->addColumnAfter($tableName, 'id', static::$_idColumnType, 'id_old')->execute();

		// Get all of the rows
		$oldRows = (new Query())
			->select('id_old')
			->from($tableName)
			->all($db);

		// Figure out which locales we're going to be storing elements_i18n and content rows in.
		if (!$locales || !is_array($locales))
		{
			if ($isLocalized)
			{
				$locales = Craft::$app->getI18n()->getSiteLocaleIds();
			}
			else
			{
				$locales = [Craft::$app->getI18n()->getPrimarySiteLocaleId()];
			}
		}

		$i18nValues    = [];
		$contentValues = [];

		foreach ($oldRows as $row)
		{
			// Create a new row in elements
			$db->createCommand()->insert('{{%elements}}', [
				'type'     => $elementType,
				'enabled'  => 1,
				'archived' => 0
			])->execute();

			// Get the new element ID
			$elementId = $db->getLastInsertID();

			// Update this table with the new element ID
			$db->createCommand()->update($tableName, ['id' => $elementId], ['id_old' => $row['id_old']])->execute();

			// Update the other tables' new FK columns
			foreach ($fks as $fk)
			{
				$db->createCommand()->update($fk->table->name, [$fk->column => $elementId], [$fk->column.'_old' => $row['id_old']])->execute();
			}

			// Queue up the elements_i18n and content values
			foreach ($locales as $locale)
			{
				$i18nValues[] = [$elementId, $locale, 1];
			}

			if ($hasContent)
			{
				foreach ($locales as $locale)
				{
					$contentValues[] = [$elementId, $locale];
				}
			}
		}

		// Save the new elements_i18n and content rows
		$db->createCommand()->batchInsert('{{%elements_i18n}}', ['elementId', 'locale', 'enabled'], $i18nValues)->execute();

		if ($hasContent)
		{
			$db->createCommand()->batchInsert('{{%content}}', ['elementId', 'locale'], $contentValues)->execute();
		}

		// Drop the old id column
		$db->createCommand()->dropColumn($tableName, 'id_old')->execute();

		// Set the new PK
		$db->createCommand()->addPrimaryKey($db->getPrimaryKeyName($tableName, 'id'), $tableName, 'id')->execute();

		// Make 'id' a FK to elements
		$db->createCommand()->addForeignKey($db->getForeignKeyName($tableName, 'id'), $tableName, 'id', 'elements', 'id', 'CASCADE')->execute();

		// Now deal with the rest of the tables
		foreach ($fks as $fk)
		{
			// Drop the old FK column
			$db->createCommand()->dropColumn($fk->table->name, $fk->column.'_old')->execute();

			// Restore its unique indexes and FKs
			static::restoreAllUniqueIndexesOnTable($fk->table);
			static::restoreAllForeignKeysOnTable($fk->table);
		}
	}

	/**
	 * Returns info about all of the tables.
	 *
	 * @return array
	 */
	public static function getTables()
	{
		if (!isset(static::$_tables))
		{
			static::_analyzeTables();
		}

		return static::$_tables;
	}

	/**
	 * Returns info about a given table.
	 *
	 * @param string $tableName
	 *
	 * @return object|null
	 */
	public static function getTable($tableName)
	{
		$tableName = Craft::$app->db->getSchema()->getRawTableName($tableName);
		$tables = static::getTables();

		if (isset($tables[$tableName]))
		{
			return $tables[$tableName];
		}
		else
		{
			return null;
		}
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
		$tableName = Craft::$app->db->getSchema()->getRawTableName($tableName);
		$fks = [];

		foreach (static::getTables() as $otherTable)
		{
			foreach ($otherTable->fks as $fk)
			{
				if ($fk->refTable == $tableName)
				{
					// Figure out which column in the FK is pointing to this table's id column (if any)
					$fkColumnIndex = array_search($column, $fk->refColumns);

					if ($fkColumnIndex !== false)
					{
						$fkColumnName = $fk->columns[$fkColumnIndex];

						// Get its column type
						$fkColumnRequired = StringHelper::contains($otherTable->columns[$fkColumnName]->type, 'NOT NULL');
						$fkColumnType = array_merge(static::$_idColumnType, ['required' => $fkColumnRequired]);

						$fks[] = (object) [
							'fk'         => $fk,
							'table'      => $otherTable,
							'column'     => $fkColumnName,
							'columnType' => $fkColumnType
						];
					}
				}
			}
		}

		return $fks;
	}

	/**
	 * Drops all the foreign keys on a table.
	 *
	 * @param object $table
	 *
	 * @return null
	 */
	public static function dropAllForeignKeysOnTable($table)
	{
		foreach ($table->fks as $fk)
		{
			static::dropForeignKey($fk);
		}
	}

	/**
	 * Drops a foreign key.
	 *
	 * @param object $fk
	 *
	 * @return null
	 */
	public static function dropForeignKey($fk)
	{
		$db = Craft::$app->getDb();
		$db->createCommand()->dropForeignKey($fk->name, $fk->table->name)->execute();
	}

	/**
	 * Drops all the indexes on a table.
	 *
	 * @param object $table
	 *
	 * @return null
	 */
	public static function dropAllIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			static::dropIndex($index);
		}
	}

	/**
	 * Drops all the unique indexes on a table.
	 *
	 * @param object $table
	 *
	 * @return null
	 */
	public static function dropAllUniqueIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				static::dropIndex($index);
			}
		}
	}

	/**
	 * Drops an index.
	 *
	 * @param object $index
	 *
	 * @return null
	 */
	public static function dropIndex($index)
	{
		$db = Craft::$app->getDb();
		$db->createCommand()->dropIndex($index->name, $index->table->name)->execute();
	}

	/**
	 * Restores all the indexes on a table.
	 *
	 * @param object $table
	 *
	 * @return null
	 */
	public static function restoreAllIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			static::restoreIndex($index);
		}
	}

	/**
	 * Restores all the unique indexes on a table.
	 *
	 * @param object $table
	 *
	 * @return null
	 */
	public static function restoreAllUniqueIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				static::restoreIndex($index);
			}
		}
	}

	/**
	 * Restores an index.
	 *
	 * @param object $index
	 *
	 * @return null
	 */
	public static function restoreIndex($index)
	{
		$db = Craft::$app->getDb();
		$table = $index->table->name;
		$columns = implode(',', $index->columns);
		$db->createCommand()->createIndex($db->getIndexName($table, $columns), $table, $columns, $index->unique)->execute();

		// Update our record of its name
		$index->name = $db->getIndexName($index->table->name, $index->columns, $index->unique);
	}

	/**
	 * Restores all the foreign keys on a table.
	 *
	 * @param object $table
	 *
	 * @return null
	 */
	public static function restoreAllForeignKeysOnTable($table)
	{
		foreach ($table->fks as $fk)
		{
			static::restoreForeignKey($fk);
		}
	}

	/**
	 * Restores a foreign key.
	 *
	 * @param object $fk
	 *
	 * @return null
	 */
	public static function restoreForeignKey($fk)
	{
		$db = Craft::$app->getDb();
		$table = $fk->table->name;
		$columns = implode(',', $fk->columns);
		$db->createCommand()->addForeignKey($db->getForeignKeyName($table, $columns), $table, $columns, $fk->refTable, implode(',', $fk->refColumns), $fk->onDelete, $fk->onUpdate)->execute();

		// Update our record of its name
		$fk->name = $db->getForeignKeyName($fk->table->name, $fk->columns);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Records all the foreign keys and indexes for each table.
	 *
	 * @return null
	 */
	private static function _analyzeTables()
	{
		static::$_tables = [];

		$tables = Craft::$app->getDb()->getSchema()->getTableNames();

		foreach ($tables as $table)
		{
			static::_analyzeTable($table);
		}
	}

	/**
	 * Records all the foreign keys and indexes for a given table.
	 *
	 * @param string $table
	 *
	 * @return null
	 */
	private static function _analyzeTable($table)
	{
		static::$_tables[$table] = (object) [
			'name'    => $table,
			'columns' => [],
			'fks'     => [],
			'indexes' => [],
		];

		// Get the CREATE TABLE sql
		$query = Craft::$app->getDb()->createCommand("SHOW CREATE TABLE `$table`")->queryOne();

		// Don't want to include any views.
		if (isset($query['Create Table']))
		{
			$createTableSql = $query['Create Table'];

			// Find the columns
			if (preg_match_all('/^\s*`(\w+)`\s+(.*),$/m', $createTableSql, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
					$name = $match[1];
					static::$_tables[$table]->columns[$name] = (object)[
						'name' => $name,
						'type' => $match[2]
					];
				}
			}

			// Find the foreign keys
			if (preg_match_all("/CONSTRAINT `(\w+)` FOREIGN KEY \(`([\w`,]+)`\) REFERENCES `(\w+)` \(`([\w`,]+)`\)( ON DELETE (" . static::$_fkRefActions . "))?( ON UPDATE (" . static::$_fkRefActions . "))?/", $createTableSql, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
					$name = $match[1];
					static::$_tables[$table]->fks[] = (object)[
						'name'       => $name,
						'columns'    => explode('`,`', $match[2]),
						'refTable'   => $match[3],
						'refColumns' => explode('`,`', $match[4]),
						'onDelete'   => (!empty($match[6]) ? $match[6] : null),
						'onUpdate'   => (!empty($match[8]) ? $match[8] : null),
						'table'      => static::$_tables[$table],
					];
				}
			}

			// Find the indexes
			if (preg_match_all('/(UNIQUE )?KEY `(\w+)` \(`([\w`,]+)`\)/', $createTableSql, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
					$name = $match[2];
					static::$_tables[$table]->indexes[] = (object)[
						'name'    => $name,
						'columns' => explode('`,`', $match[3]),
						'unique'  => !empty($match[1]),
						'table'   => static::$_tables[$table],
					];
				}
			}
		}
	}
}
