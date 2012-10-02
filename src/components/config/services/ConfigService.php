<?php
namespace Blocks;

/**
 * Config service
 */
class ConfigService extends BaseApplicationComponent
{
	private $_tablePrefix;
	private $_cacheDuration;

	/**
	 * Adds config items to the mix of possible magic getter properties
	 *
	 * @param $name
	 * @return mixed|null
	 */
	function __get($name)
	{
		if (parent::__isset($name))
			return parent::__get($name);
		else
			return $this->getItem($name);
	}

	/**
	 * Get a config item
	 *
	 * @param      $item
	 * @param null $default
	 * @return null
	 */
	public function getItem($item, $default = null)
	{
		if (isset(blx()->params['blocksConfig'][$item]))
			return blx()->params['blocksConfig'][$item];

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
		if (isset(blx()->params['dbConfig'][$item]))
			return blx()->params['dbConfig'][$item];

		return $default;
	}

	/**
	 * Get the time things should be cached for in seconds.
	 *
	 * @return int
	 */
	public function getCacheDuration()
	{
		if (!isset($this->_cacheDuration))
		{
			$duration = $this->getItem('cacheDuration');
			if ($duration)
			{
				$interval = new DateInterval($duration);
				$this->_cacheDuration = $interval->seconds();
			}
			else
			{
				$this->_cacheDuration = 0;
			}
		}
		return $this->_cacheDuration;
	}
}
