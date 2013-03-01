<?php
namespace Craft;

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

		if (craft()->config->get('devMode'))
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

	/**
	 * @return bool|string
	 */
	public function backup()
	{
		$backup = new DbBackup();
		if (($backupFile = $backup->run()) !== false)
		{
			return $backupFile;
		}

		return false;
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function quoteDatabaseName($name)
	{
		return $this->getSchema()->quoteTableName($name);
	}

	/**
	 * Returns whether a table exists.
	 *
	 * @param string $table
	 * @param bool $refresh
	 * @return bool
	 */
	public function tableExists($table, $refresh = false)
	{
		// Always refresh the schema cache if Craft isn't installed yet
		if ($refresh || !craft()->isInstalled)
		{
			$this->getSchema()->refresh();
		}

		$table = DbHelper::addTablePrefix($table);
		return in_array($table, $this->getSchema()->getTableNames());
	}
}
