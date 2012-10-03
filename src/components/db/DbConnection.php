<?php
namespace Blocks;

/**
 *
 */
class DbConnection extends \CDbConnection
{
	/**
	 *
	 */
	public function init()
	{
		parent::init();

		if (blx()->config->devMode)
		{
			$this->enableProfiling = true;
			$this->enableParamLogging = true;
		}
	}

	/**
	 * @param null $query
	 * @return DbCommand
	 */
	public function createCommand($query = null)
	{
		$this->setActive(true);
		return new DbCommand($this, $query);
	}
}
