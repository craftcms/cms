<?php
namespace Craft;

/**
 * Migration utility methods.
 */
class MigrationHelper
{
	private static $_tables;
	private static $_tablePrefixLength;

	private static $_idColumnType = array('column' => ColumnType::Int, 'required' => true);
	private static $_fkRefActions = 'RESTRICT|CASCADE|NO ACTION|SET DEFAULT|SET NULL';

	/**
	 * Drops a foreign key if it exists.
	 *
	 * @static
	 * @param string $tableName
	 * @param array $columns
	 */
	public static function dropForeignKeyIfExists($tableName, $columns)
	{
		$table = static::_getTable($tableName);

		foreach ($table->fks as $i => $fk)
		{
			if ($columns == $fk->columns)
			{
				static::_dropForeignKey($fk);
				unset($table->fks[$i]);
				break;
			}
		}
	}

	/**
	 * Drops an index if it exists.
	 *
	 * @static
	 * @param string $tableName
	 * @param array $columns
	 * @param bool $unique
	 */
	public static function dropIndexIfExists($tableName, $columns, $unique = false)
	{
		$table = static::_getTable($tableName);

		foreach ($table->indexes as $i => $index)
		{
			if ($columns == $index->columns && $unique == $index->unique)
			{
				static::_dropIndex($index);
				unset($table->indexes[$i]);
				break;
			}
		}
	}

	/**
	 * Renames a table, while also updating its index and FK names,
	 * as well as any other FK names pointing to the table.
	 *
	 * @static
	 * @param string $oldName
	 * @param string $newName
	 */
	public static function renameTable($oldName, $newName)
	{
		// Drop any foreign keys pointing to this table
		$fks = static::_findForeignKeysToTable($oldName);

		foreach ($fks as $fk)
		{
			static::_dropForeignKey($fk->fk);
		}

		// Drop all the FKs and indexes on the table
		$table = static::_getTable($oldName);
		static::_dropAllForeignKeysOnTable($table);
		static::_dropAllIndexesOnTable($table);

		// Rename the table
		craft()->db->createCommand()->renameTable($oldName, $newName);

		// Update our internal records
		static::$_tables[$newName] = $table;
		static::$_tables[$newName]->name = $newName;
		unset(static::$_tables[$oldName]);

		static::_restoreAllIndexesOnTable($table);
		static::_restoreAllForeignKeysOnTable($table);

		foreach ($fks as $fk)
		{
			$fk->fk->refTable = $newName;
			static::_restoreForeignKey($fk->fk);
		}
	}

	/**
	 * Renames a column, while also updating any index and FK names that use the column.
	 *
	 * @static
	 * @param string $tableName
	 * @param string $oldName
	 * @param string $newName
	 */
	public static function renameColumn($tableName, $oldName, $newName)
	{
		$table = static::_getTable($tableName);
		$allOtherTableFks = static::_findForeignKeysToTable($tableName);

		// Temporarily drop any FKs and indexes that include this column
		$columnFks = array();
		$columnIndexs = array();
		$otherTableFks = array();

		foreach ($table->fks as $fk)
		{
			$key = array_search($oldName, $fk->columns);
			if ($key !== false)
			{
				$columnFks[] = array($fk, $key);
				static::_dropForeignKey($fk);
			}
		}

		foreach ($table->indexes as $index)
		{
			$key = array_search($oldName, $index->columns);
			if ($key !== false)
			{
				$columnIndexes[] = array($index, $key);
				static::_dropIndex($index);
			}
		}

		foreach ($allOtherTableFks as $fkData)
		{
			$key = array_search($oldName, $fkData->fk->refColumns);
			if ($key !== false)
			{
				$otherTableFks[] = array($fkData->fk, $key);
				static::_dropForeignKey($fkData->fk);
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
			static::_restoreForeignKey($fk);
		}

		foreach ($columnIndexes as $indexData)
		{
			list($index, $key) = $indexData;
			$index->columns[$key] = $newName;
			static::_restoreIndex($index);
		}

		foreach ($columnFks as $fkData)
		{
			list($fk, $key) = $fkData;
			$fk->columns[$key] = $newName;
			static::_restoreForeignKey($fk);
		}
	}

	/**
	 * Creates elements for all rows in a given table, swaps its 'id' PK for 'elementId',
	 * and updates the names of any FK's in other tables.
	 *
	 * @static
	 * @param string $table
	 * @param string $elementType
	 */
	public static function makeElemental($table, $elementType)
	{
		$fks = static::_findForeignKeysToTable($table);

		foreach ($fks as $fk)
		{
			// Drop all FKs and indexes on this table
			static::_dropAllForeignKeysOnTable($fk->table);
			static::_dropAllUniqueIndexesOnTable($fk->table);

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
			static::_restoreAllUniqueIndexesOnTable($fk->table);
			static::_restoreAllForeignKeysOnTable($fk->table);
		}
	}

	/**
	 * Returns info about all of the tables.
	 *
	 * @static
	 * @access private
	 * @return array
	 */
	private static function _getTables()
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
	 * @static
	 * @access private
	 * @param string $table
	 * @return object|null
	 */
	private static function _getTable($table)
	{
		$tables = static::_getTables();

		if (isset($tables[$table]))
		{
			return $tables[$table];
		}
	}

	/**
	 * Returns a list of all the foreign keys that point to a given table.
	 *
	 * @static
	 * @access private
	 * @param string $table
	 * @return array
	 */
	private static function _findForeignKeysToTable($table)
	{
		$fks = array();

		foreach (static::_getTables() as $otherTable)
		{
			foreach ($otherTable->fks as $fk)
			{
				if ($fk->refTable == $table)
				{
					// Figure out which column in the FK is pointing to this table's id column (if any)
					$fkColumnIndex = array_search('id', $fk->refColumns);

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
	 * Returns the length of the table prefix.
	 *
	 * @static
	 * @access private
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
	 * @static
	 * @access private
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
	 * @static
	 * @access private
	 * @param string $table
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
		$createTableSql = $query['Create Table'];

		// Find the columns
		if (preg_match_all('/^\s*`(\w+)`\s+(.*),$/m', $createTableSql, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$name = $match[1];
				static::$_tables[$table]->columns[$name] = (object) array(
					'name' => $name,
					'type' => $match[2]
				);
			}
		}

		// Find the foreign keys
		if (preg_match_all("/CONSTRAINT `(\w+)` FOREIGN KEY \(`([\w`,]+)`\) REFERENCES `(\w+)` \(`([\w`,]+)`\)( ON DELETE (".static::$_fkRefActions."))?( ON UPDATE (".static::$_fkRefActions."))?/", $createTableSql, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$name = $match[1];
				static::$_tables[$table]->fks[] = (object) array(
					'name'        => $name,
					'columns'     => explode('`,`', $match[2]),
					'refTable'    => mb_substr($match[3], static::_getTablePrefixLength()),
					'refColumns'  => explode('`,`', $match[4]),
					'onDelete'    => (!empty($match[6]) ? $match[6] : null),
					'onUpdate'    => (!empty($match[8]) ? $match[8] : null),
					'table'       => static::$_tables[$table],
				);
			}
		}

		// Find the indexes
		if (preg_match_all('/(UNIQUE )?KEY `(\w+)` \(`([\w`,]+)`\)/', $createTableSql, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$name = $match[2];
				static::$_tables[$table]->indexes[] = (object) array(
					'name'    => $name,
					'columns' => explode('`,`', $match[3]),
					'unique'  => !empty($match[1]),
					'table'   => static::$_tables[$table],
				);
			}
		}
	}

	/**
	 * Returns all of the tables that have foreign keys to a given table and column.
	 *
	 * @static
	 * @access private
	 * @param string $table
	 * @param string $column
	 * @return array
	 */
	private static function _getTablesWithForeignKeysTo($table, $column = 'id')
	{
		$tables = array();

		foreach (static::_getTables() as $table)
		{
			foreach ($table->fks as $fk)
			{
				if ($fk->refTable == $table && in_array($column, $fk->refColumns))
				{
					$fkTables[] = $table;
				}
			}
		}

		return $tables;
	}

	/**
	 * Drops all the foreign keys on a table.
	 *
	 * @static
	 * @access private
	 * @param object $table
	 */
	private static function _dropAllForeignKeysOnTable($table)
	{
		foreach ($table->fks as $fk)
		{
			static::_dropForeignKey($fk);
		}
	}

	/**
	 * Drops a foreign key.
	 *
	 * @static
	 * @access private
	 * @param object $fk
	 */
	private static function _dropForeignKey($fk)
	{
		// Don't assume that the FK name is "correct"
		craft()->db->createCommand()->setText(craft()->db->getSchema()->dropForeignKey($fk->name, '{{'.$fk->table->name.'}}'))->execute();
	}

	/**
	 * Drops all the indexes on a table.
	 *
	 * @static
	 * @access private
	 * @param object $table
	 */
	private static function _dropAllIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			static::_dropIndex($index);
		}
	}

	/**
	 * Drops all the unique indexes on a table.
	 *
	 * @static
	 * @access private
	 * @param object $table
	 */
	private static function _dropAllUniqueIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				static::_dropIndex($index);
			}
		}
	}

	/**
	 * Drops an index.
	 *
	 * @static
	 * @access private
	 * @param object $index
	 */
	private static function _dropIndex($index)
	{
		// Don't assume that the constraint name is "correct"
		craft()->db->createCommand()->setText(craft()->db->getSchema()->dropIndex($index->name, '{{'.$index->table->name.'}}'))->execute();
	}

	/**
	 * Restores all the indexes on a table.
	 *
	 * @static
	 * @access private
	 * @param object $table
	 */
	private static function _restoreAllIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			static::_restoreIndex($index);
		}
	}

	/**
	 * Restores all the unique indexes on a table.
	 *
	 * @static
	 * @access private
	 * @param object $table
	 */
	private static function _restoreAllUniqueIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				static::_restoreIndex($index);
			}
		}
	}

	/**
	 * Restores an index.
	 *
	 * @static
	 * @access private
	 * @param object $fk
	 */
	private static function _restoreIndex($index)
	{
		craft()->db->createCommand()->createIndex($index->table->name, implode(',', $index->columns), $index->unique);

		// Update our record of its name
		$index->name = DbHelper::getIndexName($index->table->name, $index->columns, $index->unique);
	}

	/**
	 * Restores all the foreign keys on a table.
	 *
	 * @static
	 * @access private
	 * @param object $table
	 */
	private static function _restoreAllForeignKeysOnTable($table)
	{
		foreach ($table->fks as $fk)
		{
			static::_restoreForeignKey($fk);
		}
	}

	/**
	 * Restores a foreign key.
	 *
	 * @static
	 * @access private
	 * @param object $fk
	 */
	private static function _restoreForeignKey($fk)
	{
		craft()->db->createCommand()->addForeignKey($fk->table->name, implode(',', $fk->columns), $fk->refTable, implode(',', $fk->refColumns), $fk->onDelete, $fk->onUpdate);

		// Update our record of its name
		$fk->name = DbHelper::getForeignKeyName($fk->table->name, $fk->columns);
	}
}
