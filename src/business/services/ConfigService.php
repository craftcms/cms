<?php
namespace Blocks;

/**
 * Config service
 */
class ConfigService extends BaseService
{
	/**
	 * Get a config item
	 */
	public function getItem($item, $default = null)
	{
		if (isset(Blocks::app()->params['blocksConfig'][$item]))
			return Blocks::app()->params['blocksConfig'][$item];

		return $default;
	}

	/**
	 * Get a DB config item
	 */
	public function getDbItem($item, $default = null)
	{
		if (isset(Blocks::app()->params['dbConfig'][$item]))
			return Blocks::app()->params['dbConfig'][$item];

		return $default;
	}

	/**
	 * Get all license keys
	 */
	public function getLicenseKeys()
	{
		return LicenseKey::model()->findAll();
	}
}
