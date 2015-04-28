<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db\mysql;

use Craft;
use craft\app\db\Connection;
use craft\app\enums\ConfigCategory;
use yii\base\Exception;
use yii\db\Expression;

/**
 * @inheritdoc
 *
 * @property Connection $db Connection the DB connection that this command is associated with.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class QueryBuilder extends \yii\db\mysql\QueryBuilder
{
	/**
	 * @inheritdoc
	 *
	 * @param string $table the name of the table to be created. The name will be properly quoted by the method.
	 * @param array $columns the columns (name => definition) in the new table.
	 * @param string $options additional SQL fragment that will be appended to the generated SQL.
	 * @return string the SQL statement for creating a new DB table.
	 */
	public function createTable($table, $columns, $options = null)
	{
		// Default to InnoDb
		if ($options === null || strpos($options, 'ENGINE=') === false)
		{
			$options = ($options !== null ? $options.' ' : '').'ENGINE=InnoDb';
		}

		// Use the default charset
		if (strpos($options, 'DEFAULT CHARSET=') === false)
		{
			$options .= ' DEFAULT CHARSET='.Craft::$app->getConfig()->get('charset', ConfigCategory::Db);
		}

		// Use the default collation
		if (strpos($options, 'COLLATE=') === false)
		{
			$options .= ' COLLATE='.Craft::$app->getConfig()->get('collation', ConfigCategory::Db);
		}

		return parent::createTable($table, $columns, $options);
	}

	/**
	 * Builds a SQL statement for dropping a DB table if it exists.
	 *
	 * @param string $table The table to be dropped. The name will be properly quoted by the method.
	 * @return string The SQL statement for dropping a DB table.
	 */
	public function dropTableIfExists($table)
	{
		return 'DROP TABLE IF EXISTS '.$this->db->quoteTableName($table);
	}

	/**
	 * Builds a SQL statement for adding a new DB column before all other columns in the table.
	 *
	 * @param string $table  The table that the new column will be added to. The table name will be properly quoted by the method.
	 * @param string $column The name of the new column. The name will be properly quoted by the method.
	 * @param string $type   The column type. The [[getColumnType()]] method will be invoked to convert abstract column type (if any)
	 *                       into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
	 *                       For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
	 * @return string The SQL statement for adding a new column.
	 */
	public function addColumnFirst($table, $column, $type)
	{
		return 'ALTER TABLE ' . $this->db->quoteTableName($table) .
			' ADD ' . $this->db->quoteColumnName($column).' ' .
			$this->getColumnType($type) .
			' FIRST';
	}

	/**
	 * Builds a SQL statement for adding a new DB column after another column.
	 *
	 * @param string $table  The table that the new column will be added to. The table name will be properly quoted by the method.
	 * @param string $column The name of the new column. The name will be properly quoted by the method.
	 * @param string $type   The column type. The [[getColumnType()]] method will be invoked to convert abstract column type (if any)
	 *                       into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
	 *                       For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
	 * @param string $after  The name of the column that the new column should be placed after.
	 * @return string The SQL statement for adding a new column.
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		return 'ALTER TABLE ' . $this->db->quoteTableName($table) .
			' ADD ' . $this->db->quoteColumnName($column).' ' .
			$this->getColumnType($type) .
			' AFTER '.$this->db->quoteColumnName($after);
	}

	/**
	 * Builds a SQL statement for adding a new DB column before another column.
	 *
	 * @param string $table  The table that the new column will be added to. The table name will be properly quoted by the method.
	 * @param string $column The name of the new column. The name will be properly quoted by the method.
	 * @param string $type   The column type. The [[getColumnType()]] method will be invoked to convert abstract column type (if any)
	 *                       into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
	 *                       For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
	 * @param string $before The name of the column that the new column should be placed before.
	 * @return string The SQL statement for adding a new column.
	 * @throws Exception If the $before column doesn't exist.
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		$tableInfo = $this->db->getTableSchema($table, true);
		$columns = array_keys($tableInfo->columns);
		$beforeIndex = array_search($before, $columns);

		if ($beforeIndex === false)
		{
			throw new Exception('A "'.$before.'" columns doesn’t exist on `'.$table.'`.');
		}

		if ($beforeIndex === 0)
		{
			return $this->addColumnFirst($table, $column, $type);
		}

		$after = $columns[$beforeIndex-1];
		return $this->addColumnAfter($table, $column, $type, $after);
	}

	/**
	 * Builds a SQL statement for changing the definition of a column.
	 *
	 * @param string      $table   The table whose column is to be changed. The table name will be properly quoted by the method.
	 * @param string      $column  The name of the column to be changed. The name will be properly quoted by the method.
	 * @param string      $type    The new column type. The [[getColumnType()]] method will be invoked to convert abstract
	 *                             column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
	 *                             in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null'
	 *                             will become 'varchar(255) not null'.
	 * @param string|null $newName The new column name, if any.
	 * @param string|null $after   The column that this column should be placed after, if it should be moved.
	 * @return string The SQL statement for changing the definition of a column.
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		if (!$newName)
		{
			$newName = $column;
		}

		return 'ALTER TABLE ' . $this->db->quoteTableName($table).' CHANGE ' .
			$this->db->quoteColumnName($column).' ' .
			$this->db->quoteColumnName($newName).' ' .
			$this->getColumnType($type) .
			($after ? ' AFTER '.$this->db->quoteColumnName($after) : '');
	}

	/**
	 * Builds a SQL statement for inserting some given data into a table, or updating an existing row
	 * in the event of a key constraint violation.
	 *
	 * @param string $table               The table that the row will be inserted into, or updated.
	 * @param array $keyColumns           The key-constrained column data (name => value) to be inserted into the table
	 *                                    in the event that a new row is getting created
	 * @param array $updateColumns        The non-key-constrained column data (name => value) to be inserted into the table
	 *                                    or updated in the existing row.
	 * @param array $params The binding parameters that will be generated by this method.
	 *                      They should be bound to the DB command later.
	 * @return string The SQL statement for inserting or updating data in a table.
	 */
	public function insertOrUpdate($table, $keyColumns, $updateColumns, &$params)
	{
		$schema = $this->db->getSchema();

		if (($tableSchema = $schema->getTableSchema($table)) !== null)
		{
			$columnSchemas = $tableSchema->columns;
		}
		else
		{
			$columnSchemas = [];
		}

		$columns = array_merge($keyColumns, $updateColumns);
		$names = [];
		$placeholders = [];
		$updates = [];

		foreach ($columns as $name => $value)
		{
			$qName = $schema->quoteColumnName($name);
			$names[] = $qName;

			if ($value instanceof Expression)
			{
				$placeholder = $value->expression;

				foreach ($value->params as $n => $v)
				{
					$params[$n] = $v;
				}
			}
			else
			{
				$phName = static::PARAM_PREFIX.count($params);
				$placeholder = $phName;
				$params[$phName] = !is_array($value) && isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;
			}

			$placeholders[] = $placeholder;

			// Was this an update column?
			if (isset($updateColumns[$name]))
			{
				$updates[] = "$qName = $placeholder";
			}
		}

		return 'INSERT INTO '.$schema->quoteTableName($table) .
			' (' . implode(', ', $names) . ') VALUES (' .
			implode(', ', $placeholders) . ') ON DUPLICATE KEY UPDATE ' .
			implode(', ', $updates);
	}

	/**
	 * Builds a SQL statement for replacing some text with other text in a given table column.
	 *
	 * @param string $table   The table to be updated.
	 * @param string $column  The column to be searched.
	 * @param string $find    The text to be searched for.
	 * @param string $replace The replacement text.
	 * @param array  $params  The binding parameters that will be generated by this method.
	 *                        They should be bound to the DB command later.
	 * @return string The SQL statement for replacing some text in a given table.
	 */
	public function replace($table, $column, $find, $replace, &$params)
	{
		$schema = $this->db->getSchema();
		$column = $schema->quoteColumnName($column);

		$findPhName = static::PARAM_PREFIX.count($params);
		$params[$findPhName] = $find;

		$replacePhName = static::PARAM_PREFIX.count($params);
		$params[$replacePhName] = $replace;

		return 'UPDATE '.$schema->quoteTableName($table) .
			" SET $column = REPLACE($column, $findPhName, $replacePhName)";
	}

	/**
	 * Builds the SQL expression used to return a DB result in a fixed order.
	 *
	 * @param string $column The column name that contains the values.
	 * @param array $values The column values, in the order in which the rows should be returned in.
	 * @return string The SQL expression.
	 */
	public function fixedOrder($column, $values)
	{
		$schema = $this->db->getSchema();

		foreach ($values as $i => $value)
		{
			$values[$i] = $schema->quoteValue($value);
		}

		return 'FIELD('.$this->db->quoteColumnName($column).', ' .
			implode(', ', $values).')';
	}
}
