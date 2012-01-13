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

	/**
	 * Returns the current transaction if it exists, or starts a new one
	 * @return CDbTransaction The transaction
	 */
	public function beginTransaction()
	{
		$transaction = $this->getCurrentTransaction();
		if ($transaction !== null)
			return $transaction;

		return parent::beginTransaction();
	}
}
