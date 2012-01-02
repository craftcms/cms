<?php

class BlocksDbCommand extends CDbCommand
{
	public function addColumnAfter($table, $column, $type, $after)
	{
		return $this->setText($this->connection->schema->addColumnAfter($table, $column, $type, $after))->execute();
	}
}
