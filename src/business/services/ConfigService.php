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

	/* Paths */
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
		return $this->getBlocksAppPath().'resources'.DIRECTORY_SEPARATOR;
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
		$siteHandle = Blocks::app()->request->getSiteInfo();
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->getBlocksBasePath().'templates'.DIRECTORY_SEPARATOR.$siteHandle.DIRECTORY_SEPARATOR;
	}

	public function getBlocksTemplatePath()
	{
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
			return $this->getBlocksSiteTemplatePath();

		if (($moduleName = Blocks::app()->urlManager->getTemplateMatch()->getModuleName()) !== null)
			return $this->getBlocksAppPath().'modules'.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;

		return $this->getBlocksCPTemplatePath();
	}

	public function getBlocksTemplateCachePath()
	{
		$cachePath = null;

		$requestType = Blocks::app()->request->getCMSRequestType();
		switch ($requestType)
		{
			case RequestType::Site:
				$siteHandle = Blocks::app()->request->getSiteInfo();
				$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;
				$cachePath = Blocks::app()->config->getBlocksRuntimePath().'cached'.DIRECTORY_SEPARATOR.$siteHandle.DIRECTORY_SEPARATOR.'translated_site_templates'.DIRECTORY_SEPARATOR;
				break;

			case RequestType::ControlPanel:
				$cachePath = Blocks::app()->config->getBlocksRuntimePath().'cached'.DIRECTORY_SEPARATOR.'translated_cp_templates'.DIRECTORY_SEPARATOR;

				if (($moduleName = Blocks::app()->urlManager->getTemplateMatch()->getModuleName()) !== null)
					$cachePath .= 'modules'.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR;
				break;

			default:
				$cachePath = Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR.'cached';
		}

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $cachePath;
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

	/* Site */
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

	public function getSiteByUrl()
	{
		$serverName = Blocks::app()->request->getServerName();
		$httpServerName = 'http://'.$serverName;
		$httpsServerName = 'https://'.$serverName;

		$site = Sites::model()->find(
			'url=:url OR url=:httpUrl OR url=:httpsUrl', array(':url' => $serverName, ':httpUrl' => $httpServerName, ':httpsUrl' => $httpsServerName)
		);

		return $site;
	}
}
