<?php

class ConfigRepository extends CApplicationComponent implements IConfigRepository
{
	private $dbCharsetDefault = 'utf8';
	private $dbPortDefault = '3306';
	private $dbCollationDefault = 'utf8_unicode_ci';
	private $dbTypeDefault = DatabaseType::MySQL;

	public function getDatabaseServerName()
	{
		return Blocks::app()->params['db']['server'];
	}

	public function getDatabasePort()
	{
		if (isset(Blocks::app()->params['db']['port']))
			return Blocks::app()->params['db']['port'];

		return $this->dbPortDefault;
	}

	public function getDatabaseCharset()
	{
		if (isset(Blocks::app()->params['db']['charset']))
			return Blocks::app()->params['db']['charset'];

		return $this->dbCharsetDefault;
	}

	public function getDatabaseCollation()
	{
		if (isset(Blocks::app()->params['db']['collation']))
			return Blocks::app()->params['db']['collation'];

		return $this->dbCollationDefault;
	}

	public function getDatabaseType()
	{
		if (isset(Blocks::app()->params['db']['type']))
			return Blocks::app()->params['db']['type'];

		return $this->dbTypeDefault;
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

	public function getBlocksBasePath()
	{
		return BLOCKS_BASE_PATH;
	}

	public function getBlocksConfigPath()
	{
		return $this->getBlocksBasePath().'config'.DIRECTORY_SEPARATOR;
	}

	public function getBlocksPluginsPath()
	{
		return $this->getBlocksBasePath().'plugins'.DIRECTORY_SEPARATOR;
	}

	public function getBlocksResourcesPath()
	{
		return $this->getBlocksBasePath().'resources'.DIRECTORY_SEPARATOR;
	}

	public function getBlocksAppPath()
	{
		return Blocks::app()->getBasePath().DIRECTORY_SEPARATOR;
	}

	public function getBlocksFrameworkPath()
	{
		return $this->getBlocksAppPath().'framework'.DIRECTORY_SEPARATOR;
	}

	public function getBlocksRuntimePath()
	{
		return Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR;
	}


	public function getBlocksResourceProcessorPath()
	{
		return $this->getBlocksAppPath().'business'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'ResourceProcessor.php';
	}

	public function getBlocksResourceProcessorUrl()
	{
		return '/index.php/blocks/app/business/web/ResourceProcessor.php';
	}

	public function getBlocksCPTemplatePath()
	{
		return $this->getBlocksAppPath().'templates'.DIRECTORY_SEPARATOR;
	}

	public function getBlocksSiteTemplatePath()
	{
		return $this->getBlocksBasePath().'templates'.DIRECTORY_SEPARATOR;
	}

	public function getBlocksCPTemplateCachePath()
	{
		return $this->getBlocksRuntimePath().'cached'.DIRECTORY_SEPARATOR.'translated_cp_templates'.DIRECTORY_SEPARATOR;
	}

	public function getBlocksSiteTemplateCachePath()
	{
		return $this->getBlocksRuntimePath().'cached'.DIRECTORY_SEPARATOR.'translated_site_templates'.DIRECTORY_SEPARATOR;
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
		return Blocks::app()->params['config']['licenseKey'];
	}

	public function getSiteName()
	{
		return Blocks::app()->params['config']['siteName'];
	}

	public function getSiteLanguage()
	{
		return Blocks::app()->params['config']['language'];
	}

	public function getSiteUrl()
	{
		return Blocks::app()->params['config']['siteUrl'];
	}

	public function getAllowedTemplateFileExtensions()
	{
		return array('html', 'php');
	}

	public function isExtensionInAllowedTemplateExtensions($extension)
	{
		return in_array($extension, $this->getAllowedTemplateFileExtensions());
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
