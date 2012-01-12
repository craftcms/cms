<?php

/**
 *
 */
class ConfigService extends CApplicationComponent
{
	/* Database */

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseServerName()
	{
		return Blocks::app()->params['db']['server'];
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabasePort()
	{
		return Blocks::app()->params['db']['port'];
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseCharset()
	{
		return Blocks::app()->params['db']['charset'];
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseCollation()
	{
		return Blocks::app()->params['db']['collation'];
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseType()
	{
		return Blocks::app()->params['db']['type'];
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseVersion()
	{
		return Blocks::app()->db->serverVersion();
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseTablePrefix()
	{
		return Blocks::app()->params['db']['tablePrefix'];
	}

	/**
	 * @access public
	 *
	 * @return array
	 */
	public function getDatabaseSupportedTypes()
	{
		$supportedDatabaseTypes = new ReflectionClass('DatabaseType');
		return $supportedDatabaseTypes->getConstants();
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseName()
	{
		return Blocks::app()->params['db']['database'];
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseAuthName()
	{
		return Blocks::app()->params['db']['user'];
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getDatabaseAuthPassword()
	{
		return Blocks::app()->params['db']['password'];
	}

	/* Environment */

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getLocalPHPVersion()
	{
		return PHP_VERSION;
	}

	/* Requirements */

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getRequiredPHPVersion()
	{
		return BLOCKS_MIN_PHP_VERSION;
	}

	/**
	 * @access publi
	 *
	 * @param $databaseType
	 *
	 * @return string
	 *
	 * @throws BlocksException
	 */
	public function getDatabaseRequiredVersionByType($databaseType)
	{
		if (StringHelper::IsNullOrEmpty($databaseType))
			throw new BlocksException('databaseType is required.');

		switch($databaseType)
		{
			case DatabaseType::MySQL:
				return BLOCKS_MIN_MYSQL_VERSION;

			case DatabaseType::Oracle:
				return BLOCKS_MIN_ORACLE_VERSION;

			case DatabaseType::PostgreSQL:
				return BLOCKS_MIN_POSTGRESQL_VERSION;

			case DatabaseType::SQLite:
				return BLOCKS_MIN_SQLITE_VERSION;

			case DatabaseType::SQLServer:
				return BLOCKS_MIN_SQLSERVER_VERSION;
		}

		throw new BlocksException('Unknown database type: '.$databaseType);
	}
}
