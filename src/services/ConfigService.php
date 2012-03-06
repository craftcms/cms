<?php
namespace Blocks;

/**
 * Config service
 */
class ConfigService extends BaseService
{
	private $_tablePrefix;

	/**
	 * Get a config item
	 *
	 * @param      $item
	 * @param null $default
	 * @return null
	 */
	public function getItem($item, $default = null)
	{
		if (isset(b()->params['blocksConfig'][$item]))
			return b()->params['blocksConfig'][$item];

		return $default;
	}

	/**
	 * Get a DB config item
	 *
	 * @param      $item
	 * @param null $default
	 * @return null
	 */
	public function getDbItem($item, $default = null)
	{
		if (isset(b()->params['dbConfig'][$item]))
			return b()->params['dbConfig'][$item];

		return $default;
	}

	public function getTablePrefix()
	{
		if (!isset($this->_tablePrefix))
		{
			$tablePrefix = (string)$this->getDbItem('tablePrefix');
			if ($tablePrefix)
				$tablePrefix = rtrim($tablePrefix, '_').'_';
			$this->_tablePrefix = $tablePrefix;
		}
		return $this->_tablePrefix;
	}

	/**
	 * Get all license keys
	 * @return mixed
	 */
	public function getLicenseKeys()
	{
		return LicenseKey::model()->findAll();
	}
}
