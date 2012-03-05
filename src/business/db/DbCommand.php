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
		return $this->setText($this->connection->schema->addColumnAfter($table, $column, $type, $after))->execute();
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @return int
	 */
	public function insertAll($table, $columns, $vals)
	{
		$queryParams = $this->connection->schema->insertAll($table, $columns, $vals);
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

	/**
	 * Adds `id`, `date_created`, `date_update`, and `uid` columns to $columns,
	 * packages up the column definitions into strings,
	 * and then passes it back to CDbCommand->createTable()
	 */
	public function createTable($table, $columns, $options=null)
	{
		// Make sure that the table doesn't already exist
		//if ($connection->schema->getTable('{{'.$tableName.'}}') !== null)
		//	throw new Exception($tableName.' already exists.');

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
		$return = parent::createTable('{{'.$table.'}}', $columns, $options);

		// Add the INSERT and UPDATE triggers
		DatabaseHelper::createInsertAuditTrigger($table);
		DatabaseHelper::createUpdateAuditTrigger($table);

		return $return;
	}
}
