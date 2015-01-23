<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use craft\app\enums\ColumnType;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\DbHelper;
use craft\app\helpers\StringHelper;

/**
 * Class Command
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Command extends \yii\db\Command
{
	// Properties
	// =========================================================================

	/**
	 * Captures the joined tables.
	 *
	 * @var array
	 */
	private $_joinedTables = [];

	// Public Methods
	// =========================================================================

	/**
	 * Returns the tables that have been joined.
	 *
	 * @return array
	 */
	public function getJoinedTables()
	{
		return $this->_joinedTables;
	}

	/**
	 * Returns whether a given table has been joined in this query.
	 *
	 * @param string $table
	 *
	 * @return bool
	 */
	public function isJoined($table)
	{
		return in_array($table, $this->_joinedTables);
	}

	/**
	 * Returns the total number of rows matched by the query.
	 *
	 * @param string $column The column to count.
	 *
	 * @return int The total number of rows matched by the query.
	 */
	public function count($column)
	{
		if (is_object($column))
		{
			$column = (string) $column;
		}
		else if (!StringHelper::contains($column, '('))
		{
			if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $column, $matches))
			{
				$column = $this->db->quoteColumnName($matches[1]).' AS '.$this->db->quoteColumnName($matches[2]);
			}
			else
			{
				$column = $this->db->quoteColumnName($column);
			}
		}

		return (int) $this->select("count({$column})")->queryScalar();
	}

	/**
	 * Adds additional select columns.
	 *
	 * @param string $columns
	 *
	 * @return Command
	 */
	public function addSelect($columns = '*')
	{
		$oldSelect = $this->getSelect();

		if ($oldSelect)
		{
			$columns = str_replace('`', '', $oldSelect).','.$columns;
		}

		$this->setSelect($columns);

		return $this;
	}

	/**
	 * @param $tables
	 *
	 * @return Command
	 */
	public function from($tables)
	{
		$tables = $this->db->addTablePrefix($tables);

		return parent::from($tables);
	}

	/**
	 * @param mixed $conditions
	 * @param array $params
	 *
	 * @return Command
	 */
	public function where($conditions, $params = [])
	{
		if (!$conditions)
		{
			return $this;
		}

		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::where($conditions, $params);
	}

	/**
	 * Adds an additional "and where" condition.
	 *
	 * @param mixed      $conditions
	 * @param array|null $params
	 *
	 * @return Command
	 */
	public function andWhere($conditions, $params = [])
	{
		if (!$conditions)
		{
			return $this;
		}

		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::andWhere($conditions, $params);
	}

	/**
	 * Adds an additional "or where" condition.
	 *
	 * @param mixed      $conditions
	 * @param array|null $params
	 *
	 * @return Command
	 */
	public function orWhere($conditions, $params = [])
	{
		if (!$conditions)
		{
			return $this;
		}

		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::orWhere($conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed  $conditions
	 * @param array  $params
	 *
	 * @return Command
	 */
	public function join($table, $conditions, $params = [])
	{
		$this->_addJoinedTable($table);
		$table = $this->db->addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::join($table, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed  $conditions
	 * @param array  $params
	 *
	 * @return Command
	 */
	public function leftJoin($table, $conditions, $params = [])
	{
		$this->_addJoinedTable($table);
		$table = $this->db->addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::leftJoin($table, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed  $conditions
	 * @param array  $params
	 *
	 * @return Command
	 */
	public function rightJoin($table, $conditions, $params = [])
	{
		$this->_addJoinedTable($table);
		$table = $this->db->addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::rightJoin($table, $conditions, $params);
	}

	/**
	 * @param $table
	 *
	 * @return Command
	 */
	public function crossJoin($table)
	{
		$this->_addJoinedTable($table);
		$table = $this->db->addTablePrefix($table);

		return parent::crossJoin($table);
	}

	/**
	 * @param $table
	 *
	 * @return Command
	 */
	public function naturalJoin($table)
	{
		$this->_addJoinedTable($table);
		$table = $this->db->addTablePrefix($table);

		return parent::naturalJoin($table);
	}

	/**
	 * @param mixed $conditions
	 * @param array $params
	 *
	 * @return Command
	 */
	public function having($conditions, $params = [])
	{
		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::having($conditions, $params);
	}

	/**
	 * @param mixed $columns
	 *
	 * @return Command
	 */
	public function addOrder($columns)
	{
		$oldOrder = $this->getOrder();

		if ($oldOrder)
		{
			return $this->order([$oldOrder, $columns]);
		}
		else
		{
			return $this->order($columns);
		}
	}

	/**
	 * @param string $table
	 * @param array  $columns
	 * @param bool   $includeAuditColumns
	 *
	 * @return int
	 */
	public function insert($table, $columns, $includeAuditColumns = true)
	{
		$table = $this->db->addTablePrefix($table);

		if ($includeAuditColumns)
		{
			$columns['dateCreated'] = DateTimeHelper::currentTimeForDb();
			$columns['dateUpdated'] = DateTimeHelper::currentTimeForDb();
			$columns['uid']         = StringHelper::UUID();
		}

		return parent::insert($table, $columns);
	}

	/**
	 * @param string $table
	 * @param array  $columns
	 * @param array  $rows
	 * @param bool   $includeAuditColumns
	 *
	 * @return int
	 */
	public function insertAll($table, $columns, $rows, $includeAuditColumns = true)
	{
		if (!$rows)
		{
			return 0;
		}

		$table = $this->db->addTablePrefix($table);

		if ($includeAuditColumns)
		{
			$columns[] = 'dateCreated';
			$columns[] = 'dateUpdated';
			$columns[] = 'uid';

			foreach ($rows as &$row)
			{
				$row[] = DateTimeHelper::currentTimeForDb();
				$row[] = DateTimeHelper::currentTimeForDb();
				$row[] = StringHelper::UUID();
			}
		}

		$queryParams = $this->db->getSchema()->insertAll($table, $columns, $rows);

		return $this->setText($queryParams['query'])->execute($queryParams['params']);
	}

	/**
	 * @param string $table
	 * @param array  $keyColumns
	 * @param array  $updateColumns
	 * @param bool   $includeAuditColumns
	 *
	 * @return int
	 */
	public function insertOrUpdate($table, $keyColumns, $updateColumns, $includeAuditColumns = true)
	{
		if ($includeAuditColumns)
		{
			$keyColumns['dateCreated']    = DateTimeHelper::currentTimeForDb();
			$keyColumns['uid']            = StringHelper::UUID();
			$updateColumns['dateUpdated'] = DateTimeHelper::currentTimeForDb();
		}

		// TODO: This is all MySQL specific

		$allColumns = array_merge($keyColumns, $updateColumns);
		$params = [];

		$table = $this->db->addTablePrefix($table);
		$sql = 'INSERT INTO '.$this->db->quoteTableName($table).' (';

		foreach (array_keys($allColumns) as $i => $column)
		{
			if ($i > 0)
			{
				$sql .= ', ';
			}

			$sql .= $this->db->quoteColumnName($column);

			$params[':'.$column] = $allColumns[$column];
		}

		$sql .= ') VALUES (:'.implode(', :', array_keys($allColumns)).')' .
		        ' ON DUPLICATE KEY UPDATE ';

		foreach (array_keys($updateColumns) as $i => $column)
		{
			if ($i > 0)
			{
				$sql .= ', ';
			}

			$sql .= $this->db->quoteColumnName($column).' = :'.$column;
		}

		return $this->setText($sql)->execute($params);
	}

	/**
	 * @param string $table
	 * @param array  $columns
	 * @param mixed  $conditions
	 * @param array  $params
	 * @param bool   $includeAuditColumns
	 *
	 * @return int
	 */
	public function update($table, $columns, $conditions = '', $params = [], $includeAuditColumns = true)
	{
		$table = $this->db->addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);

		if ($includeAuditColumns)
		{
			$columns['dateUpdated'] = DateTimeHelper::currentTimeForDb();
		}

		return parent::update($table, $columns, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param string $column
	 * @param string $find
	 * @param string $replace
	 *
	 * @return int
	 */
	public function replace($table, $column, $find, $replace)
	{
		$table = $this->db->addTablePrefix($table);
		$queryParams = $this->db->getSchema()->replace($table, $column, $find, $replace);

		return $this->setText($queryParams['query'])->execute($queryParams['params']);
	}

	/**
	 * @param string $table
	 * @param mixed  $conditions
	 * @param array  $params
	 *
	 * @return int
	 */
	public function delete($table, $conditions = '', $params = [])
	{
		$table = $this->db->addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);

		return parent::delete($table, $conditions, $params);
	}

	/**
	 * Adds `id`, `dateCreated`, `date_update`, and `uid` columns to $columns, packages up the column definitions into
	 * strings, and then passes it back to CDbCommand->createTable().
	 *
	 * @param string $table
	 * @param array  $columns
	 * @param null   $options
	 * @param bool   $addIdColumn
	 * @param bool   $addAuditColumns
	 *
	 * @return int
	 */
	public function createTable($table, $columns, $options = null, $addIdColumn = true, $addAuditColumns = true)
	{
		$table = $this->db->addTablePrefix($table);

		$columns = array_merge(
			($addIdColumn ? ['id' => ColumnType::PK] : []),
			$columns,
			($addAuditColumns ? DbHelper::getAuditColumnConfig() : [])
		);

		foreach ($columns as $col => $settings)
		{
			$columns[$col] = DbHelper::generateColumnDefinition($settings);
		}

		// Create the table
		return parent::createTable($table, $columns, $options);
	}

	/**
	 * @param $table
	 * @param $newName
	 *
	 * @return int
	 */
	public function renameTable($table, $newName)
	{
		$table = $this->db->addTablePrefix($table);
		$newName = $this->db->addTablePrefix($newName);

		return parent::renameTable($table, $newName);
	}

	/**
	 * @param $table
	 *
	 * @return int
	 */
	public function dropTable($table)
	{
		$table = $this->db->addTablePrefix($table);

		return parent::dropTable($table);
	}

	/**
	 * @param $table
	 *
	 * @return int
	 */
	public function dropTableIfExists($table)
	{
		$table = $this->db->addTablePrefix($table);
		$sql = $this->db->getSchema()->dropTableIfExists($table);

		return $this->setText($sql)->execute();
	}

	/**
	 * @param $table
	 *
	 * @return int
	 */
	public function truncateTable($table)
	{
		$table = $this->db->addTablePrefix($table);

		return parent::truncateTable($table);
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 *
	 * @return mixed
	 */
	public function addColumn($table, $column, $type)
	{
		// Keep new columns before the dateCreated audit column
		return $this->addColumnBefore($table, $column, $type, 'dateCreated');
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 *
	 * @return mixed
	 */
	public function addColumnFirst($table, $column, $type)
	{
		$table = $this->db->addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);

		return $this->setText($this->db->getSchema()->addColumnFirst($table, $column, $type))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 *
	 * @return mixed
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		$table = $this->db->addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);

		return $this->setText($this->db->getSchema()->addColumnBefore($table, $column, $type, $before))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 *
	 * @return mixed
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		$table = $this->db->addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);

		return $this->setText($this->db->getSchema()->addColumnAfter($table, $column, $type, $after))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 *
	 * @return int
	 */
	public function dropColumn($table, $column)
	{
		$table = $this->db->addTablePrefix($table);

		return parent::dropColumn($table, $column);
	}

	/**
	 * @param $table
	 * @param $name
	 * @param $newName
	 *
	 * @return int
	 */
	public function renameColumn($table, $name, $newName)
	{
		$table = $this->db->addTablePrefix($table);

		return parent::renameColumn($table, $name, $newName);
	}

	/**
	 * @param      $table
	 * @param      $column
	 * @param      $type
	 * @param null $newName
	 * @param null $after
	 *
	 * @return int
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		$table = $this->db->addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);

		return $this->setText($this->db->getSchema()->alterColumn($table, $column, $type, $newName, $after))->execute();
	}

	/**
	 * @param      $table
	 * @param      $columns
	 * @param bool $unique
	 *
	 * @return int
	 */
	public function createIndex($table, $columns, $unique = false)
	{
		$name = $this->db->getIndexName($table, $columns, $unique);
		$table = $this->db->addTablePrefix($table);

		return parent::createIndex($name, $table, $columns, $unique);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @param bool   $unique
	 *
	 * @return int
	 */
	public function dropIndex($table, $columns, $unique = false)
	{
		$name = $this->db->getIndexName($table, $columns, $unique);
		$table = $this->db->addTablePrefix($table);

		return parent::dropIndex($name, $table);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 *
	 * @return int
	 */
	public function addPrimaryKey($table, $columns)
	{
		$name = $this->db->getPrimaryKeyName($table, $columns);
		$table = $this->db->addTablePrefix($table);

		return parent::addPrimaryKey($name, $table, $columns);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 *
	 * @return int
	 */
	public function dropPrimaryKey($table, $columns)
	{
		$name = $this->db->getPrimaryKeyName($table, $columns);
		$table = $this->db->addTablePrefix($table);

		return parent::dropPrimaryKey($name, $table);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Adds a table to our record of joined tables.
	 *
	 * @param string $table The table name
	 *
	 * @return bool
	 */
	private function _addJoinedTable($table)
	{
		// If there's an alias set, use the alias rather than the "real" table name.
		$parts = explode(' ', $table);

		if (count($parts) == 1)
		{
			$table = $parts[0];
		}
		else
		{
			$table = $parts[1];
		}

		// Don't add any backticks or whatever
		if (preg_match('/\w+/', $table, $matches))
		{
			$this->_joinedTables[] = $matches[0];
		}
	}

	/**
	 * Adds support for ['column' => 'value'] conditional syntax. Supports nested conditionals, e.g.
	 * ['or', ['column' => 'value'], ['column2' => 'value2']]
	 *
	 * @param mixed $conditions
	 * @param array &$params
	 *
	 * @return mixed
	 */
	private function _normalizeConditions($conditions, &$params = [])
	{
		if (!is_array($conditions))
		{
			return $conditions;
		}
		else if ($conditions === [])
		{
			return '';
		}

		$normalizedConditions = [];

		// Find any key/value pairs and convert them to the CDbCommand's conditional syntax
		foreach ($conditions as $key => $value)
		{
			if (!is_numeric($key))
			{
				$param = ':p'.StringHelper::randomString(9);
				$normalizedConditions[] = $this->db->quoteColumnName($key).'='.$param;
				$params[$param] = $value;
				unset($conditions[$key]);
			}
			else
			{
				$conditions[$key] = $this->_normalizeConditions($value, $params);
			}
		}

		if ($normalizedConditions)
		{
			// Were there normal conditions in there as well?
			if ($conditions)
			{
				// Is this already an AND conditional?
				if (StringHelper::toLowerCase($conditions[0]) == 'and')
				{
					// Just merge our normalized conditions into the $conditions
					$conditions = array_merge($conditions, $normalizedConditions);
				}
				else
				{
					// Append the normalized conditions as nested AND conditions
					array_unshift($normalizedConditions, 'and');
					$conditions[] = $normalizedConditions;
				}
			}
			else
			{
				if (count($normalizedConditions) == 1)
				{
					$conditions = $normalizedConditions[0];
				}
				else
				{
					array_unshift($normalizedConditions, 'and');
					$conditions = $normalizedConditions;
				}
			}
		}

		return $conditions;
	}
}
