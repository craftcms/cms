<?php
namespace Craft;

/**
 * Class AppBehavior
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.behaviors
 * @since     1.2
 */
class AppBehavior extends BaseBehavior
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_isInstalled;

	/**
	 * @var
	 */
	private $_isLocalized;

	/**
	 * @var
	 */
	private $_info;

	/**
	 * @var
	 */
	private $_siteName;

	/**
	 * @var
	 */
	private $_siteUrl;

	/**
	 * @var bool
	 */
	private $_isDbConfigValid = false;

	/**
	 * @var bool
	 */
	private $_isDbConnectionValid = false;

	/**
	 * @var
	 */
	private $_language;

	/**
	 * @var bool
	 */
	private $_gettingLanguage = false;


	// Public Methods
	// =========================================================================

	/**
	 * Determines if Craft is installed by checking if the info table exists.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		if (!isset($this->_isInstalled))
		{
			try
			{
				// First check to see if DbConnection has even been initialized, yet.
				if (craft()->getComponent('db'))
				{
					// If the db config isn't valid, then we'll assume it's not installed.
					if (!craft()->getIsDbConnectionValid())
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
			catch (DbConnectException $e)
			{
				return false;
			}

			$this->_isInstalled = (craft()->isConsole() || craft()->db->tableExists('info', false));
		}

		return $this->_isInstalled;
	}

	/**
	 * Tells Craft that it's installed now.
	 *
	 * @return null
	 */
	public function setIsInstalled()
	{
		// If you say so!
		$this->_isInstalled = true;
	}

	/**
	 * Returns whether this site has multiple locales.
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		if (!isset($this->_isLocalized))
		{
			$this->_isLocalized = ($this->getEdition() == Craft::Pro && count(craft()->i18n->getSiteLocales()) > 1);
		}

		return $this->_isLocalized;
	}

	/**
	 * Returns the installed Craft version.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->getInfo('version');
	}

	/**
	 * Returns the installed Craft build.
	 *
	 * @return string
	 */
	public function getBuild()
	{
		return $this->getInfo('build');
	}

	/**
	 * Returns the installed Craft build.
	 *
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return $this->getInfo('schemaVersion');
	}

	/**
	 * Returns the installed Craft release date.
	 *
	 * @return string
	 */
	public function getReleaseDate()
	{
		return $this->getInfo('releaseDate');
	}

	/**
	 * Returns the Craft track.
	 *
	 * @return string
	 */
	public function getTrack()
	{
		return $this->getInfo('track');
	}

	/**
	 * Returns the Craft edition.
	 *
	 * @return int
	 */
	public function getEdition()
	{
		return $this->getInfo('edition');
	}

	/**
	 * Returns the name of the Craft edition.
	 *
	 * @return string
	 */
	public function getEditionName()
	{
		return AppHelper::getEditionName($this->getEdition());
	}

	/**
	 * Returns the edition Craft is actually licensed to run in.
	 *
	 * @return int|null
	 */
	public function getLicensedEdition()
	{
		$licensedEdition = craft()->cache->get('licensedEdition');

		if ($licensedEdition !== false)
		{
			return $licensedEdition;
		}
	}

	/**
	 * Returns the name of the edition Craft is actually licensed to run in.
	 *
	 * @return string|null
	 */
	public function getLicensedEditionName()
	{
		$licensedEdition = $this->getLicensedEdition();

		if ($licensedEdition !== null)
		{
			return AppHelper::getEditionName($licensedEdition);
		}
	}

	/**
	 * Returns whether Craft is running with the wrong edition.
	 *
	 * @return bool
	 */
	public function hasWrongEdition()
	{
		$licensedEdition = $this->getLicensedEdition();
		return ($licensedEdition !== null && $licensedEdition != $this->getEdition() && !$this->canTestEditions());
	}

	/**
	 * Sets the Craft edition.
	 *
	 * @param int $edition
	 *
	 * @return bool
	 */
	public function setEdition($edition)
	{
		$info = $this->getInfo();
		$info->edition = $edition;
		return $this->saveInfo($info);
	}

	/**
	 * Requires that Craft is running an equal or better edition than what's passed in
	 *
	 * @param int  $edition
	 * @param bool $orBetter
	 *
	 * @throws Exception
	 */
	public function requireEdition($edition, $orBetter = true)
	{
		if ($this->isInstalled())
		{
			$installedEdition = $this->getEdition();

			if (($orBetter && $installedEdition < $edition) || (!$orBetter && $installedEdition != $edition))
			{
				throw new Exception(Craft::t('Craft {edition} is required to perform this action.', array(
					'edition' => AppHelper::getEditionName($edition)
				)));
			}
		}
	}

	/**
	 * Returns whether Craft is eligible to be upgraded to a different edition.
	 *
	 * @return bool
	 */
	public function canUpgradeEdition()
	{
		// Only admins can upgrade Craft
		if (craft()->userSession->isAdmin())
		{
			// If they're running on a testable domain, go for it
			if ($this->canTestEditions())
			{
				return true;
			}

			// Base this off of what they're actually licensed to use, not what's currently running
			$licensedEdition = $this->getLicensedEdition();
			return ($licensedEdition !== null && $licensedEdition < Craft::Pro);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns whether Craft is running on a domain that is eligible to test out the editions.
	 *
	 * @return bool
	 */
	public function canTestEditions()
	{
		return (craft()->cache->get('editionTestableDomain@'.craft()->request->getHostName()) == 1);
	}

	/**
	 * Returns the site name.
	 *
	 * @return string
	 */
	public function getSiteName()
	{
		if (!isset($this->_siteName))
		{
			// Start by checking the config
			$siteName = craft()->config->getLocalized('siteName');

			if (!$siteName)
			{
				$siteName = $this->getInfo('siteName');

				// Parse it for environment variables
				$siteName = craft()->config->parseEnvironmentString($siteName);
			}

			$this->_siteName = $siteName;
		}

		return $this->_siteName;
	}

	/**
	 * Returns the site URL (with a trailing slash).
	 *
	 * @param string|null $protocol The protocol to use (http or https). If none is specified, it will default to
	 *                              whatever's in the Site URL setting.
	 *
	 * @return string
	 */
	public function getSiteUrl($protocol = null)
	{
		if (!isset($this->_siteUrl))
		{
			// Start by checking the config
			$siteUrl = craft()->config->getLocalized('siteUrl');

			if (!$siteUrl)
			{
				if (defined('CRAFT_SITE_URL'))
				{
					$siteUrl = CRAFT_SITE_URL;
				}
				else
				{
					$siteUrl = $this->getInfo('siteUrl');
				}

				if ($siteUrl)
				{
					// Parse it for environment variables
					$siteUrl = craft()->config->parseEnvironmentString($siteUrl);
				}
				else
				{
					// Figure it out for ourselves, then
					$siteUrl = craft()->request->getBaseUrl(true);
				}
			}

			$this->setSiteUrl($siteUrl);
		}

		return UrlHelper::getUrlWithProtocol($this->_siteUrl, $protocol);
	}

	/**
	 * Sets the site URL, while ensuring that the given URL ends with a trailing slash.
	 *
	 * @param string $siteUrl
	 *
	 * @return null
	 */
	public function setSiteUrl($siteUrl)
	{
		$this->_siteUrl = rtrim($siteUrl, '/').'/';
	}

	/**
	 * Returns the system timezone.
	 *
	 * @return string
	 */
	public function getTimezone()
	{
		$timezone = craft()->config->get('timezone');

		if ($timezone)
		{
			return $timezone;
		}

		return $this->getInfo('timezone');
	}

	/**
	 * Returns the site UID.
	 *
	 * @return string
	 */
	public function getSiteUid()
	{
		return $this->getInfo('uid');
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @return bool
	 */
	public function isSystemOn()
	{
		if (is_bool($on = craft()->config->get('isSystemOn')))
		{
			return $on;
		}

		return (bool) $this->getInfo('on');
	}

	/**
	 * Returns whether the system is in maintenance mode.
	 *
	 * @return bool
	 */
	public function isInMaintenanceMode()
	{
		return (bool) $this->getInfo('maintenance');
	}

	/**
	 * Enables Maintenance Mode.
	 *
	 * @return bool
	 */
	public function enableMaintenanceMode()
	{
		return $this->_setMaintenanceMode(1);
	}

	/**
	 * Disables Maintenance Mode.
	 *
	 * @return bool
	 */
	public function disableMaintenanceMode()
	{
		return $this->_setMaintenanceMode(0);
	}

	/**
	 * Returns the info model, or just a particular attribute.
	 *
	 * @param string|null $attribute
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function getInfo($attribute = null)
	{
		if (!isset($this->_info))
		{
			if ($this->isInstalled())
			{
				$row = craft()->db->createCommand()
					->from('info')
					->limit(1)
					->queryRow();

				if (!$row)
				{
					throw new Exception(Craft::t('Craft appears to be installed but the info table is empty.'));
				}

				$this->_info = new InfoModel($row);
			}
			else
			{
				$this->_info = new InfoModel();
			}
		}

		if ($attribute)
		{
			return $this->_info->getAttribute($attribute);
		}
		else
		{
			return $this->_info;
		}
	}

	/**
	 * Updates the info row.
	 *
	 * @param InfoModel $info
	 *
	 * @return bool
	 */
	public function saveInfo(InfoModel $info)
	{
		if ($info->validate())
		{
			$attributes = $info->getAttributes(null, true);

			if ($this->isInstalled())
			{
				craft()->db->createCommand()->update('info', $attributes);
			}
			else
			{
				craft()->db->createCommand()->insert('info', $attributes);

				// Set the new id
				$info->id = craft()->db->getLastInsertID();
			}

			// Use this as the new cached InfoModel
			$this->_info = $info;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the target application language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		if (!isset($this->_language))
		{
			// Defend against an infinite getLanguage() loop
			if (!$this->_gettingLanguage)
			{
				$this->_gettingLanguage = true;
				$useUserLanguage = craft()->request->isCpRequest();
				$targetLanguage = $this->getTargetLanguage($useUserLanguage);
				$this->setLanguage($targetLanguage);
			}
			else
			{
				if (craft()->getComponent('request', false))
				{
					// We tried to get the language, but something went wrong. Use fallback to prevent infinite loop.
					$fallbackLanguage = $this->_getFallbackLanguage();
					$this->setLanguage($fallbackLanguage);
					$this->_gettingLanguage = false;
				}
				else
				{
					// Seriously?
					$this->setLanguage('en_us');
					$this->_gettingLanguage = false;
				}
			}
		}

		return $this->_language;
	}

	/**
	 * Sets the target application language.
	 *
	 * @param string $language
	 *
	 * @return null
	 */
	public function setLanguage($language)
	{
		$this->_language = $language;
	}

	/**
	 * Returns the Yii framework version.
	 *
	 * @return mixed
	 */
	public function getYiiVersion()
	{
		return \Yii::getVersion();
	}

	/**
	 * Make sure the basics are in place in the db connection file before we
	 * actually try to connect later on.
	 *
	 * @throws DbConnectException
	 * @return null
	 */
	public function validateDbConfigFile()
	{
		if ($this->_isDbConfigValid === null)
		{
			$messages = array();

			$databaseServerName = craft()->config->get('server', ConfigFile::Db);
			$databaseAuthName = craft()->config->get('user', ConfigFile::Db);
			$databaseName = craft()->config->get('database', ConfigFile::Db);
			$databasePort = craft()->config->get('port', ConfigFile::Db);
			$databaseCharset = craft()->config->get('charset', ConfigFile::Db);
			$databaseCollation = craft()->config->get('collation', ConfigFile::Db);

			if (StringHelper::isNullOrEmpty($databaseServerName))
			{
				$messages[] = Craft::t('The database server name isn’t set in your db config file.');
			}

			if (StringHelper::isNullOrEmpty($databaseAuthName))
			{
				$messages[] = Craft::t('The database user name isn’t set in your db config file.');
			}

			if (StringHelper::isNullOrEmpty($databaseName))
			{
				$messages[] = Craft::t('The database name isn’t set in your db config file.');
			}

			if (StringHelper::isNullOrEmpty($databasePort))
			{
				$messages[] = Craft::t('The database port isn’t set in your db config file.');
			}

			if (StringHelper::isNullOrEmpty($databaseCharset))
			{
				$messages[] = Craft::t('The database charset isn’t set in your db config file.');
			}

			if (StringHelper::isNullOrEmpty($databaseCollation))
			{
				$messages[] = Craft::t('The database collation isn’t set in your db config file.');
			}

			if (!empty($messages))
			{
				$this->_isDbConfigValid = false;
				throw new DbConnectException(Craft::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
			}

			$this->_isDbConfigValid = true;
		}

		return $this->_isDbConfigValid;
	}

	/**
	 * Don't even think of moving this check into DbConnection->init().
	 *
	 * @return bool
	 */
	public function getIsDbConnectionValid()
	{
		return $this->_isDbConnectionValid;
	}

	/**
	 * Don't even think of moving this check into DbConnection->init().
	 *
	 * @param $value
	 */
	public function setIsDbConnectionValid($value)
	{
		$this->_isDbConnectionValid = $value;
	}

	// Deprecated methods

	/**
	 * Returns whether a package is included in this Craft build.
	 *
	 * @param $packageName
	 *
	 * @deprecated Deprecated in 2.0. To get the installed Craft edition, use
	 *             {@link AppBehavior::getEdition() craft()->getEdition()}.
	 * @return bool
	 */
	public function hasPackage($packageName)
	{
		return $this->getEdition() == Craft::Pro;
	}

	/**
	 * Returns the target app language.
	 *
	 * @param bool Whether the user's preferred language should be used
	 * @return string|null
	 */
	public function getTargetLanguage($useUserLanguage = true)
	{
		if ($this->isInstalled())
		{
			// Will any locale validation be necessary here?
			if ($useUserLanguage || defined('CRAFT_LOCALE'))
			{
				if ($useUserLanguage)
				{
					$locale = 'auto';
				}
				else
				{
					$locale = StringHelper::toLowerCase(CRAFT_LOCALE);
				}

				// Get the list of actual site locale IDs
				$siteLocaleIds = craft()->i18n->getSiteLocaleIds();

				// Is it set to "auto"?
				if ($locale == 'auto')
				{
					// Prevents a PHP notice in case the session failed to start, for whatever reason.
					if (craft()->getComponent('userSession', false))
					{
						// Place this within a try/catch in case userSession is being fussy.
						try
						{
							// If the user is logged in *and* has a primary language set, use that
							$user = craft()->userSession->getUser();

							if ($user && $user->preferredLocale)
							{
								return $user->preferredLocale;
							}
						} catch (\Exception $e)
						{
							Craft::log("Tried to determine the user's preferred locale, but got this exception: ".$e->getMessage(), LogLevel::Error);
						}
					}

					// Otherwise check if the browser's preferred language matches any of the site locales
					$browserLanguages = craft()->request->getBrowserLanguages();

					if ($browserLanguages)
					{
						foreach ($browserLanguages as $language)
						{
							if (in_array($language, $siteLocaleIds))
							{
								return $language;
							}
						}
					}
				}

				// Is it set to a valid site locale?
				else if (in_array($locale, $siteLocaleIds))
				{
					return $locale;
				}
			}

			// Use the primary site locale by default
			return craft()->i18n->getPrimarySiteLocaleId();
		}
		else
		{
			return $this->_getFallbackLanguage();
		}
	}

	/**
	 * Creates a {@link DbConnection} specifically initialized for Craft's craft()->db instance.
	 *
	 * @throws DbConnectException
	 * @return DbConnection
	 */
	public function createDbConnection()
	{
		try
		{
			$dbConnection = new DbConnection();

			$dbConnection->connectionString = $this->_processConnectionString();
			$dbConnection->emulatePrepare   = true;
			$dbConnection->username         = craft()->config->get('user', ConfigFile::Db);
			$dbConnection->password         = craft()->config->get('password', ConfigFile::Db);
			$dbConnection->charset          = craft()->config->get('charset', ConfigFile::Db);
			$dbConnection->tablePrefix      = $dbConnection->getNormalizedTablePrefix();
			$dbConnection->driverMap        = array('mysql' => 'Craft\MysqlSchema');

			$dbConnection->init();
		}
		// Most likely missing PDO in general or the specific database PDO driver.
		catch(\CDbException $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);

			// TODO: Multi-db driver check.
			if (!extension_loaded('pdo'))
			{
				throw new DbConnectException(Craft::t('Craft requires the PDO extension to operate.'));
			}
			else if (!extension_loaded('pdo_mysql'))
			{
				throw new DbConnectException(Craft::t('Craft requires the PDO_MYSQL driver to operate.'));
			}
			else
			{
				Craft::log($e->getMessage(), LogLevel::Error);
				throw new DbConnectException(Craft::t('Craft can’t connect to the database with the credentials in craft/config/db.php.'));
			}
		}
		catch (\Exception $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);
			throw new DbConnectException(Craft::t('Craft can’t connect to the database with the credentials in craft/config/db.php.'));
		}

		$this->setIsDbConnectionValid(true);

		// Now that we've validated the config and connection, set extra db logging if devMode is enabled.
		if (craft()->config->get('devMode'))
		{
			$dbConnection->enableProfiling = true;
			$dbConnection->enableParamLogging = true;
		}

		return $dbConnection;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Enables or disables Maintenance Mode
	 *
	 * @param bool $value
	 *
	 * @return bool
	 */
	private function _setMaintenanceMode($value)
	{
		$info = $this->getInfo();
		$info->maintenance = $value;

		return $this->saveInfo($info);
	}

	/**
	 * Returns the correct connection string depending on whether a unixSocket is specified or not in the db config.
	 *
	 * @return string
	 */
	private function _processConnectionString()
	{
		$unixSocket = craft()->config->get('unixSocket', ConfigFile::Db);

		if (!empty($unixSocket))
		{
			return strtolower('mysql:unix_socket='.$unixSocket.';dbname=').craft()->config->get('database', ConfigFile::Db).';';
		}
		else
		{
			return strtolower('mysql:host='.craft()->config->get('server', ConfigFile::Db).';dbname=').craft()->config->get('database', ConfigFile::Db).strtolower(';port='.craft()->config->get('port', ConfigFile::Db).';');
		}
	}

	/**
	 * Tries to find a language match with the user's browser's preferred language(s).
	 * If not uses the app's sourceLanguage.
	 *
	 * @return string
	 */
	private function _getFallbackLanguage()
	{
		// See if we have the CP translated in one of the user's browsers preferred language(s)
		$language = craft()->getTranslatedBrowserLanguage();

		// Default to the source language.
		if (!$language)
		{
			$language = craft()->sourceLanguage;
		}

		return $language;
	}
}
