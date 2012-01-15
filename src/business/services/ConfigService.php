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
		return Blocks::app()->db->serverVersion;
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

	/**
	 * Gets the sessionTimeout config var and converts it into seconds.
	 *
	 * If we can't figure out what sessionTimeout setting is, then we default to $defaultTimeout.
	 *
	 * @return int
	 */
	public function getSessionTimeoutInSeconds()
	{
		// If we can't figure out what the setting is, we'll default to this, which is 1 hour (3600 seconds).
		$defaultTimeout = 3600;

		$timeOut = Blocks::app()->config('sessionTimeout');

		if (StringHelper::IsNullOrEmpty($timeOut))
			return $defaultTimeout;

		$unit = $timeOut[strlen($timeOut) - 1];
		$time = substr($timeOut, 0, strlen($timeOut) - 1);

		if ($unit !== 'm' && $unit !== 'h' && $unit !== 'd' && !is_numeric($time))
			return $defaultTimeout;

		switch ($unit)
		{
			case 'm':
				return (int)$time * 60;
				break;

			case 'h':
				return (int)$time * 60 * 60;
				break;

			case 'd':
				return (int)$time * 60 * 60 * 24;
				break;

			default:
				return $defaultTimeout;
				break;
		}
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
