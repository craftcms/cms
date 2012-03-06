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
	 * @param $after
	 * @return mixed
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		return $this->setText($this->connection->schema->addColumnAfter($this->addTablePrefix($table), $column, $type, $after))->execute();
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @return int
	 */
	public function insertAll($table, $columns, $vals)
	{
		$queryParams = $this->connection->schema->insertAll($this->addTablePrefix($table), $columns, $vals);
		return $this->setText($queryParams['query'])->execute($queryParams['params']);
	}

	/**
	 * @return int
	 */
	public function getUUID()
	{
		$result = $this->setText($this->connection->schema->getUUID())->queryRow();
		return $result['UUID'];
	}

	public function from($tables)
	{
		return parent::from($this->addTablePrefix($tables));
	}

	public function join($table, $conditions, $params=array())
	{
		return parent::join($this->addTablePrefix($table), $conditions, $params);
	}

	public function leftJoin($table, $conditions, $params=array())
	{
		return parent::leftJoin($this->addTablePrefix($table), $conditions, $params);
	}

	public function rightJoin($table, $conditions, $params=array())
	{
		return parent::rightJoin($this->addTablePrefix($table), $conditions, $params);
	}

	public function crossJoin($table)
	{
		return parent::crossJoin($this>addTablePrefix($table));
	}

	public function naturalJoin($table)
	{
		return parent::naturalJoin($this->addTablePrefix($table));
	}

	public function insert($table, $columns)
	{
		return parent::insert($this->addTablePrefix($table), $columns);
	}

	public function update($table, $columns, $conditions='', $params=array())
	{
		return parent::update($this->addTablePrefix($table), $columns, $conditions, $params);
	}

	public function delete($table, $conditions='', $params=array())
	{
		return parent::delete($this->addTablePrefix($table), $conditions, $params);
	}

	/**
	 * Adds `id`, `date_created`, `date_update`, and `uid` columns to $columns,
	 * packages up the column definitions into strings,
	 * and then passes it back to CDbCommand->createTable()
	 */
	public function createTable($table, $columns, $options=null)
	{
		$columns = array_merge(
			array('id' => AttributeType::PK),
			$columns,
			array(
				'date_created' => array('type' => AttributeType::Int, 'required' => true),
				'date_updated' => array('type' => AttributeType::Int, 'required' => true),
				'uid'          => array('type' => AttributeType::Char, 'maxLength' => 36, 'required' => true)
			)
		);

		foreach ($columns as $col => $settings)
		{
			$columns[$col] = DatabaseHelper::generateColumnDefinition($settings);
		}

		// Create the table
		$return = parent::createTable($this->addTablePrefix($table), $columns, $options);

		// Add the INSERT and UPDATE triggers
		DatabaseHelper::createInsertAuditTrigger($table);
		DatabaseHelper::createUpdateAuditTrigger($table);

		return $return;
	}

	public function renameTable($table, $newName)
	{
		return parent::renameTable($this->addTablePrefix($table), $newName);
	}

	public function dropTable($table)
	{
		return parent::dropTable($this->addTablePrefix($table));
	}

	public function truncateTable($table)
	{
		return parent::truncateTable($this->addTablePrefix($table));
	}

	public function addColumn($table, $column, $type)
	{
		return parent::addColumn($this->addTablePrefix($table), $column, $type);
	}

	public function dropColumn($table, $column)
	{
		return parent::dropColumn($this->addTablePrefix($table), $column);
	}

	public function renameColumn($table, $name, $newName)
	{
		return parent::renameColumn($this->addTablePrefix($table), $name, $newName);
	}

	public function alterColumn($table, $column, $type)
	{
		return parent::alterColumn($this->addTablePrefix($table), $columns, $type);
	}

	public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete=null, $update=null)
	{
		return parent::addForeignKey($name, $this->addTablePrefix($table), $columns, $this->addTablePrefix($refTable), $refColumns, $delete, $update);
	}

	public function dropForeignKey($name, $table)
	{
		return parent::dropForeignKey($name, $this->addTablePrefix($table));
	}

	public function createIndex($name, $table, $column, $unique=false)
	{
		return parent::createIndex($name, $this->addTablePrefix($table), $column, $unique);
	}

	public function dropIndex($name, $table)
	{
		return parent::dropIndex($name, $this->getTableName($table));
	}

	/**
	 * Prepares a table name for Yii to add its table prefix
	 * @param mixed $table The table name or an array of table names
	 * @return mixed The modified table name(s)
	 */
	private function addTablePrefix($table)
	{
		if (is_array($table))
		{
			foreach ($table as &$t)
			{
				$t = $this->addTablePrefix($t);
			}
		}
		else
			$table = preg_replace('/^\w+/', b()->config->tablePrefix.'\0', $table);

		return $table;
	}
}
