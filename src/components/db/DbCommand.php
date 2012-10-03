<?php
namespace Blocks;

/**
 * Extends CDbCommand
 */
class DbCommand extends \CDbCommand
{
	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @return mixed
	 */
	public function addColumnFirst($table, $column, $type)
	{
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnFirst($this->_addTablePrefix($table), $column, $type))->execute();
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
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnAfter($this->_addTablePrefix($table), $column, $type, $after))->execute();
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
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnBefore($this->_addTablePrefix($table), $column, $type, $before))->execute();
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @return int
	 */
	public function insertAll($table, $columns, $vals)
	{
		$columns[] = 'dateCreated';
		$columns[] = 'dateUpdated';
		$columns[] = 'uid';

		foreach ($vals as &$val)
		{
			$val[] = DateTimeHelper::currentTime();
			$val[] = DateTimeHelper::currentTime();
			$val[] = StringHelper::UUID();
		}

		$queryParams = $this->getConnection()->getSchema()->insertAll($this->_addTablePrefix($table), $columns, $vals);
		return $this->setText($queryParams['query'])->execute($queryParams['params']);
	}

	/**
	 * @param $tables
	 * @return \CDbCommand
	 */
	public function from($tables)
	{
		return parent::from($this->_addTablePrefix($tables));
	}

	/**
	 * @param $table
	 * @param $conditions
	 * @param array $params
	 * @return \CDbCommand
	 */
	public function join($table, $conditions, $params=array())
	{
		return parent::join($this->_addTablePrefix($table), $conditions, $params);
	}

	/**
	 * @param $table
	 * @param $conditions
	 * @param array $params
	 * @return \CDbCommand
	 */
	public function leftJoin($table, $conditions, $params=array())
	{
		return parent::leftJoin($this->_addTablePrefix($table), $conditions, $params);
	}

	/**
	 * @param $table
	 * @param $conditions
	 * @param array $params
	 * @return \CDbCommand
	 */
	public function rightJoin($table, $conditions, $params=array())
	{
		return parent::rightJoin($this->_addTablePrefix($table), $conditions, $params);
	}

	/**
	 * @param $table
	 * @return \CDbCommand
	 */
	public function crossJoin($table)
	{
		return parent::crossJoin($this->_addTablePrefix($table));
	}

	/**
	 * @param $table
	 * @return \CDbCommand
	 */
	public function naturalJoin($table)
	{
		return parent::naturalJoin($this->_addTablePrefix($table));
	}

	/**
	 * @param $table
	 * @param $columns
	 * @return int
	 */
	public function insert($table, $columns)
	{
		$columns['dateCreated'] = DateTimeHelper::currentTime();
		$columns['dateUpdated'] = DateTimeHelper::currentTime();
		$columns['uid'] = StringHelper::UUID();

		return parent::insert($this->_addTablePrefix($table), $columns);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param string $conditions
	 * @param array $params
	 * @return int
	 */
	public function update($table, $columns, $conditions='', $params = array())
	{
		$columns['dateUpdated'] = DateTimeHelper::currentTime();
		return parent::update($this->_addTablePrefix($table), $columns, $conditions, $params);
	}

	/**
	 * @param $table
	 * @param string $conditions
	 * @param array $params
	 * @return int
	 */
	public function delete($table, $conditions='', $params = array())
	{
		return parent::delete($this->_addTablePrefix($table), $conditions, $params);
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
		$return = parent::createTable($this->_addTablePrefix($table), $columns, $options);

		return $return;
	}

	/**
	 * @param $table
	 * @param $newName
	 * @return int
	 */
	public function renameTable($table, $newName)
	{
		return parent::renameTable($this->_addTablePrefix($table), $this->_addTablePrefix($newName));
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function dropTable($table)
	{
		return parent::dropTable($this->_addTablePrefix($table));
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function truncateTable($table)
	{
		return parent::truncateTable($this->_addTablePrefix($table));
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @return mixed
	 */
	public function addColumn($table, $column, $type)
	{
		$type = DbHelper::generateColumnDefinition($type);
		return $this->addColumnBefore($table, $column, $type, 'dateCreated');
	}

	/**
	 * @param $table
	 * @param $column
	 * @return int
	 */
	public function dropColumn($table, $column)
	{
		return parent::dropColumn($this->_addTablePrefix($table), $column);
	}

	/**
	 * @param $table
	 * @param $name
	 * @param $newName
	 * @return int
	 */
	public function renameColumn($table, $name, $newName)
	{
		return parent::renameColumn($this->_addTablePrefix($table), $name, $newName);
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
		return parent::addForeignKey($name, $this->_addTablePrefix($table), $columns, $this->_addTablePrefix($refTable), $refColumns, $delete, $update);
	}

	/**
	 * @param $name
	 * @param $table
	 * @return int
	 */
	public function dropForeignKey($name, $table)
	{
		$name = md5($this->_addTablePrefix($name));
		return parent::dropForeignKey($name, $this->_addTablePrefix($table));
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
		return parent::createIndex($name, $this->_addTablePrefix($table), $column, $unique);
	}

	/**
	 * @param $name
	 * @param $table
	 * @return int
	 */
	public function dropIndex($name, $table)
	{
		$name = md5($this->_addTablePrefix($name));
		return parent::dropIndex($name, $this->getTableName($table));
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
}
