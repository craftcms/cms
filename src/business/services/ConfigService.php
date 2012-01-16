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
	 * @return array
	 */
	public function getDatabaseSupportedTypes()
	{
		$supportedDatabaseTypes = new ReflectionClass('DatabaseType');
		return $supportedDatabaseTypes->getConstants();
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
