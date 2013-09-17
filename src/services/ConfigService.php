<?php
namespace Craft;

/**
 * Config service
 */
class ConfigService extends BaseApplicationComponent
{
	/**
	 * The general configuration names and values as defined in craft/app/etc/config and craft/config
	 *
	 * @var
	 */
	public $generalConfig;

	/**
	 * The database configuration names and values as defined in craft/app/etc/config and craft/config
	 *
	 * @var
	 */
	public $dbConfig;

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
		// If we're looking for devMode and we it looks like we're on the installer and it's a CP request, pretend like devMode is turned on.
		if (!craft()->isConsole() && $item == 'devMode' && craft()->request->getSegment(1) == 'install' && craft()->request->isCpRequest())
		{
			return true;
		}
		else
		{
			if (isset($this->generalConfig[$item]))
			{
				return $this->generalConfig[$item];
			}
		}
	}

	/**
	 * Sets a config item value.
	 *
	 * @param string $item
	 * @param mixed $value
	 */
	public function set($item, $value)
	{
		$this->generalConfig[$item] = $value;
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
		if (isset($this->dbConfig[$item]))
		{
			return $this->dbConfig[$item];
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
				$this->_cacheDuration = $interval->toSeconds();
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
				$cachedVal = craft()->fileCache->get('omitScriptNameInUrls');

				if ($cachedVal !== false)
				{
					$this->_omitScriptNameInUrls = $cachedVal;
				}
				else
				{
					// PHP Dev Server does omit the script name from 404s without any help from a redirect script,
					// *unless* the URI looks like a file, in which case it'll just throw a 404.
					if (AppHelper::isPhpDevServer())
					{
						$this->_omitScriptNameInUrls = false;
					}
					else
					{
						// Test the server for it
						try
						{
							$baseUrl = craft()->request->getHostInfo().craft()->request->getScriptUrl();
							$url = mb_substr($baseUrl, 0, mb_strrpos($baseUrl, '/')).'/testScriptNameRedirect';

							$client = new \Guzzle\Http\Client();
							$response = $client->get($url, array(), array('connect_timeout' => 2, 'timeout' => 4))->send();

							if ($response->isSuccessful() && $response->getBody(true) === 'success')
							{
								$this->_omitScriptNameInUrls = 'yes';
							}
						}
						catch (\Exception $e)
						{
							Craft::log('Unable to determine if a script name redirect is in place on the server: '.$e->getMessage(), LogLevel::Error);
						}
					}

					// Cache it
					craft()->fileCache->set('omitScriptNameInUrls', $this->_omitScriptNameInUrls);
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
				$cachedVal = craft()->fileCache->get('usePathInfo');

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
					// PHP Dev Server supports path info, and doesn't support simultaneous requests,
					// so we need to explicitly check for that.
					else if (AppHelper::isPhpDevServer())
					{
						$this->_usePathInfo = true;
					}
					else
					{
						// Test the server for it
						try
						{
							$url = craft()->request->getHostInfo().craft()->request->getScriptUrl().'/testPathInfo';
							$client = new \Guzzle\Http\Client();
							$response = $client->get($url, array(), array('connect_timeout' => 2, 'timeout' => 4))->send();

							if ($response->isSuccessful() && $response->getBody(true) === 'success')
							{
								$this->_usePathInfo = 'yes';
							}
						}
						catch (\Exception $e)
						{
							Craft::log('Unable to determine if PATH_INFO is enabled on the server: '.$e->getMessage(), LogLevel::Error);
						}
					}

					// Cache it
					craft()->fileCache->set('usePathInfo', $this->_usePathInfo);
				}
			}
		}

		return $this->_usePathInfo == 'no' ? false : true;
	}

	/**
	 * For when you have to give it all you can.
	 */
	public function maxPowerCaptain()
	{
		// I need more memory.
		@ini_set('memory_limit', craft()->config->get('phpMaxMemoryLimit'));

		// I need more time.
		set_time_limit(120);
	}

	/**
	 * Returns the correct login path based on the type of the current request.
	 *
	 * @return mixed|string
	 */
	public function getLoginPath()
	{
		if (craft()->request->isSiteRequest())
		{
			return craft()->config->get('loginPath');
		}

		return $this->getCpLoginPath();
	}

	/**
	 * Returns the correct logout path based on the type of the current request.
	 *
	 * @return mixed|string
	 */
	public function getLogoutPath()
	{
		if (craft()->request->isSiteRequest())
		{
			return craft()->config->get('logoutPath');
		}

		return $this->getCpLogoutPath();
	}

	/**
	 * Gets the account verification URL for a user account.
	 *
	 * @param       $code
	 * @param       $uid
	 * @param  bool $full
	 * @return string
	 */
	public function getActivateAccountPath($code, $uid, $full = true)
	{
		$url = 'actions/users/validate';

		if (!$full)
		{
			return $url;
		}

		if (craft()->request->isSecureConnection)
		{
			$url = UrlHelper::getUrl($url, array(
				'code' => $code, 'id' => $uid
			), 'https');
		}

		$url = UrlHelper::getUrl($url, array(
			'code' => $code, 'id' => $uid
		));

		// Special case because we don't want the CP trigger showing in the email.
		return str_replace(craft()->config->get('cpTrigger').'/', '', $url);
	}

	/**
	 * Gets the set password URL for a user account.
	 *
	 * @param       $code
	 * @param       $uid
	 * @param       $user
	 * @param  bool $full
	 * @return string
	 */
	public function getSetPasswordPath($code, $uid, $user, $full = false)
	{
		if ($user->can('accessCp'))
		{
			$url = $this->getCpSetPasswordPath();

			if ($full)
			{
				if (craft()->request->isSecureConnection)
				{
					$url = UrlHelper::getCpUrl($url, array(
						'code' => $code, 'id' => $uid
					), 'https');
				}
				else
				{
					$url = UrlHelper::getCpUrl($url, array(
						'code' => $code, 'id' => $uid
					));
				}
			}
		}
		else
		{
			$url = craft()->config->get('setPasswordPath');

			if ($full)
			{
				if (craft()->request->isSecureConnection)
				{
					$url = UrlHelper::getUrl($url, array(
						'code' => $code, 'id' => $uid
					), 'https');
				}
				else
				{
					$url = UrlHelper::getUrl($url, array(
						'code' => $code, 'id' => $uid
					));
				}
			}
		}

		return $url;
	}

	/**
	 * @return string
	 */
	public function getCpSetPasswordPath()
	{
		return 'setpassword';
	}

	/**
	 * @return string
	 */
	public function getCpActivateAccountPath()
	{
		return 'activate';
	}

	/**
	 * @return string
	 */
	public function getCpLoginPath()
	{
		return 'login';
	}

	/**
	 * @return string
	 */
	public function getCpLogoutPath()
	{
		return 'logout';
	}

	/**
	 * TODO: Deprecate when we remove the 'activateFailurePath' config var.
	 */
	public function getActivateAccountFailurePath()
	{
		if ($path = craft()->config->get('activateAccountFailurePath') !== '')
		{
			return $path;
		}

		// Check the deprecated one.
		return craft()->config->get('activateFailurePath');
	}

	/**
	 * Parses a string for any environment variable tags.
	 *
	 * @param string $str
	 * @return string $str
	 */
	public function parseEnvironmentString($str)
	{
		foreach ($this->get('environmentVariables') as $key => $value)
		{
			$str = str_replace('{'.$key.'}', $value, $str);
		}

		return $str;
	}

	/**
	 * Returns the CP resource trigger word.
	 *
	 * @return string
	 */
	public function getResourceTrigger()
	{
		if (craft()->request->isCpRequest())
		{
			return 'resources';
		}
		else
		{
			return $this->get('resourceTrigger');
		}
	}
}
