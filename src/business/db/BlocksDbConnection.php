<?php

/**
 *
 */
class BlocksDbConnection extends CDbConnection
{
	/**
	 * @param null $query
	 * @return \BlocksDbCommand
	 */
	public function createCommand($query = null)
	{
		$this->setActive(true);
		return new BlocksDbCommand($this, $query);
	}
}
