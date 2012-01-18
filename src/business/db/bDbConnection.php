<?php

/**
 *
 */
class bDbConnection extends CDbConnection
{
	/**
	 * @param null $query
	 * @return \bDbCommand
	 */
	public function createCommand($query = null)
	{
		$this->setActive(true);
		return new bDbCommand($this, $query);
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
