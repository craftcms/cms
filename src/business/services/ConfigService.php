<?php

class ConfigService extends CApplicationComponent implements IConfigService
{
	/* Database */
	public function getDatabaseServerName()
	{
		return Blocks::app()->params['db']['server'];
	}

	public function getDatabasePort()
	{
		return Blocks::app()->params['db']['port'];
	}

	public function getDatabaseCharset()
	{
		return Blocks::app()->params['db']['charset'];
	}

	public function getDatabaseCollation()
	{
		return Blocks::app()->params['db']['collation'];
	}

	public function getDatabaseType()
	{
		return Blocks::app()->params['db']['type'];
	}

	public function getDatabaseVersion()
	{
		return Blocks::app()->db->getServerVersion();
	}

	public function getDatabaseTablePrefix()
	{
		return Blocks::app()->params['db']['tablePrefix'];
	}

	public function getDatabaseSupportedTypes()
	{
		$supportedDatabaseTypes = new ReflectionClass('DatabaseType');
		return $supportedDatabaseTypes->getConstants();
	}

	public function getDatabaseName()
	{
		return Blocks::app()->params['db']['database'];
	}

	public function getDatabaseAuthName()
	{
		return Blocks::app()->params['db']['user'];
	}

	public function getDatabaseAuthPassword()
	{
		return Blocks::app()->params['db']['password'];
	}

	/* Environment */
	public function getLocalPHPVersion()
	{
		return PHP_VERSION;
	}

	/* Requirements */
	public function getRequiredPHPVersion()
	{
		return BLOCKS_MIN_PHP_VERSION;
	}

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
