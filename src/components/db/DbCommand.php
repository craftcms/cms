<?php
namespace Blocks;

/**
 * Extends CDbCommand
 */
class DbCommand extends \CDbCommand
{
	/**
	 * Adds additional select columns.
	 *
	 * @param string $columns
	 * @return DbCommand
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
	 * @return \CDbCommand
	 */
	public function from($tables)
	{
		$tables = $this->_addTablePrefix($tables);
		return parent::from($tables);
	}

	/**
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function where($conditions, $params = array())
	{
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::where($conditions, $params);
	}

	/**
	 * Adds an additional where condition.
	 *
	 * @param mixed $conditions
	 * @param array|null $params
	 * @return DbCommand
	 */
	public function addWhere($conditions, $params = array())
	{
		$oldWhere = $this->getWhere();
		if ($oldWhere)
		{
			$conditions = array('and', $oldWhere, $conditions);
		}
		return $this->where($conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function join($table, $conditions, $params = array())
	{
		$table = $this->_addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::join($table, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function leftJoin($table, $conditions, $params = array())
	{
		$table = $this->_addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::leftJoin($table, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function rightJoin($table, $conditions, $params = array())
	{
		$table = $this->_addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::rightJoin($table, $conditions, $params);
	}

	/**
	 * @param $table
	 * @return DbCommand
	 */
	public function crossJoin($table)
	{
		$table = $this->_addTablePrefix($table);
		return parent::crossJoin($table);
	}

	/**
	 * @param $table
	 * @return DbCommand
	 */
	public function naturalJoin($table)
	{
		$table = $this->_addTablePrefix($table);
		return parent::naturalJoin($table);
	}

	/**
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function having($conditions, $params = array())
	{
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::having($conditions, $params);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @return int
	 */
	public function insert($table, $columns)
	{
		$table = $this->_addTablePrefix($table);

		$columns['dateCreated'] = DateTimeHelper::currentTime();
		$columns['dateUpdated'] = DateTimeHelper::currentTime();
		$columns['uid'] = StringHelper::UUID();

		return parent::insert($table, $columns);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @return int
	 */
	public function insertAll($table, $columns, $vals)
	{
		$table = $this->_addTablePrefix($table);

		$columns[] = 'dateCreated';
		$columns[] = 'dateUpdated';
		$columns[] = 'uid';

		foreach ($vals as &$val)
		{
			$val[] = DateTimeHelper::currentTime();
			$val[] = DateTimeHelper::currentTime();
			$val[] = StringHelper::UUID();
		}

		$queryParams = $this->getConnection()->getSchema()->insertAll($table, $columns, $vals);
		return $this->setText($queryParams['query'])->execute($queryParams['params']);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param string $conditions
	 * @param array $params
	 * @return int
	 */
	public function update($table, $columns, $conditions = '', $params = array())
	{
		$table = $this->_addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);

		$columns['dateUpdated'] = DateTimeHelper::currentTime();

		return parent::update($table, $columns, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed $conditions
	 * @param array
	 * @return int
	 */
	public function delete($table, $conditions = '', $params = array())
	{
		$table = $this->_addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::delete($table, $conditions, $params);
	}

	/**
	 * Adds `id`, `dateCreated`, `date_update`, and `uid` columns to $columns,
	 * packages up the column definitions into strings,
	 * and then passes it back to CDbCommand->createTable()
	 *
	 * @param      $table
	 * @param      $columns
	 * @param null $options
	 * @return int
	 */
	public function createTable($table, $columns, $options=null)
	{
		$table = $this->_addTablePrefix($table);

		$columns = array_merge(
			array('id' => ColumnType::PK),
			$columns,
			($table !== 'activity' ? DbHelper::getAuditColumnConfig() : array())
		);

		foreach ($columns as $col => $settings)
		{
			$columns[$col] = DbHelper::generateColumnDefinition($settings);
		}

		// Create the table
		$return = parent::createTable($table, $columns, $options);

		return $return;
	}

	/**
	 * @param $table
	 * @param $newName
	 * @return int
	 */
	public function renameTable($table, $newName)
	{
		$table = $this->_addTablePrefix($table);
		$newName = $this->_addTablePrefix($newName);
		return parent::renameTable($table, $newName);
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function dropTable($table)
	{
		$table = $this->_addTablePrefix($table);
		return parent::dropTable($table);
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function truncateTable($table)
	{
		$table = $this->_addTablePrefix($table);
		return parent::truncateTable($table);
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
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
	 * @return mixed
	 */
	public function addColumnFirst($table, $column, $type)
	{
		$table = $this->_addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnFirst($table, $column, $type))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 * @return mixed
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		$table = $this->_addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnBefore($table, $column, $type, $before))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 * @return mixed
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		$table = $this->_addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnAfter($table, $column, $type, $after))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @return int
	 */
	public function dropColumn($table, $column)
	{
		$table = $this->_addTablePrefix($table);
		return parent::dropColumn($table, $column);
	}

	/**
	 * @param $table
	 * @param $name
	 * @param $newName
	 * @return int
	 */
	public function renameColumn($table, $name, $newName)
	{
		$table = $this->_addTablePrefix($table);
		return parent::renameColumn($table, $name, $newName);
	}

	/**
	 * @param      $table
	 * @param      $column
	 * @param      $type
	 * @param null $newName
	 * @param      $after
	 * @return int
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		$table = $this->_addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->alterColumn($table, $column, $type, $newName, $after))->execute();
	}

	/**
	 * @param $name
	 * @param $table
	 * @param $columns
	 * @param $refTable
	 * @param $refColumns
	 * @param null $delete
	 * @param null $update
	 * @return int
	 */
	public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete=null, $update=null)
	{
		$name = md5($this->_addTablePrefix($name));
		$table = $this->_addTablePrefix($table);
		$refTable = $this->_addTablePrefix($refTable);
		return parent::addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
	}

	/**
	 * @param $name
	 * @param $table
	 * @return int
	 */
	public function dropForeignKey($name, $table)
	{
		$name = md5($this->_addTablePrefix($name));
		$table = $this->_addTablePrefix($table);
		return parent::dropForeignKey($name, $table);
	}

	/**
	 * @param $name
	 * @param $table
	 * @param $column
	 * @param bool $unique
	 * @return int
	 */
	public function createIndex($name, $table, $column, $unique=false)
	{
		$name = md5($this->_addTablePrefix($name));
		$table = $this->_addTablePrefix($table);
		return parent::createIndex($name, $table, $column, $unique);
	}

	/**
	 * @param $name
	 * @param $table
	 * @return int
	 */
	public function dropIndex($name, $table)
	{
		$name = md5($this->_addTablePrefix($name));
		$table = $this->_addTablePrefix($table);
		return parent::dropIndex($name, $table);
	}

	/**
	 * Prepares a table name for Yii to add its table prefix
	 *
	 * @param mixed $table The table name or an array of table names
	 * @return mixed The modified table name(s)
	 */
	private function _addTablePrefix($table)
	{
		if (is_array($table))
		{
			foreach ($table as &$t)
			{
				$t = $this->_addTablePrefix($t);
			}
		}
		else
		{
			$table = preg_replace('/^\w+/', blx()->db->tablePrefix.'\0', $table);
		}

		return $table;
	}

	/**
	 * Adds support for array('column' => 'value') conditional syntax.
	 * Supports nested conditionals, e.g. array('or', array('column' => 'value'), array('column2' => 'value2'))
	 *
	 * @param mixed $conditions
	 * @param array &$params
	 * @return mixed
	 */
	private function _normalizeConditions($conditions, &$params = array())
	{
		if (!is_array($conditions))
		{
			return $conditions;
		}
		else if ($conditions === array())
		{
			return '';
		}

		$normalizedConditions = array();

		// Find any key/value pairs and convert them to the CDbCommand's conditional syntax
		foreach ($conditions as $key => $value)
		{
			if (!is_numeric($key))
			{
				$param = ':p'.StringHelper::randomString(9);
				$normalizedConditions[] = $key.'='.$param;
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
				if (strtolower($conditions[0]) == 'and')
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
