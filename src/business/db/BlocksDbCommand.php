<?php

/**
 * Extends CDbCommand
 */
class BlocksDbCommand extends CDbCommand
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
}
