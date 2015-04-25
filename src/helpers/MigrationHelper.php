<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\db\Query;
use yii\db\Migration;

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
	private static $_idColumnType = 'integer not null';

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
	 * @param array $columns
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function dropForeignKeyIfExists($tableName, $columns, Migration $migration = null)
	{
		$tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
		$table = static::getTable($tableName);

		foreach ($table->fks as $i => $fk)
		{
			if ($columns == $fk->columns)
			{
				static::dropForeignKey($fk, $migration);
				unset($table->fks[$i]);
				break;
			}
		}
	}

	/**
	 * Drops an index if it exists.
	 *
	 * @param string $tableName
	 * @param array $columns
	 * @param bool $unique
	 * @param Migration $migration
	 *
	 * @return false
	 */
	public static function dropIndexIfExists($tableName, $columns, $unique = false, Migration $migration = null)
	{
		$tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
		$table = static::getTable($tableName);

		foreach ($table->indexes as $i => $index)
		{
			if ($columns == $index->columns && $unique == $index->unique)
			{
				static::dropIndex($index, $migration);
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
	 * @param Migration $migration
	 *
	 * @return false
	 */
	public static function renameTable($oldName, $newName, Migration $migration = null)
	{
		$oldName = Craft::$app->getDb()->getSchema()->getRawTableName($oldName);
		$newName = Craft::$app->getDb()->getSchema()->getRawTableName($newName);

		// Drop any foreign keys pointing to this table
		$fks = static::findForeignKeysTo($oldName);

		foreach ($fks as $fk)
		{
			// Ignore if the FK is coming from this very table, since dropAllForeignKeysOnTable() will take care of that
			if ($fk->table->name === $oldName)
			{
				continue;
			}

			static::dropForeignKey($fk->fk, $migration);
		}

		// Drop all the FKs and indexes on the table
		$table = static::getTable($oldName);
		static::dropAllForeignKeysOnTable($table, $migration);
		static::dropAllIndexesOnTable($table, $migration);

		// Rename the table
		if ($migration !== null)
		{
			$migration->renameTable($oldName, $newName);
		}
		else
		{
			Craft::$app->getDb()->createCommand()->renameTable($oldName, $newName)->execute();
		}

		// Update our internal records
		static::$_tables[$newName] = $table;
		static::$_tables[$newName]->name = $newName;
		unset(static::$_tables[$oldName]);

		foreach ($fks as $fk)
		{
			$fk->fk->refTable = $newName;

			// Ignore if the FK is coming from this very table, since restoreAllForeignKeysOnTable() already took care of that
			if ($fk->table->name === $newName)
			{
				continue;
			}

			static::restoreForeignKey($fk->fk, $migration);
		}

		static::restoreAllIndexesOnTable($table, $migration);
		static::restoreAllForeignKeysOnTable($table, $migration);
	}

	/**
	 * Renames a column, while also updating any index and FK names that use the column.
	 *
	 * @param string $tableName
	 * @param string $oldName
	 * @param string $newName
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function renameColumn($tableName, $oldName, $newName, Migration $migration = null)
	{
		$tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
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
			static::dropForeignKey($fk, $migration);
		}

		foreach ($table->indexes as $index)
		{
			$key = array_search($oldName, $index->columns);
			if ($key !== false)
			{
				$columnIndexes[] = [$index, $key];
				static::dropIndex($index, $migration);
			}
		}

		foreach ($allOtherTableFks as $fkData)
		{
			$key = array_search($oldName, $fkData->fk->refColumns);
			if ($key !== false)
			{
				$otherTableFks[] = [$fkData->fk, $key];
				static::dropForeignKey($fkData->fk, $migration);
			}
		}

		// Rename the column
		if ($migration !== null)
		{
			$migration->renameColumn($tableName, $oldName, $newName);
		}
		else
		{
			Craft::$app->getDb()->createCommand()->renameColumn($tableName, $oldName, $newName)->execute();
		}

		// Update the table records
		$table->columns[$newName] = $table->columns[$oldName];
		$table->columns[$newName]->name = $newName;
		unset($table->columns[$oldName]);

		// Restore the FKs and indexes, and update our records
		foreach ($otherTableFks as $fkData)
		{
			list($fk, $key) = $fkData;
			$fk->refColumns[$key] = $newName;
			static::restoreForeignKey($fk, $migration);
		}

		foreach ($columnIndexes as $indexData)
		{
			list($index, $key) = $indexData;
			$index->columns[$key] = $newName;
			static::restoreIndex($index, $migration);
		}

		foreach ($columnFks as $fkData)
		{
			list($fk, $key) = $fkData;

			if ($key !== false)
			{
				$fk->columns[$key] = $newName;
			}

			static::restoreForeignKey($fk, $migration);
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
	 * @param Migration  $migration   The migration instance that should handle the actual query executions.
	 *
	 * @return null
	 */
	public static function makeElemental($tableName, $elementType, $hasContent = false, $isLocalized = false, $locales = null, Migration $migration = null)
	{
		$tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
		$db = Craft::$app->getDb();
		$fks = static::findForeignKeysTo($tableName);

		foreach ($fks as $fk)
		{
			// Drop all FKs and indexes on this table
			static::dropAllForeignKeysOnTable($fk->table, $migration);
			static::dropAllUniqueIndexesOnTable($fk->table, $migration);

			// Rename the old id column and add the new one
			if ($migration !== null)
			{
				$migration->renameColumn($fk->table->name, $fk->column, $fk->column.'_old');
				$migration->addColumnAfter($fk->table->name, $fk->column, $fk->columnType, $fk->column.'_old');
			}
			else
			{
				$db->createCommand()->renameColumn($fk->table->name, $fk->column, $fk->column.'_old')->execute();
				$db->createCommand()->addColumnAfter($fk->table->name, $fk->column, $fk->columnType, $fk->column.'_old')->execute();
			}
		}

		// Rename the old id column and add the new one
		if ($migration !== null)
		{
			$migration->renameColumn($tableName, 'id', 'id_old');
			$migration->addColumnAfter($tableName, 'id', static::$_idColumnType, 'id_old');
		}
		else
		{
			$db->createCommand()->renameColumn($tableName, 'id', 'id_old')->execute();
			$db->createCommand()->addColumnAfter($tableName, 'id', static::$_idColumnType, 'id_old')->execute();
		}

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
			$columns = [
				'type'     => $elementType,
				'enabled'  => 1,
				'archived' => 0
			];

			if ($migration !== null)
			{
				$migration->insert('{{%elements}}', $columns);
			}
			else
			{
				$db->createCommand()->insert('{{%elements}}', $columns)->execute();
			}

			// Get the new element ID
			$elementId = $db->getLastInsertID();

			// Update this table with the new element ID
			$columns = ['id' => $elementId];
			$conditions = ['id_old' => $row['id_old']];

			if ($migration !== null)
			{
				$migration->update($tableName, $columns, $conditions);
			}
			else
			{
				$db->createCommand()->update($tableName, $columns, $conditions)->execute();
			}

			// Update the other tables' new FK columns
			foreach ($fks as $fk)
			{
				$columns = [$fk->column => $elementId];
				$conditions = [$fk->column.'_old' => $row['id_old']];

				if ($migration !== null)
				{
					$migration->update($fk->table->name, $columns, $conditions);
				}
				else
				{
					$db->createCommand()->update($fk->table->name, $columns, $conditions)->execute();
				}
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
		$columns = ['elementId', 'locale', 'enabled'];

		if ($migration !== null)
		{
			$migration->batchInsert('{{%elements_i18n}}', $columns, $i18nValues);
		}
		else
		{
			$db->createCommand()->batchInsert('{{%elements_i18n}}', $columns, $i18nValues)->execute();
		}

		if ($hasContent)
		{
			$columns = ['elementId', 'locale'];

			if ($migration !== null)
			{
				$migration->batchInsert('{{%content}}', $columns, $contentValues);
			}
			else
			{
				$db->createCommand()->batchInsert('{{%content}}', $columns, $contentValues)->execute();
			}
		}

		// Drop the old id column, set the new PK, and make 'id' a FK to elements
		$pkName = $db->getPrimaryKeyName($tableName, 'id');
		$fkName = $db->getForeignKeyName($tableName, 'id');

		if ($migration !== null)
		{
			$migration->dropColumn($tableName, 'id_old');
			$migration->addPrimaryKey($pkName, $tableName, 'id');
			$migration->addForeignKey($fkName, $tableName, 'id', 'elements', 'id', 'CASCADE');
		}
		else
		{
			$db->createCommand()->dropColumn($tableName, 'id_old')->execute();
			$db->createCommand()->addPrimaryKey($pkName, $tableName, 'id')->execute();
			$db->createCommand()->addForeignKey($fkName, $tableName, 'id', 'elements', 'id', 'CASCADE')->execute();
		}

		// Now deal with the rest of the tables
		foreach ($fks as $fk)
		{
			// Drop the old FK column
			if ($migration !== null)
			{
				$migration->dropColumn($fk->table->name, $fk->column.'_old');
			}
			else
			{
				$db->createCommand()->dropColumn($fk->table->name, $fk->column.'_old')->execute();
			}

			// Restore its unique indexes and FKs
			static::restoreAllUniqueIndexesOnTable($fk->table, $migration);
			static::restoreAllForeignKeysOnTable($fk->table, $migration);
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
		$tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
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
		$tableName = Craft::$app->getDb()->getSchema()->getRawTableName($tableName);
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
						$fkColumnType = static::$_idColumnType;
						$fkColumnRequired = StringHelper::contains($otherTable->columns[$fkColumnName]->type, 'NOT NULL');

						if (!$fkColumnRequired)
						{
							$fkColumnType = str_replace(' NOT NULL', '', $fkColumnType);
						}

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
	 * Drops a table, its own foreign keys, and any foreign keys referencing it.
	 *
	 * @param string $tableName
	 * @param Migration $migration
	 */
	public static function dropTable($tableName, Migration $migration = null)
	{
		$table = static::getTable($tableName);

		static::dropAllForeignKeysOnTable($table, $migration);
		static::dropAllForeignKeysToTable($table, $migration);

		if ($migration !== null)
		{
			$migration->dropTable($tableName);
		}
		else
		{
			Craft::$app->getDb()->createCommand()->dropTable($tableName)->execute();
		}
	}

	/**
	 * Drops all the foreign keys on a table.
	 *
	 * @param object $table
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function dropAllForeignKeysOnTable($table, Migration $migration = null)
	{
		foreach ($table->fks as $fk)
		{
			static::dropForeignKey($fk, $migration);
		}
	}

	/**
	 * Drops all the foreign keys that reference a table.
	 *
	 * @param object $table
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function dropAllForeignKeysToTable($table, Migration $migration = null)
	{
		foreach (array_keys($table->columns) as $column)
		{
			$fks = static::findForeignKeysTo($table->name, $column);

			foreach ($fks as $fk)
			{
				static::dropForeignKey($fk, $migration);
			}
		}
	}

	/**
	 * Drops a foreign key.
	 *
	 * @param object $fk
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function dropForeignKey($fk, Migration $migration = null)
	{
		if ($migration !== null)
		{
			$migration->dropForeignKey($fk->name, $fk->table->name);
		}
		else
		{
			Craft::$app->getDb()->createCommand()->dropForeignKey($fk->name, $fk->table->name)->execute();
		}
	}

	/**
	 * Drops all the indexes on a table.
	 *
	 * @param object $table
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function dropAllIndexesOnTable($table, Migration $migration = null)
	{
		foreach ($table->indexes as $index)
		{
			static::dropIndex($index, $migration);
		}
	}

	/**
	 * Drops all the unique indexes on a table.
	 *
	 * @param object $table
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function dropAllUniqueIndexesOnTable($table, Migration $migration = null)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				static::dropIndex($index, $migration);
			}
		}
	}

	/**
	 * Drops an index.
	 *
	 * @param object $index
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function dropIndex($index, Migration $migration = null)
	{
		if ($migration !== null)
		{
			$migration->dropIndex($index->name, $index->table->name);
		}
		else
		{
			Craft::$app->getDb()->createCommand()->dropIndex($index->name, $index->table->name)->execute();
		}
	}

	/**
	 * Restores all the indexes on a table.
	 *
	 * @param object $table
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function restoreAllIndexesOnTable($table, Migration $migration = null)
	{
		foreach ($table->indexes as $index)
		{
			static::restoreIndex($index, $migration);
		}
	}

	/**
	 * Restores all the unique indexes on a table.
	 *
	 * @param object $table
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function restoreAllUniqueIndexesOnTable($table, Migration $migration = null)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				static::restoreIndex($index, $migration);
			}
		}
	}

	/**
	 * Restores an index.
	 *
	 * @param object $index
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function restoreIndex($index, Migration $migration = null)
	{
		$db = Craft::$app->getDb();
		$table = $index->table->name;
		$columns = implode(',', $index->columns);
		$indexName = $db->getIndexName($table, $columns, $index->unique);

		if ($migration !== null)
		{
			$migration->createIndex($indexName, $table, $columns, $index->unique);
		}
		else
		{
			$db->createCommand()->createIndex($indexName, $table, $columns, $index->unique)->execute();
		}

		// Update our record of its name
		$index->name = $indexName;
	}

	/**
	 * Restores all the foreign keys on a table.
	 *
	 * @param object $table
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function restoreAllForeignKeysOnTable($table, Migration $migration = null)
	{
		foreach ($table->fks as $fk)
		{
			static::restoreForeignKey($fk, $migration);
		}
	}

	/**
	 * Restores a foreign key.
	 *
	 * @param object $fk
	 * @param Migration $migration
	 *
	 * @return null
	 */
	public static function restoreForeignKey($fk, Migration $migration = null)
	{
		$db = Craft::$app->getDb();
		$table = $fk->table->name;
		$columns = implode(',', $fk->columns);
		$fkName = $db->getForeignKeyName($table, $columns);

		if ($migration !== null)
		{
			$migration->addForeignKey($fkName, $table, $columns, $fk->refTable, $fk->refColumns, $fk->onDelete, $fk->onUpdate);
		}
		else
		{
			$db->createCommand()->addForeignKey($fkName, $table, $columns, $fk->refTable, $fk->refColumns, $fk->onDelete, $fk->onUpdate)->execute();
		}

		// Update our record of its name
		$fk->name = $fkName;
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
			'pks'     => [],
			'fks'     => [],
			'indexes' => [],
			'options' => '',
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

			// Find the primary keys
			if (preg_match('/PRIMARY KEY \(([^\)]+)\)/', $createTableSql, $matches))
			{
				if (preg_match_all('/`(\w+)`/', $matches[0], $pkMatches))
				{
					static::$_tables[$table]->pks = $pkMatches[1];
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

			if (preg_match('/\)\s*(.*)$/', $createTableSql, $matches))
			{
				static::$_tables[$table]->options = $matches[1];
			}
		}
	}
}
