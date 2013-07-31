<?php
namespace Craft;

/**
 *
 */
class DbConnection extends \CDbConnection
{
	private $_isDbConnectionValid = false;

	/**
	 *
	 */
	public function init()
	{
		try
		{
			parent::init();
		}
		// Most likely missing PDO in general or the specific database PDO driver.
		catch(\CDbException $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);
			$missingPdo = false;

			// TODO: Multi-db driver check.
			if (!extension_loaded('pdo'))
			{
				$missingPdo = true;
				$messages[] = Craft::t('Craft requires the PDO extension to operate.');
			}

			if (!extension_loaded('pdo_mysql'))
			{
				$missingPdo = true;
				$messages[] = Craft::t('Craft requires the PDO_MYSQL driver to operate.');
			}

			if (!$missingPdo)
			{
				Craft::log($e->getMessage(), LogLevel::Error);
				$messages[] = Craft::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
			}
		}
		catch (\Exception $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);
			$messages[] = Craft::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
		}

		if (!empty($messages))
		{
			throw new DbConnectException(Craft::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
		}

		$this->_isDbConnectionValid = true;

		// Now that we've validated the config and connection, set extra db logging if devMode is enabled.
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
	public function tableExists($table, $refresh = null)
	{
		// Default to refreshing the tables if Craft isn't installed yet
		if ($refresh || ($refresh === null && !Craft::isInstalled()))
		{
			$this->getSchema()->refresh();
		}

		$table = DbHelper::addTablePrefix($table);
		return in_array($table, $this->getSchema()->getTableNames());
	}

	/**
	 * @return bool
	 */
	public function isDbConnectionValid()
	{
		return $this->_isDbConnectionValid;
	}
}
