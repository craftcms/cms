<?php
namespace Blocks;

/**
 * Config service
 */
class ConfigService extends BaseApplicationComponent
{
	private $_cacheDuration;
	private $_omitScriptNameInUrls;
	private $_usePathInfo;

	/**
	 * Returns a config item value, or null if it doesn't exist.
	 *
	 * @param string $item
	 * @return mixed
	 */
	public function get($item)
	{
		if (isset(blx()->params['generalConfig'][$item]))
		{
			return blx()->params['generalConfig'][$item];
		}
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
		{
			return blx()->params['dbConfig'][$item];
		}

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
			$duration = $this->get('cacheDuration');

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

	/**
	 * Returns whether generated URLs should omit 'index.php'.
	 *
	 * @return bool
	 */
	public function omitScriptNameInUrls()
	{
		if (!isset($this->_omitScriptNameInUrls))
		{
			$this->_omitScriptNameInUrls = 'no';

			// Check if the config value has actually been set to true/false
			$configVal = $this->get('omitScriptNameInUrls');

			if (is_bool($configVal))
			{
				$this->_omitScriptNameInUrls = ($configVal == true ? 'yes' : 'no');
			}
			else
			{
				// Check if it's cached
				$cachedVal = blx()->fileCache->get('omitScriptNameInUrls');

				if ($cachedVal !== false)
				{
					$this->_omitScriptNameInUrls = $cachedVal;
				}
				else
				{
					// Test the server for it
					try
					{
						$baseUrl = blx()->request->getHostInfo().blx()->request->getScriptUrl();
						$url = substr($baseUrl, 0, strrpos($baseUrl, '/')).'/testScriptNameRedirect';
						$response = \Requests::get($url);

						if ($response->success && $response->body === 'success')
						{
							$this->_omitScriptNameInUrls = 'yes';
						}
					}
					catch (\Exception $e)
					{
						Blocks::log('Unable to determine if a script name redirect is in place on the server: '.$e->getMessage(), \CLogger::LEVEL_ERROR);
					}

					// Cache it
					blx()->fileCache->set('omitScriptNameInUrls', $this->_omitScriptNameInUrls, $this->getCacheDuration());
				}
			}
		}

		return $this->_omitScriptNameInUrls == 'no' ? false : true;
	}

	/**
	 * Returns whether generated URLs should be formatted using PATH_INFO.
	 *
	 * @return bool
	 */
	public function usePathInfo()
	{
		if (!isset($this->_usePathInfo))
		{
			$this->_usePathInfo = 'no';

			// Check if the config value has actually been set to true/false
			$configVal = $this->get('usePathInfo');

			if (is_bool($configVal))
			{
				$this->_usePathInfo = ($configVal == true ? 'yes' : 'no');
			}
			else
			{
				// Check if it's cached
				$cachedVal = blx()->fileCache->get('usePathInfo');

				if ($cachedVal !== false)
				{
					$this->_usePathInfo = $cachedVal;
				}
				else
				{
					// If there is already a PATH_INFO var available, we know it supports it.
					// Added the !empty() check for nginx.
					if (!empty($_SERVER['PATH_INFO']))
					{
						$this->_usePathInfo = true;
					}
					else
					{
						// Test the server for it
						try
						{
							$url = blx()->request->getHostInfo().blx()->request->getScriptUrl().'/testPathInfo';
							$response = \Requests::get($url);

							if ($response->success && $response->body === 'success')
							{
								$this->_usePathInfo = 'yes';
							}
						}
						catch (\Exception $e)
						{
							Blocks::log('Unable to determine if PATH_INFO is enabled on the server: '.$e->getMessage(), \CLogger::LEVEL_ERROR);
						}
					}

					// Cache it
					blx()->fileCache->set('usePathInfo', $this->_usePathInfo, $this->getCacheDuration());
				}
			}
		}

		return $this->_usePathInfo == 'no' ? false : true;
	}
}
