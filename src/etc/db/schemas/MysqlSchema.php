<?php
namespace Craft;

/**
 * Class MysqlSchema
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.db.schemas
 * @since     1.0
 */
class MysqlSchema extends \CMysqlSchema
{
	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param \CDbConnection $conn The database connection.
	 */
	public function __construct($conn)
	{
		parent::__construct($conn);

		$this->columnTypes['mediumtext'] = 'mediumtext';
	}

	/**
	 * @var int The maximum length that objects' names can be.
	 */
	public $maxObjectNameLength = 64;

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 *
	 * @return string
	 */
	public function addColumnFirst($table, $column, $type)
	{
		$type = $this->getColumnType($type);

		$sql = 'ALTER TABLE '.$this->quoteTableName($table)
		       .' ADD '.$this->quoteColumnName($column).' '
		       .$this->getColumnType($type).' '
		       .'FIRST';

		return $sql;
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 *
	 * @return string
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		$type = $this->getColumnType($type);

		$sql = 'ALTER TABLE '.$this->quoteTableName($table).' ADD '.$this->quoteColumnName($column).' '.$this->getColumnType($type);

		if ($after)
		{
			$sql .= ' AFTER '.$this->quoteTableName($after);
		}

		return $sql;
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 *
	 * @return string
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		$tableInfo = $this->getTable($table, true);
		$columns = array_keys($tableInfo->columns);
		$beforeIndex = array_search($before, $columns);

		if ($beforeIndex === false)
		{
			return $this->addColumn($table, $column, $type);
		}
		else if ($beforeIndex > 0)
		{
			$after = $columns[$beforeIndex-1];
			return $this->addColumnAfter($table, $column, $type, $after);
		}
		else
		{
			return $this->addColumnFirst($table, $column, $type);
		}
	}

	/**
	 * @param string $table
	 * @param string $column
	 * @param string $type
	 * @param mixed $newName
	 * @param mixed $after
	 *
	 * @return string
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		if (!$newName)
		{
			$newName = $column;
		}

		return 'ALTER TABLE '.$this->quoteTableName($table).' CHANGE '
			. $this->quoteColumnName($column).' '
			. $this->quoteColumnName($newName).' '
			. $this->getColumnType($type)
			. ($after ? ' AFTER '.$this->quoteColumnName($after) : '');
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $rows
	 *
	 * @return mixed
	 */
	public function insertAll($table, $columns, $rows)
	{
		$params = array();

		// Quote the column names
		foreach ($columns as $colIndex => $column)
		{
			$columns[$colIndex] = $this->quoteColumnName($column);
		}

		$valuesSql = '';

		foreach ($rows as $rowIndex => $row)
		{
			if ($rowIndex != 0)
			{
				$valuesSql .= ', ';
			}

			$valuesSql .= '(';

			foreach ($columns as $colIndex => $column)
			{
				if ($colIndex != 0)
				{
					$valuesSql .= ', ';
				}

				if (isset($row[$colIndex]) && $row[$colIndex] !== null)
				{
					$key = ':row'.$rowIndex.'_col'.$colIndex;
					$params[$key] = $row[$colIndex];
					$valuesSql .= $key;
				}
				else
				{
					$valuesSql .= 'NULL';
				}
			}

			$valuesSql .= ')';
		}

		// Generate the SQL
		$sql = 'INSERT INTO '.$this->quoteTableName($table).' ('.implode(', ', $columns).') VALUES '.$valuesSql;

		return array('query' => $sql, 'params' => $params);
	}

	/**
	 * @param string $table   The name of the table (including prefix, or wrapped in "{{" and "}}").
	 * @param array  $columns An array of columns.
	 * @param string $options Any additional SQL to append to the end of the query.
	 * @param string $engine  The engine the table should use ("InnoDb" or "MyISAM"). Default is "InnoDb".
	 *
	 * @return string The full SQL for creating a table.
	 */
	public function createTable($table, $columns, $options = null, $engine = 'InnoDb')
	{
		$cols = array();
		$primaryKeys = array();

		$options = 'ENGINE='.$engine.' DEFAULT CHARSET='.craft()->config->get('charset', ConfigFile::Db).' COLLATE='.craft()->config->get('collation', ConfigFile::Db).($options ? ' '.$options : '');

		foreach ($columns as $name => $type)
		{
			if (is_string($name))
			{
				$columnType = $this->getColumnType($type);

				if ($type == ColumnType::PK || strpos($columnType, ' PRIMARY KEY') !== false)
				{
					$primaryKeys[] = $name;

					// TODO: Probably MySQL specific, but no an issue in Craft 3 anyway.
					$columnType = str_replace(' PRIMARY KEY', '', $columnType);
				}

				$cols[] = "\t".$this->quoteColumnName($name).' '.$columnType;
			}
			else
			{
				$cols[] = "\t".$type;
			}
		}

		if ($primaryKeys)
		{
			$cols[] = "\tPRIMARY KEY (".implode(', ', $primaryKeys).")";
		}

		$sql = "CREATE TABLE ".$this->quoteTableName($table)." (\n".implode(",\n", $cols)."\n)";

		return $options === null ? $sql : $sql.' '.$options;
	}

	/**
	 * Builds a SQL statement for dropping a DB table if it exists.
	 *
	 * @param string $table
	 *
	 * @return string
	 */
	public function dropTableIfExists($table)
	{
		return 'DROP TABLE IF EXISTS '.$this->quoteTableName($table);
	}

	/**
	 * Returns the SQL for ordering results by column values.
	 *
	 * @param string $column
	 * @param array  $values
	 *
	 * @return string
	 */
	public function orderByColumnValues($column, $values)
	{
		$sql = 'FIELD('.$this->quoteColumnName($column);

		foreach ($values as $value)
		{
			$sql .= ', '.$this->getDbConnection()->quoteValue($value);
		}

		$sql .= ')';

		return $sql;
	}

	/**
	 * Returns the SQL for finding/replacing text.
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $find
	 * @param string $replace
	 *
	 * @return array
	 */
	public function replace($table, $column, $find, $replace)
	{
		$sql = 'UPDATE '.$this->quoteTableName($table).' SET '.$this->quoteColumnName($column).' = REPLACE('.$this->quoteColumnName($column).',  :find, :replace)';
		$params = array(':find' => (string) $find, ':replace' => (string) $replace);

		return array('query' => $sql, 'params' => $params);
	}

	/**
	 * Quotes a database name for use in a query.
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public function quoteDatabaseName($name)
	{
		return '`'.$name.'`';
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns all table names in the database which start with the tablePrefix.
	 *
	 * @param string $schema
	 *
	 * @return string
	 */
	protected function findTableNames($schema = null)
	{
		if (!$schema)
		{
			$connection = $this->getDbConnection();
			$likeSql = ($connection->tablePrefix ? ' LIKE \''.$connection->tablePrefix.'%\'' : '');
			return $connection->createCommand()->setText('SHOW TABLES'.$likeSql)->queryColumn();
		}
		else
		{
			return parent::findTableNames();
		}
	}
}
