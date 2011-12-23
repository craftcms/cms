<?php

class BlocksDbConnection extends CDbConnection
{
	public function createCommand($query = null)
	{
		$this->setActive(true);
		return new BlocksDbCommand($this, $query);
	}
}
