<?php
namespace Craft;

/**
 * ConfigService provides APIs for retrieving the values of Craft’s [config settings](http://craftcms.com/docs/config-settings),
 * as well as the values of any plugins’ config settings.
 *
 * An instance of ConfigService is globally accessible in Craft via {@link WebApp::config `craft()->config`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class ConfigService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

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

	// Public Methods
	// =========================================================================

	/**
	 * Returns a config setting value by its name.
	 *
	 * If the config file is set up as a [multi-environment config](http://craftcms.com/docs/multi-environment-configs),
	 * only values from config arrays that match the current request’s environment will be checked and returned.
	 *
	 * By default, `get()` will check craft/config/general.php, and fall back on the default values specified in
	 * craft/app/etc/config/defaults/general.php. See [Craft’s documentation](http://craftcms.com/docs/config-settings)
	 * for a full list of config settings that Craft will check for within that file.
	 *
	 * ```php
	 * $isDevMode = craft()->config->get('devMode');
	 * ```
	 *
	 * If you want to get the config setting from a different config file (e.g. config/myplugin.php), you can specify
	 * its filename as a second argument. If the filename matches a plugin handle, `get()` will check for a
	 * craft/plugins/PluginHandle]/config.php file and use the array it returns as the list of default values.
	 *
	 * ```php
	 * $myConfigSetting = craft()->config->get('myConfigSetting', 'myplugin');
	 * ```
	 *
	 * @param string $item The name of the config setting.
	 * @param string $file The name of the config file (sans .php). Defaults to 'general'.
	 *
	 * @return mixed The value of the config setting, or `null` if a value could not be found.
	 */
	public function get($item, $file = ConfigFile::General)
	{
		$lowercaseFile = StringHelper::toLowerCase($file);

		if (!isset($this->_loadedConfigFiles[$lowercaseFile]))
		{
			$this->_loadConfigFile($file);
		}

		if ($this->exists($item, $file))
		{
			return $this->_loadedConfigFiles[$lowercaseFile][$item];
		}
	}

	/**
	 * Overrides the value of a config setting to a given value.
	 *
	 * By default, `set()` will update the config array that came from craft/config/general.php.
	 * See [Craft’s documentation](http://craftcms.com/docs/config-settings)
	 * for a full list of config settings that Craft will check for within that file.
	 *
	 * ```php
	 * craft()->config->set('devMode', true);
	 * ```
	 *
	 * If you want to set a config setting from a different config file (e.g. config/myplugin.php), you can specify
	 * its filename as a third argument.
	 *
	 * ```php
	 * craft()->config->set('myConfigSetting', 'foo', 'myplugin');
	 * ```
	 *
	 * @param string $item  The name of the config setting.
	 * @param mixed  $value The new value of the config setting.
	 * @param string $file  The name of the config file (sans .php). Defaults to 'general'.
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
	 * Returns a localized config setting value by its name.
	 *
	 * Internally, {@link get()} will be called to get the value of the config setting. If the value is an array,
	 * then only a single value in that array will be returned: the one that has a key matching the `$localeId` argument.
	 * If no matching key is found, the first element of the array will be returned instead.
	 *
	 * This function is used for Craft’s “localizable” config settings:
	 *
	 * - [siteUrl](http://craftcms.com/docs/config-settings#siteUrl)
	 * - [invalidUserTokenPath](http://craftcms.com/docs/config-settings#invalidUserTokenPath)
	 * - [loginPath](http://craftcms.com/docs/config-settings#loginPath)
	 * - [logoutPath](http://craftcms.com/docs/config-settings#logoutPath)
	 * - [setPasswordPath](http://craftcms.com/docs/config-settings#setPasswordPath)
	 * - [setPasswordSuccessPath](http://craftcms.com/docs/config-settings#setPasswordSuccessPath)
	 *
	 * @param string $item     The name of the config setting.
	 * @param string $localeId The locale ID to return. Defaults to {@link WebApp::language `craft()->language`}.
	 * @param string $file     The name of the config file (sans .php). Defaults to 'general'.
	 *
	 * @return mixed The value of the config setting, or `null` if a value could not be found.
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
	 * Returns a config setting value by its name, pulled from craft/config/db.php.
	 *
	 * @param string $item    The name of the config setting.
	 * @param mixed  $default The default value to be returned in the event that the config setting isn’t set. Defaults to `null`.
	 *
	 * @deprecated Deprecated in 2.0. Use {@link get() `get('key', ConfigFile::Db)`} instead.
	 * @return string The value of the config setting, or $default if a value could not be found.
	 */
	public function getDbItem($item, $default = null)
	{
		craft()->deprecator->log('ConfigService::getDbItem()', 'ConfigService::getDbItem() is deprecated. Use get(\'key\', ConfigFile::Db) instead.');

		if ($value = craft()->config->get($item, ConfigFile::Db))
		{
			return $value;
		}

		return $default;
	}

	/**
	 * Returns whether a config setting value exists, by a given name.
	 *
	 * If the config file is set up as a [multi-environment config](http://craftcms.com/docs/multi-environment-configs),
	 * only values from config arrays that match the current request’s environment will be checked.
	 *
	 * By default, `exists()` will check craft/config/general.php, and fall back on the default values specified in
	 * craft/app/etc/config/defaults/general.php. See [Craft’s documentation](http://craftcms.com/docs/config-settings)
	 * for a full list of config settings that Craft will check for within that file.
	 *
	 * If you want to get the config setting from a different config file (e.g. config/myplugin.php), you can specify
	 * its filename as a second argument. If the filename matches a plugin handle, `get()` will check for a
	 * craft/plugins/PluginHandle]/config.php file and use the array it returns as the list of default values.
	 *
	 * ```php
	 * if (craft()->config->exists('myConfigSetting', 'myplugin'))
	 * {
	 *     Craft::log('This site has some pretty useless config settings.');
	 * }
	 * ```
	 *
	 * @param string $item The name of the config setting.
	 * @param string $file The name of the config file (sans .php). Defaults to 'general'.
	 *
	 * @return bool Whether the config setting value exists.
	 */
	public function exists($item, $file = ConfigFile::General)
	{
		$lowercaseFile = StringHelper::toLowerCase($file);

		if (!isset($this->_loadedConfigFiles[$lowercaseFile]))
		{
			$this->_loadConfigFile($file);
		}

		if (array_key_exists($item, $this->_loadedConfigFiles[$lowercaseFile]))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns the value of the [cacheDuration](http://craftcms.com/docs/config-settings#cacheDuration) config setting,
	 * normalized into seconds.
	 *
	 * The actual value of the cacheDuration config setting is supposed to be set using the
	 * [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php).
	 *
	 * ```php
	 * craft()->config->get('cacheDuration'); // 'P1D'
	 * craft()->config->getCacheDuration();   // 86400
	 * ```
	 *
	 * @return int The cacheDuration config setting value, in seconds.
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
	 * Returns whether generated URLs should omit “index.php”, taking the
	 * [omitScriptNameInUrls](http://craftcms.com/docs/config-settings#omitScriptNameInUrls) config setting value
	 * into account.
	 *
	 * If the omitScriptNameInUrls config setting is set to `true` or `false`, then its value will be returned directly.
	 * Otherwise, `omitScriptNameInUrls()` will try to determine whether the server is set up to support index.php
	 * redirection. (See [this help article](http://craftcms.com/help/remove-index.php) for instructions.)
	 *
	 * It does that by creating a dummy request to the site URL with the URI “/testScriptNameRedirect”. If the index.php
	 * redirect is in place, that request should be sent to Craft’s index.php file, which will detect that this is an
	 * index.php redirect-testing request, and simply return “success”. If anything besides “success” is returned
	 * (i.e. an Apache-styled 404 error), then Craft assumes the index.php redirect is not in fact in place.
	 *
	 * Results of the redirect test request will be cached for the amount of time specified by the
	 * [cacheDuration](http://craftcms.com/docs/config-settings#cacheDuration) config setting.
	 *
	 * @return bool Whether generated URLs should omit “index.php”.
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
	 * Returns whether generated URLs should be formatted using PATH_INFO, taking the
	 * [usePathInfo](http://craftcms.com/docs/config-settings#usePathInfo) config setting value into account.
	 *
	 * This method will usually only be called in the event that {@link omitScriptNameInUrls()} returns `false`
	 * (so “index.php” _should_ be included), and it determines what follows “index.php” in the URL.
	 *
	 * If it returns `true`, a forward slash will be used, making “index.php” look like just another segment of the URL
	 * (e.g. http://example.com/index.php/some/path). Otherwise the Craft path will be included in the URL as a query
	 * string param named ‘p’ (e.g. http://example.com/index.php?p=some/path).
	 *
	 * If the usePathInfo config setting is set to `true` or `false`, then its value will be returned directly.
	 * Otherwise, `usePathInfo()` will try to determine whether the server is set up to support PATH_INFO.
	 * (See http://craftcms.com/help/enable-path-info for instructions.)
	 *
	 * It does that by creating a dummy request to the site URL with the URI “/index.php/testPathInfo”. If the server
	 * supports it, that request should be sent to Craft’s index.php file, which will detect that this is an
	 * PATH_INFO-testing request, and simply return “success”. If anything besides “success” is returned
	 * (i.e. an Apache-styled 404 error), then Craft assumes the server is not set up to support PATH_INFO.
	 *
	 * Results of the PATH_INFO test request will be cached for the amount of time specified by the
	 * [cacheDuration](http://craftcms.com/docs/config-settings#cacheDuration) config setting.
	 *
	 * @return bool Whether generaletd URLs should be formatted using PATH_INFO.
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
					// PHP Dev Server supports path info, and doesn't support simultaneous requests, so we need to
					// explicitly check for that.
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
	 * Sets PHP’s memory limit to the maximum specified by the
	 * [phpMaxMemoryLimit](http://craftcms.com/docs/config-settings#phpMaxMemoryLimit) config setting, and gives
	 * the script an unlimited amount of time to execute.
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
	 * Returns the configured user session duration in seconds, or `null` if there is none because user sessions should
	 * expire when the HTTP session expires.
	 *
	 * You can choose whether the
	 * [rememberedUserSessionDuration](http://craftcms.com/docs/config-settings#rememberedUserSessionDuration)
	 * or [userSessionDuration](http://craftcms.com/docs/config-settings#userSessionDuration) config setting
	 * should be used with the $remembered param. If rememberedUserSessionDuration’s value is empty (disabling the
	 * feature) then userSessionDuration will be used regardless of $remembered.
	 *
	 * @param bool $remembered Whether the rememberedUserSessionDuration config setting should be used if it’s set.
	 *                         Default is `false`.
	 *
	 * @return int|null The user session duration in seconds, or `null` if user sessions should expire along with the
	 *                  HTTP session.
	 */
	public function getUserSessionDuration($remembered = false)
	{
		if ($remembered)
		{
			$duration = craft()->config->get('rememberedUserSessionDuration');
		}

		// Even if $remembered = true, it's possible that they've disabled long-term user sessions
		// by setting rememberedUserSessionDuration = 0
		if (empty($duration))
		{
			$duration = craft()->config->get('userSessionDuration');
		}

		if ($duration)
		{
			return DateTimeHelper::timeFormatToSeconds($duration);
		}
	}

	/**
	 * Returns the configured elevated session duration in seconds.
	 *
	 * @return int|boolean The elevated session duration in seconds or false if it has been disabled.
	 */
	public function getElevatedSessionDuration()
	{
		$duration = craft()->config->get('elevatedSessionDuration');

		// See if it has been disabled.
		if ($duration === false)
		{
			return false;
		}

		if ($duration)
		{
			return DateTimeHelper::timeFormatToSeconds($duration);
		}

		// Default to 5 minutes
		return 300;
	}

	/**
	 * Returns the user login path based on the type of the current request.
	 *
	 * If it’s a front-end request, the [loginPath](http://craftcms.com/docs/config-settings#loginPath) config
	 * setting value will be returned. Otherwise the path specified in {@link getCpLoginPath()} will be returned.
	 *
	 * @return string The login path.
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
	 * Returns the user logout path based on the type of the current request.
	 *
	 * If it’s a front-end request, the [logoutPath](http://craftcms.com/docs/config-settings#logoutPath) config
	 * setting value will be returned. Otherwise the path specified in {@link getCpLogoutPath()} will be returned.
	 *
	 * @return string The logout path.
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
	 * Returns the path to the CP’s Login page.
	 *
	 * @return string The Login path.
	 */
	public function getCpLoginPath()
	{
		return 'login';
	}

	/**
	 * Returns the path to the CP’s Logout page.
	 *
	 * @return string The Logout path.
	 */
	public function getCpLogoutPath()
	{
		return 'logout';
	}

	/**
	 * Parses a string for any [environment variables](http://craftcms.com/docs/multi-environment-configs#environment-specific-variables).
	 *
	 * This method simply loops through all of the elements in the
	 * [environmentVariables](http://craftcms.com/docs/config-settings#environmentVariables) config setting’s
	 * value, and replaces any {tag}s in the string that have matching keys with their corresponding values.
	 *
	 * @param string $str The string that should be parsed for environment variables.
	 *
	 * @return string The parsed string.
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
	 * Returns the Resource Request trigger word based on the type of the current request.
	 *
	 * If it’s a front-end request, the [resourceTrigger](http://craftcms.com/docs/config-settings#resourceTrigger)
	 * config setting value will be returned. Otherwise `'resources'` will be returned.
	 *
	 * @return string The Resource Request trigger word.
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

	/**
	 * Returns whether the system is allowed to be auto-updated to the latest release.
	 *
	 * @return bool
	 */
	public function allowAutoUpdates()
	{
		$updateInfo = craft()->updates->getUpdates();

		if (!$updateInfo)
		{
			return false;
		}

		$configVal = $this->get('allowAutoUpdates');

		if (is_bool($configVal))
		{
			return $configVal;
		}

		// TODO: Remove in v3
		if ($configVal === 'build-only')
		{
			craft()->deprecator->log('allowAutoUpdates:build-only', 'The ‘allowAutoUpdates’ config setting should be set to “patch-only” instead of “build-only”.');
			$configVal = 'patch-only';
		}

		if ($configVal === 'patch-only')
		{
			// Return true if the major and minor versions are still the same
			return (AppHelper::getMajorMinorVersion($updateInfo->app->latestVersion) == AppHelper::getMajorMinorVersion(craft()->getVersion()));
		}

		if ($configVal === 'minor-only')
		{
			// Return true if the major version is still the same
			return (AppHelper::getMajorVersion($updateInfo->app->latestVersion) == AppHelper::getMajorVersion(craft()->getVersion()));
		}

		return false;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $name
	 */
	private function _loadConfigFile($name)
	{
		$lowercaseName = StringHelper::toLowerCase($name);

		// Is this a valid Craft config file?
		if (ConfigFile::isValidName($name))
		{
			$defaultsPath = CRAFT_APP_PATH.'etc/config/defaults/'.$name.'.php';
		}
		else
		{
			$defaultsPath = CRAFT_PLUGINS_PATH.$lowercaseName.'/config.php';
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

			if (!IOHelper::fileExists($customConfigPath))
			{
				// Be a little forgiving on case sensitive file systems.
				$customConfigPath = CRAFT_CONFIG_PATH.StringHelper::toLowerCase($name).'.php';
			}

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

		$this->_loadedConfigFiles[$lowercaseName] = $defaultsConfig;
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
