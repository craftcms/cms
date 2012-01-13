<?php

/**
 *
 */
class ConfigService extends CApplicationComponent
{
	const BUILD_PERSONAL_FILENAME_PREFIX = 'blocks_personal_';
	const BUILD_PRO_FILENAME_PREFIX = 'blocks_pro_';
	const BUILD_STANDARD_FILENAME_PREFIX = 'blocks_standard_';

	/* Database */

	/**
	 * @return string
	 */
	public function getDatabaseServerName()
	{
		return Blocks::app()->params['db']['server'];
	}

	/**
	 * @return string
	 */
	public function getDatabasePort()
	{
		return Blocks::app()->params['db']['port'];
	}

	/**
	 * @return string
	 */
	public function getDatabaseCharset()
	{
		return Blocks::app()->params['db']['charset'];
	}

	/**
	 * @return string
	 */
	public function getDatabaseCollation()
	{
		return Blocks::app()->params['db']['collation'];
	}

	/**
	 * @return string
	 */
	public function getDatabaseType()
	{
		return Blocks::app()->params['db']['type'];
	}

	/**
	 * @return string
	 */
	public function getDatabaseVersion()
	{
		return Blocks::app()->db->serverVersion();
	}

	/**
	 * @return string
	 */
	public function getDatabaseTablePrefix()
	{
		return Blocks::app()->params['db']['tablePrefix'];
	}

	/**
	 * @return array
	 */
	public function getDatabaseSupportedTypes()
	{
		$supportedDatabaseTypes = new ReflectionClass('DatabaseType');
		return $supportedDatabaseTypes->getConstants();
	}

	/**
	 * @return string
	 */
	public function getDatabaseName()
	{
		return Blocks::app()->params['db']['database'];
	}

	/**
	 * @return string
	 */
	public function getDatabaseAuthName()
	{
		return Blocks::app()->params['db']['user'];
	}

	/**
	 * @return string
	 */
	public function getDatabaseAuthPassword()
	{
		return Blocks::app()->params['db']['password'];
	}

	/* Environment */

	/**
	 * @return string
	 */
	public function getLocalPHPVersion()
	{
		return PHP_VERSION;
	}

	/**
	 * @return string
	 */
	public function getBuildPersonalFileNamePrefix()
	{
		return self::BUILD_PERSONAL_FILENAME_PREFIX;
	}

	/**
	 * @return string
	 */
	public function getBuildProFileNamePrefix()
	{
		return self::BUILD_PRO_FILENAME_PREFIX;
	}

	/**
	 * @return string
	 */
	public function getBuildStandardFileNamePrefix()
	{
		return self::BUILD_STANDARD_FILENAME_PREFIX;
	}

	/* Requirements */

	/**
	 * @return string
	 */
	public function getRequiredPHPVersion()
	{
		return '5.1.0';
	}

	/**
	 * @param $databaseType
	 * @return string
	 * @throws BlocksException
	 */
	public function getDatabaseRequiredVersionByType($databaseType)
	{
		if (StringHelper::IsNullOrEmpty($databaseType))
			throw new BlocksException('databaseType is required.');

		switch($databaseType)
		{
			case DatabaseType::MySQL:
				return '';

			case DatabaseType::Oracle:
				return '';

			case DatabaseType::PostgreSQL:
				return '';

			case DatabaseType::SQLite:
				return '';

			case DatabaseType::SQLServer:
				return '';
		}

		throw new BlocksException('Unknown database type: '.$databaseType);
	}
}
