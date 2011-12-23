<?php

class BlocksDbCommand extends CDbCommand
{
	public function addColumnAfter($table, $column, $type, $after)
	{
		return $this->setText($this->getConnection()->getSchema()->addColumnAfter($table, $column, $type, $after))->execute();
	}
}
