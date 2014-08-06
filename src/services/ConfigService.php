<?php
namespace Craft;

/**
 * Class ConfigService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class ConfigService extends BaseApplicationComponent
{
	////////////////////
	// PROPERTIES
	////////////////////

	/**
	 * @var
	 */
	private $_cacheDuration;

	/**
	 * @var
	 */
	private $_omitScriptNameInUrls;

	/**
	 * @var
	 */
	private $_usePathInfo;

	/**
	 * @var array
	 */
	private $_loadedConfigFiles = array();

	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Returns a config item value, or null if it doesn't exist.
	 *
	 * @param string $item
	 * @param string $file
	 *
	 * @return mixed
	 */
	public function get($item, $file = ConfigFile::General)
	{
		if (!isset($this->_loadedConfigFiles[$file]))
		{
			$this->_loadConfigFile($file);
		}

		// If we're looking for devMode and we it looks like we're on the installer and it's a CP request, pretend like devMode is turned on.
		if (!craft()->isConsole() && $item == 'devMode' && craft()->request->getSegment(1) == 'install' && craft()->request->isCpRequest())
		{
			return true;
		}
		else
		{
			if ($this->exists($item, $file))
			{
				return $this->_loadedConfigFiles[$file][$item];
			}
		}
	}

	/**
	 * Sets a config item value.
	 *
	 * @param string $item
	 * @param mixed  $value
	 * @param string $file
	 *
	 * @return null
	 */
	public function set($item, $value, $file = ConfigFile::General)
	{
		if (!isset($this->_loadedConfigFiles[$file]))
		{
			$this->_loadConfigFile($file);
		}

		$this->_loadedConfigFiles[$file][$item] = $value;
	}

	/**
	 * Returns a localized config setting value.
	 *
	 * @param string      $item
	 * @param string|null $localeId
	 * @param string      $file
	 *
	 * @return mixed
	 */
	public function getLocalized($item, $localeId = null, $file = ConfigFile::General)
	{
		$value = $this->get($item, $file);

		if (is_array($value))
		{
			if (!$localeId)
			{
				$localeId = craft()->language;
			}

			if (isset($value[$localeId]))
			{
				return $value[$localeId];
			}
			else if ($value)
			{
				// Just return the first value
				$keys = array_keys($value);
				return $value[$keys[0]];
			}
		}
		else
		{
			return $value;
		}
	}

	/**
	 * Get a DB config item
	 *
	 * @param      $item
	 * @param null $default
	 *
	 * @deprecated Deprecated in 2.0. Use {@link ConfigService::getDbItem() get('key', ConfigFile::Db)} instead.
	 * @return string
	 */
	public function getDbItem($item, $default = null)
	{
		craft()->deprecator->log('ConfigService::getDbItem()', 'ConfigService::getDbItem() is deprecated. Use get(\'key\', ConfigFile::Db) instead.');

		if ($value = craft()->config->get($item, Config::Db))
		{
			return $value;
		}

		return $default;
	}

	/**
	 * Checks if a key exists for a given config file.
	 *
	 * @param        $item
	 * @param string $file
	 *
	 * @return bool
	 */
	public function exists($item, $file = ConfigFile::General)
	{
		if (!isset($this->_loadedConfigFiles[$file]))
		{
			$this->_loadConfigFile($file);
		}

		if (isset($this->_loadedConfigFiles[$file][$item]))
		{
			return true;
		}

		return false;
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
				$this->_cacheDuration = DateTimeHelper::timeFormatToSeconds($duration);
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
			$this->_omitScriptNameInUrls = 'n';

			// Check if the config value has actually been set to true/false
			$configVal = $this->get('omitScriptNameInUrls');

			if (is_bool($configVal))
			{
				$this->_omitScriptNameInUrls = ($configVal === true ? 'y' : 'n');
			}
			else
			{
				// Check if it's cached
				$cachedVal = craft()->cache->get('omitScriptNameInUrls');

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
						// Cache it early so the testScriptNameRedirect request isn't checking for it too
						craft()->cache->set('omitScriptNameInUrls', 'n');

						// Test the server for it
						try
						{
							$baseUrl = craft()->request->getHostInfo().craft()->request->getScriptUrl();
							$url = mb_substr($baseUrl, 0, mb_strrpos($baseUrl, '/')).'/testScriptNameRedirect';

							$client = new \Guzzle\Http\Client();
							$response = $client->get($url, array(), array('connect_timeout' => 2, 'timeout' => 4))->send();

							if ($response->isSuccessful() && $response->getBody(true) === 'success')
							{
								$this->_omitScriptNameInUrls = 'y';
							}
						}
						catch (\Exception $e)
						{
							Craft::log('Unable to determine if a script name redirect is in place on the server: '.$e->getMessage(), LogLevel::Error);
						}
					}

					// Cache it
					craft()->cache->set('omitScriptNameInUrls', $this->_omitScriptNameInUrls);
				}
			}
		}

		return ($this->_omitScriptNameInUrls == 'y');
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
			$this->_usePathInfo = 'n';

			// Check if the config value has actually been set to true/false
			$configVal = $this->get('usePathInfo');

			if (is_bool($configVal))
			{
				$this->_usePathInfo = ($configVal === true ? 'y' : 'n');
			}
			else
			{
				// Check if it's cached
				$cachedVal = craft()->cache->get('usePathInfo');

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
						$this->_usePathInfo = 'y';
					}
					// PHP Dev Server supports path info, and doesn't support simultaneous requests,
					// so we need to explicitly check for that.
					else if (AppHelper::isPhpDevServer())
					{
						$this->_usePathInfo = 'y';
					}
					else
					{
						// Cache it early so the testPathInfo request isn't checking for it too
						craft()->cache->set('usePathInfo', 'n');

						// Test the server for it
						try
						{
							$url = craft()->request->getHostInfo().craft()->request->getScriptUrl().'/testPathInfo';
							$client = new \Guzzle\Http\Client();
							$response = $client->get($url, array(), array('connect_timeout' => 2, 'timeout' => 4))->send();

							if ($response->isSuccessful() && $response->getBody(true) === 'success')
							{
								$this->_usePathInfo = 'y';
							}
						}
						catch (\Exception $e)
						{
							Craft::log('Unable to determine if PATH_INFO is enabled on the server: '.$e->getMessage(), LogLevel::Error);
						}
					}

					// Cache it
					craft()->cache->set('usePathInfo', $this->_usePathInfo);
				}
			}
		}

		return ($this->_usePathInfo == 'y');
	}

	/**
	 * For when you have to give it all you can.
	 *
	 * @return null
	 */
	public function maxPowerCaptain()
	{
		// I need more memory.
		@ini_set('memory_limit', craft()->config->get('phpMaxMemoryLimit'));

		// I need more time.
		@set_time_limit(0);
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
			return $this->getLocalized('loginPath');
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
			return $this->getLocalized('logoutPath');
		}

		return $this->getCpLogoutPath();
	}

	/**
	 * Gets the account verification URL for a user account.
	 *
	 * @param string $code
	 * @param string $uid
	 * @param bool   $full
	 *
	 * @return string
	 */
	public function getActivateAccountPath($code, $uid, $full = true)
	{
		$url = $this->get('actionTrigger').'/users/validate';

		if (!$full)
		{
			return $url;
		}

		if (craft()->request->isSecureConnection())
		{
			$url = UrlHelper::getUrl($url, array(
				'code' => $code, 'id' => $uid
			), 'https');
		}

		$url = UrlHelper::getUrl($url, array(
			'code' => $code, 'id' => $uid
		));

		// Special case because we don't want the CP trigger showing in the email.
		return str_replace($this->get('cpTrigger').'/', '', $url);
	}

	/**
	 * Gets the set password URL for a user account.
	 *
	 * @param       $code
	 * @param       $uid
	 * @param       $user
	 * @param  bool $full
	 *
	 * @return string
	 */
	public function getSetPasswordPath($code, $uid, $user, $full = false)
	{
		if ($user->can('accessCp'))
		{
			$url = $this->getCpSetPasswordPath();

			if ($full)
			{
				if (craft()->request->isSecureConnection())
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
			$url = $this->getLocalized('setPasswordPath');

			if ($full)
			{
				if (craft()->request->isSecureConnection())
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
	 * Parses a string for any environment variable tags.
	 *
	 * @param string $str
	 *
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

	////////////////////
	// PRIVATE METHODS
	////////////////////

	/**
	 * @param $name
	 */
	private function _loadConfigFile($name)
	{
		// Is this a valid Craft config file?
		if (ConfigFile::isValidName($name))
		{
			$defaultsPath = CRAFT_APP_PATH.'etc/config/defaults/'.$name.'.php';
		}
		else
		{
			$defaultsPath = CRAFT_PLUGINS_PATH.$name.'/config.php';
		}

		if (IOHelper::fileExists($defaultsPath))
		{
			$defaultsConfig = @require_once($defaultsPath);
		}

		if (!isset($defaultsConfig) || !is_array($defaultsConfig))
		{
			$defaultsConfig = array();
		}

		// Little extra logic for the general config file.
		if ($name == ConfigFile::General)
		{
			// Does craft/config/general.php exist? (It used to be called blocks.php so maybe not.)
			if (file_exists(CRAFT_CONFIG_PATH.'general.php'))
			{
				if (is_array($customConfig = @include(CRAFT_CONFIG_PATH.'general.php')))
				{
					$this->_mergeConfigs($defaultsConfig, $customConfig);
				}
			}
			else if (file_exists(CRAFT_CONFIG_PATH.'blocks.php'))
			{
				// Originally blocks.php defined a $blocksConfig variable, and then later returned an array directly.
				if (is_array($customConfig = require_once(CRAFT_CONFIG_PATH.'blocks.php')))
				{
					$this->_mergeConfigs($defaultsConfig, $customConfig);
				}
				else if (isset($blocksConfig))
				{
					$defaultsConfig = array_merge($defaultsConfig, $blocksConfig);
					unset($blocksConfig);
				}
			}
		}
		else
		{
			$customConfigPath = CRAFT_CONFIG_PATH.$name.'.php';
			if (IOHelper::fileExists($customConfigPath))
			{
				if (is_array($customConfig = @include($customConfigPath)))
				{
					$this->_mergeConfigs($defaultsConfig, $customConfig);
				}
				else if ($name == ConfigFile::Db)
				{
					// Originally db.php defined a $dbConfig variable.
					if (@require_once(CRAFT_CONFIG_PATH.'db.php'))
					{
						$this->_mergeConfigs($defaultsConfig, $dbConfig);
						unset($dbConfig);
					}
				}
			}
		}

		$this->_loadedConfigFiles[$name] = $defaultsConfig;
	}

	/**
	 * Merges a base config array with a custom config array, taking environment-specific configs into account.
	 *
	 * @param array &$baseConfig
	 * @param array $customConfig
	 *
	 * @return null
	 */
	private function _mergeConfigs(&$baseConfig, $customConfig)
	{
		// Is this a multi-environment config?
		if (array_key_exists('*', $customConfig))
		{
			$mergedCustomConfig = array();

			foreach ($customConfig as $env => $envConfig)
			{
				if ($env == '*' || strpos(CRAFT_ENVIRONMENT, $env) !== false)
				{
					$mergedCustomConfig = \CMap::mergeArray($mergedCustomConfig, $envConfig);
				}
			}

			$customConfig = $mergedCustomConfig;
		}

		$baseConfig = array_merge($baseConfig, $customConfig);
	}
}
