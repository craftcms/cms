<?php

/**
 *
 */
class BlocksDbConnection extends CDbConnection
{
	/**
	 * @access public
	 *
	 * @param null $query
	 *
	 * @return \BlocksDbCommand
	 */
	public function createCommand($query = null)
	{
		$this->setActive(true);
		return new BlocksDbCommand($this, $query);
	}
}
