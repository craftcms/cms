<?php

class ConfigRepository extends CApplicationComponent implements IConfigRepository
{
	public function getDatabaseServerName()
	{
		return Blocks::app()->params['databaseConfig']['server'];
	}

	public function getDatabasePort()
	{
		return Blocks::app()->params['databaseConfig']['port'];
	}

	public function getDatabaseCharset()
	{
		return Blocks::app()->params['databaseConfig']['charset'];
	}

	public function getDatabaseCollation()
	{
		return Blocks::app()->params['databaseConfig']['collation'];
	}

	public function getDatabaseType()
	{
		return Blocks::app()->params['databaseConfig']['type'];
	}

	public function getDatabaseVersion()
	{
		return Blocks::app()->db->getServerVersion();
	}

	public function getDatabaseTablePrefix()
	{
		return Blocks::app()->params['databaseConfig']['tablePrefix'];
	}

	public function getDatabaseSupportedTypes()
	{
		$supportedDatabaseTypes = new ReflectionClass('DatabaseType');
		return $supportedDatabaseTypes->getConstants();
	}

	public function getDatabaseName()
	{
		return Blocks::app()->params['databaseConfig']['name'];
	}

	public function getDatabaseAuthName()
	{
		return Blocks::app()->params['databaseConfig']['user'];
	}

	public function getDatabaseAuthPassword()
	{
		return Blocks::app()->params['databaseConfig']['password'];
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

	public function getLocalPHPVersion()
	{
		return PHP_VERSION;
	}

	public function getRequiredPHPVersion()
	{
		return BLOCKS_MIN_PHP_VERSION;
	}

	public function getSiteLicenseKey()
	{
		return Blocks::app()->params['siteConfig']['licenseKey'];
	}

	public function getSiteName()
	{
		return Blocks::app()->params['siteConfig']['siteName'];
	}

	public function getSiteLanguage()
	{
		return Blocks::app()->params['siteConfig']['language'];
	}

	public function getSiteUrl()
	{
		return Blocks::app()->params['siteConfig']['siteUrl'];
	}

	public function updateConfigFile($filePath, $key, $value)
	{
		$configFile = Blocks::app()->file->set($filePath, true);
		$configFileName = $configFile->getFileName();

		clearstatcache();

		if ($configFile->exists && $configFile->getWriteable())
		{
			$configFileContent = $configFile->getContents();

			$configFileContent = $key == 'type' && $configFileName == 'db'
								? preg_replace('/^(\$'.$configFileName.'Config\[\'('.$key.')\'\])(\s=\s)(\'?)(.*?)(\'?;)/m', '${1}${3}${4}DatabaseType::'.$value.'${6}', $configFileContent)
								: preg_replace('/^(\$'.$configFileName.'Config\[\'('.$key.')\'\])(\s=\s)(\'?)(.*?)(\'?;)/m', '${1}${3}${4}'.$value.'${6}', $configFileContent);

			$configFile->setContents(null, $configFileContent);

			return true;
		}
		else
		{
			return false;
		}
	}
}
