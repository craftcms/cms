<?php
namespace Craft;

/**
 * Migration utility methods.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.1
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
	 * @var
	 */
	private static $_tablePrefixLength;

	/**
	 * @var array
	 */
	private static $_idColumnType = array('column' => ColumnType::Int, 'required' => true);

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
		craft()->db->getSchema()->refresh();
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
		craft()->db->createCommand()->renameTable($oldName, $newName);

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
		$table = static::getTable($tableName);
		$allOtherTableFks = static::findForeignKeysTo($tableName);

		// Temporarily drop any FKs and indexes that include this column
		$columnFks = array();
		$columnIndexes = array();
		$otherTableFks = array();

		// Drop all the FKs because any one of them might be relying on an index we're about to drop
		foreach ($table->fks as $fk)
		{
			$key = array_search($oldName, $fk->columns);
			$columnFks[] = array($fk, $key);
			static::dropForeignKey($fk);
		}

		foreach ($table->indexes as $index)
		{
			$key = array_search($oldName, $index->columns);
			if ($key !== false)
			{
				$columnIndexes[] = array($index, $key);
				static::dropIndex($index);
			}
		}

		foreach ($allOtherTableFks as $fkData)
		{
			$key = array_search($oldName, $fkData->fk->refColumns);
			if ($key !== false)
			{
				$otherTableFks[] = array($fkData->fk, $key);
				static::dropForeignKey($fkData->fk);
			}
		}

		// Rename the column
		craft()->db->createCommand()->renameColumn($tableName, $oldName, $newName);

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
	 * @param string     $table       The existing table name used to store records of this element.
	 * @param string     $elementType The element type handle (e.g. "Entry", "Asset", etc.).
	 * @param bool       $hasContent  Whether this element type has content.
	 * @param bool       $isLocalized Whether this element type stores data in multiple locales.
	 * @param array|null $locales     Which locales the elements should store content in. Defaults to the primary site
	 *                                locale if the element type is not localized, otherwise all locales.
	 *
	 * @return null
	 */
	public static function makeElemental($table, $elementType, $hasContent = false, $isLocalized = false, $locales = null)
	{
		$fks = static::findForeignKeysTo($table);

		foreach ($fks as $fk)
		{
			// Drop all FKs and indexes on this table
			static::dropAllForeignKeysOnTable($fk->table);
			static::dropAllUniqueIndexesOnTable($fk->table);

			// Rename the old id column and add the new one
			craft()->db->createCommand()->renameColumn($fk->table->name, $fk->column, $fk->column.'_old');
			craft()->db->createCommand()->addColumnAfter($fk->table->name, $fk->column, $fk->columnType, $fk->column.'_old');
		}

		// Rename the old id column and add the new one
		craft()->db->createCommand()->renameColumn($table, 'id', 'id_old');
		craft()->db->createCommand()->addColumnAfter($table, 'id', static::$_idColumnType, 'id_old');

		// Get all of the rows
		$oldRows = craft()->db->createCommand()
			->select('id_old')
			->from($table)
			->queryAll();

		// Figure out which locales we're going to be storing elements_i18n and content rows in.
		if (!$locales || !is_array($locales))
		{
			if ($isLocalized)
			{
				$locales = craft()->i18n->getSiteLocaleIds();
			}
			else
			{
				$locales = array(craft()->i18n->getPrimarySiteLocaleId());
			}
		}

		$i18nValues = array();
		$contentValues = array();

		foreach ($oldRows as $row)
		{
			// Create a new row in elements
			craft()->db->createCommand()->insert('elements', array(
				'type'     => $elementType,
				'enabled'  => 1,
				'archived' => 0
			));

			// Get the new element ID
			$elementId = craft()->db->getLastInsertID();

			// Update this table with the new element ID
			craft()->db->createCommand()->update($table, array('id' => $elementId), array('id_old' => $row['id_old']));

			// Update the other tables' new FK columns
			foreach ($fks as $fk)
			{
				craft()->db->createCommand()->update($fk->table->name, array($fk->column => $elementId), array($fk->column.'_old' => $row['id_old']));
			}

			// Queue up the elements_i18n and content values
			foreach ($locales as $locale)
			{
				$i18nValues[] = array($elementId, $locale, 1);
			}

			if ($hasContent)
			{
				foreach ($locales as $locale)
				{
					$contentValues[] = array($elementId, $locale);
				}
			}
		}

		// Save the new elements_i18n and content rows
		craft()->db->createCommand()->insertAll('elements_i18n', array('elementId', 'locale', 'enabled'), $i18nValues);

		if ($hasContent)
		{
			craft()->db->createCommand()->insertAll('content', array('elementId', 'locale'), $contentValues);
		}

		// Drop the old id column
		craft()->db->createCommand()->dropColumn($table, 'id_old');

		// Set the new PK
		craft()->db->createCommand()->addPrimaryKey($table, 'id');

		// Make 'id' a FK to elements
		craft()->db->createCommand()->addForeignKey($table, 'id', 'elements', 'id', 'CASCADE');

		// Now deal with the rest of the tables
		foreach ($fks as $fk)
		{
			// Drop the old FK column
			craft()->db->createCommand()->dropColumn($fk->table->name, $fk->column.'_old');

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
	 * @param string $table
	 *
	 * @return object|null
	 */
	public static function getTable($table)
	{
		$tables = static::getTables();

		if (isset($tables[$table]))
		{
			return $tables[$table];
		}
	}

	/**
	 * Returns a list of all the foreign keys that point to a given table/column.
	 *
	 * @param string $table  The table the foreign keys should point to.
	 * @param string $column The column the foreign keys should point to. Defaults to 'id'.
	 *
	 * @return array A list of the foreign keys pointing to that table/column.
	 */
	public static function findForeignKeysTo($table, $column = 'id')
	{
		$fks = array();

		foreach (static::getTables() as $otherTable)
		{
			foreach ($otherTable->fks as $fk)
			{
				if ($fk->refTable == $table)
				{
					// Figure out which column in the FK is pointing to this table's id column (if any)
					$fkColumnIndex = array_search($column, $fk->refColumns);

					if ($fkColumnIndex !== false)
					{
						$fkColumnName = $fk->columns[$fkColumnIndex];

						// Get its column type
						$fkColumnRequired = (mb_strpos($otherTable->columns[$fkColumnName]->type, 'NOT NULL') !== false);
						$fkColumnType = array_merge(static::$_idColumnType, array('required' => $fkColumnRequired));

						$fks[] = (object) array(
							'fk'         => $fk,
							'table'      => $otherTable,
							'column'     => $fkColumnName,
							'columnType' => $fkColumnType
						);
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
		// Don't assume that the FK name is "correct"
		craft()->db->createCommand()->setText(craft()->db->getSchema()->dropForeignKey($fk->name, '{{'.$fk->table->name.'}}'))->execute();
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
		// Don't assume that the constraint name is "correct"
		craft()->db->createCommand()->setText(craft()->db->getSchema()->dropIndex($index->name, '{{'.$index->table->name.'}}'))->execute();
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
		craft()->db->createCommand()->createIndex($index->table->name, implode(',', $index->columns), $index->unique);

		// Update our record of its name
		$index->name = craft()->db->getIndexName($index->table->name, $index->columns, $index->unique);
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
		craft()->db->createCommand()->addForeignKey($fk->table->name, implode(',', $fk->columns), $fk->refTable, implode(',', $fk->refColumns), $fk->onDelete, $fk->onUpdate);

		// Update our record of its name
		$fk->name = craft()->db->getForeignKeyName($fk->table->name, $fk->columns);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the length of the table prefix.
	 *
	 * @return string
	 */
	private static function _getTablePrefixLength()
	{
		if (!isset(static::$_tablePrefixLength))
		{
			static::$_tablePrefixLength = mb_strlen(craft()->db->tablePrefix);
		}

		return static::$_tablePrefixLength;
	}

	/**
	 * Records all the foreign keys and indexes for each table.
	 *
	 * @return null
	 */
	private static function _analyzeTables()
	{
		static::$_tables = array();

		$tables = craft()->db->getSchema()->getTableNames();

		foreach ($tables as $table)
		{
			$table = mb_substr($table, static::_getTablePrefixLength());
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
		static::$_tables[$table] = (object) array(
			'name'    => $table,
			'columns' => array(),
			'fks'     => array(),
			'indexes' => array(),
		);

		// Get the CREATE TABLE sql
		$query = craft()->db->createCommand()->setText('SHOW CREATE TABLE `{{'.$table.'}}`')->queryRow();

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
					static::$_tables[$table]->columns[$name] = (object)array(
						'name' => $name,
						'type' => $match[2]
					);
				}
			}

			// Find the foreign keys
			if (preg_match_all("/CONSTRAINT `(\w+)` FOREIGN KEY \(`([\w`,]+)`\) REFERENCES `(\w+)` \(`([\w`,]+)`\)( ON DELETE (" . static::$_fkRefActions . "))?( ON UPDATE (" . static::$_fkRefActions . "))?/", $createTableSql, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
					$name = $match[1];
					static::$_tables[$table]->fks[] = (object)array(
						'name'       => $name,
						'columns'    => explode('`,`', $match[2]),
						'refTable'   => mb_substr($match[3], static::_getTablePrefixLength()),
						'refColumns' => explode('`,`', $match[4]),
						'onDelete'   => (!empty($match[6]) ? $match[6] : null),
						'onUpdate'   => (!empty($match[8]) ? $match[8] : null),
						'table'      => static::$_tables[$table],
					);
				}
			}

			// Find the indexes
			if (preg_match_all('/(UNIQUE )?KEY `(\w+)` \(`([\w`,]+)`\)/', $createTableSql, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
					$name = $match[2];
					static::$_tables[$table]->indexes[] = (object)array(
						'name'    => $name,
						'columns' => explode('`,`', $match[3]),
						'unique'  => !empty($match[1]),
						'table'   => static::$_tables[$table],
					);
				}
			}
		}
	}
}
