<?php
namespace Craft;

/**
 * Class DbConnection
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.db
 * @since     1.0
 */
class DbConnection extends \CDbConnection
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Initializes the DbConnection (`craft()->db`) component.
	 *
	 * This method is required by {@link IApplicationComponent} and is invoked by Craft when the `craft()-db` is first used.
	 *
	 * This method does it's best to make sure it can connect to the database with the supplied credentials and configurations and
	 * gracefully handle the cases where it can't.
	 *
	 * @throws DbConnectException
	 * @return null
	 */
	public function init()
	{
		try
		{
			$this->connectionString = $this->_processConnectionString();
			$this->emulatePrepare   = true;
			$this->username         = craft()->config->get('user', ConfigFile::Db);
			$this->password         = craft()->config->get('password', ConfigFile::Db);
			$this->charset          = craft()->config->get('charset', ConfigFile::Db);
			$this->tablePrefix      = $this->getNormalizedTablePrefix();

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
				$messages[] = Craft::t('Craft can’t connect to the database with the credentials in craft/config/db.php.');
			}
		}
		catch (\Exception $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);
			$messages[] = Craft::t('Craft can’t connect to the database with the credentials in craft/config/db.php.');
		}

		if (!empty($messages))
		{
			throw new DbConnectException(Craft::t('{errors}', array('errors' => implode('<br />', $messages))));
		}

		craft()->setIsDbConnectionValid(true);

		// Now that we've validated the config and connection, set extra db logging if devMode is enabled.
		if (craft()->config->get('devMode'))
		{
			$this->enableProfiling = true;
			$this->enableParamLogging = true;
		}
	}

	/**
	 * @param null $query
	 *
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
	 *
	 * @return string
	 */
	public function quoteDatabaseName($name)
	{
		return $this->getSchema()->quoteTableName($name);
	}

	/**
	 * Returns whether a table exists.
	 *
	 * @param string      $table
	 * @param bool|null   $refresh
	 *
	 * @return bool
	 */
	public function tableExists($table, $refresh = null)
	{
		// Default to refreshing the tables if Craft isn't installed yet
		if ($refresh || ($refresh === null && !craft()->isInstalled()))
		{
			$this->getSchema()->refresh();
		}

		$table = DbHelper::addTablePrefix($table);
		return in_array($table, $this->getSchema()->getTableNames());
	}

	/**
	 * Checks if a column exists in a table.
	 *
	 * @param string    $table
	 * @param string    $column
	 * @param bool|null $refresh
	 *
	 * @return bool
	 */
	public function columnExists($table, $column, $refresh = null)
	{
		// Default to refreshing the tables if Craft isn't installed yet
		if ($refresh || ($refresh === null && !craft()->isInstalled()))
		{
			$this->getSchema()->refresh();
		}

		$table = $this->getSchema()->getTable('{{'.$table.'}}');

		if ($table)
		{
			if (($column = $table->getColumn($column)) !== null)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getNormalizedTablePrefix()
	{
		// Table prefixes cannot be longer than 5 characters
		$tablePrefix = rtrim(craft()->config->get('tablePrefix', ConfigFile::Db), '_');
		if ($tablePrefix)
		{
			if (strlen($tablePrefix) > 5)
			{
				$tablePrefix = substr($tablePrefix, 0, 5);
			}

			$tablePrefix .= '_';
		}
		else
		{
			$tablePrefix = '';
		}

		return $tablePrefix;

	}

	////////////////////
	// PRIVATE METHODS
	////////////////////

	/**
	 * Returns the correct connection string depending on whether a unixSocket is specific or not in the db config.
	 *
	 * @return string
	 */
	private function _processConnectionString()
	{
		$unixSocket = craft()->config->get('unixSocket', ConfigFile::Db);
		if (!empty($unixSocket))
		{
			return strtolower('mysql:unix_socket='.$unixSocket.';dbname=').craft()->config->get('database', ConfigFile::Db).';';
		}
		else
		{
			return strtolower('mysql:host='.craft()->config->get('server', ConfigFile::Db).';dbname=').craft()->config->get('database', ConfigFile::Db).strtolower(';port='.craft()->config->get('port', ConfigFile::Db).';');
		}
	}
}
