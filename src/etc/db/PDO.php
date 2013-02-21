<?php
namespace Blocks;

/**
 * Extends \PDO, adding support for savepoints, via beginTransaction() and commitTransaction()
 * @see http://www.yiiframework.com/wiki/38/how-to-use-nested-db-transactions-mysql-5-postgresql/
 */
class PDO extends \PDO
{
	protected $supportsSavepoints;
	protected $transactionLevel = 0;

 	/**
 	 * Returns whether the DB supports savepoints.
	  *
 	 * @return bool
 	 */
	protected function getSupportsSavepoints()
	{
		return false;
		if (!isset($this->supportsSavepoints))
		{
			$driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
			$this->supportsSavepoints = in_array($driver, array('pgsql', 'mysql'));
		}
		return $this->supportsSavepoints;
	}

 	/** 
 	 * Begins the transaction, or sets a savepoint.
 	 */
	public function beginTransaction()
	{
		if ($this->transactionLevel == 0)
		{
			parent::beginTransaction();
		}
		else if ($this->getSupportsSavepoints())
		{
			$this->exec("SAVEPOINT LEVEL{$this->transactionLevel}");
		}

		$this->transactionLevel++;
	}

 	/**
 	 * Commits the transaction, or releases a savepoint.
 	 */
	public function commit()
	{
		$this->transactionLevel--;

		if ($this->transactionLevel == 0)
		{
			parent::commit();
		}
		else if ($this->getSupportsSavepoints())
		{
			$this->exec("RELEASE SAVEPOINT LEVEL{$this->transactionLevel}");
		}
	}

 	/**
 	 * Rolls back the transaction or a savepoint.
 	 */
	public function rollBack()
	{
		$this->transactionLevel--;

		if ($this->transactionLevel == 0)
		{
			parent::rollBack();
		}
		else if ($this->getSupportsSavepoints())
		{
			$this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionLevel}");
		}
	}
}
