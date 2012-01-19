<?php

/**
 * Extends CDbCommand
 */
class bDbCommand extends CDbCommand
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
}
