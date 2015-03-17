<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\db\Connection;
use craft\app\db\Query;
use craft\app\enums\CacheMethod;
use craft\app\enums\ConfigCategory;
use craft\app\errors\DbConnectException;
use craft\app\errors\Exception;
use craft\app\helpers\AppHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;
use craft\app\i18n\Locale;
use craft\app\log\FileTarget;
use craft\app\models\Info;
use yii\base\InvalidConfigException;

/**
 * ApplicationTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait ApplicationTrait
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

	/**
	 * @var string Craft’s build number.
	 */
	public $build;

	/**
	 * @var string Craft’s schema version number.
	 */
	public $schemaVersion;

	/**
	 * @var string Craft’s release date.
	 */
	public $releaseDate;

	/**
	 * @var string The minimum Craft build number required to update to this build.
	 */
	public $minBuildRequired;

	/**
	 * @var string The URL to download the minimum Craft version.
	 * @see $minBuildRequired
	 */
	public $minBuildUrl;

	/**
	 * @var string The release track Craft is running on.
	 */
	public $track;

	// Public Methods
	// =========================================================================

	/**
	 * Returns a [[Locale]] object for the target language.
	 *
	 * @return Locale The locale object for the target language.
	 */
	public function getLocale()
	{
		return $this->get('locale');
	}

	/**
	 * Returns the target app language.
	 *
	 * @param boolean $useUserLanguage Whether the user's preferred language should be used.
	 * @return string|null
	 */
	public function getTargetLanguage($useUserLanguage = true)
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if ($this->isInstalled())
		{
			// Will any locale validation be necessary here?
			$request = $this->getRequest();

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
				$siteLocaleIds = $this->getI18n()->getSiteLocaleIds();

				// Is it set to "auto"?
				if ($locale == 'auto')
				{
					// Place this within a try/catch in case userSession is being fussy.
					try
					{
						// If the user is logged in *and* has a primary language set, use that
						$user = $this->getUser()->getIdentity();

						if ($user && $user->preferredLocale)
						{
							return $user->preferredLocale;
						}
					}
					catch (\Exception $e)
					{
						Craft::error('Tried to determine the user’s preferred locale, but got this exception: '.$e->getMessage(), __METHOD__);
					}

					// Otherwise check if the browser's preferred language matches any of the site locales
					if (!$request->getIsConsoleRequest())
					{
						$browserLanguages = $request->getAcceptableLanguages();

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
				}

				// Is it set to a valid site locale?
				else if (in_array($locale, $siteLocaleIds))
				{
					return $locale;
				}
			}

			// Use the primary site locale by default
			return $this->getI18n()->getPrimarySiteLocaleId();
		}
		else
		{
			return $this->_getFallbackLanguage();
		}
	}

	/**
	 * Determines if Craft is installed by checking if the info table exists.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if (!isset($this->_isInstalled))
		{
			try
			{
				// Initialize the DB connection
				$this->getDb();

				// If the db config isn't valid, then we'll assume it's not installed.
				if (!$this->getIsDbConnectionValid())
				{
					return false;
				}
			}
			catch (DbConnectException $e)
			{
				return false;
			}

			$this->_isInstalled = ($this->getRequest()->getIsConsoleRequest() || $this->getDb()->tableExists('{{%info}}', false));
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if (!isset($this->_isLocalized))
		{
			$this->_isLocalized = ($this->getEdition() == Craft::Pro && count($this->getI18n()->getSiteLocales()) > 1);
		}

		return $this->_isLocalized;
	}

	/**
	 * Returns the Craft edition.
	 *
	 * @return int
	 */
	public function getEdition()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return $this->getInfo('edition');
	}

	/**
	 * Returns the name of the Craft edition.
	 *
	 * @return string
	 */
	public function getEditionName()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return AppHelper::getEditionName($this->getEdition());
	}

	/**
	 * Returns the edition Craft is actually licensed to run in.
	 *
	 * @return int|null
	 */
	public function getLicensedEdition()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$licensedEdition = $this->getCache()->get('licensedEdition');

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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if ($this->isInstalled())
		{
			$installedEdition = $this->getEdition();

			if (($orBetter && $installedEdition < $edition) || (!$orBetter && $installedEdition != $edition))
			{
				throw new Exception(Craft::t('app', 'Craft {edition} is required to perform this action.', [
					'edition' => AppHelper::getEditionName($edition)
				]));
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		// Only admins can upgrade Craft
		if ($this->getUser()->getIsAdmin())
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$request = $this->getRequest();
		return (!$request->getIsConsoleRequest() && $this->getCache()->get('editionTestableDomain@'.$request->getHostName()) == 1);
	}

	/**
	 * Returns the site name.
	 *
	 * @return string
	 */
	public function getSiteName()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if (!isset($this->_siteName))
		{
			// Start by checking the config
			$siteName = $this->config->getLocalized('siteName');

			if (!$siteName)
			{
				$siteName = $this->getInfo('siteName');

				// Parse it for environment variables
				$siteName = $this->config->parseEnvironmentString($siteName);
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if (!isset($this->_siteUrl))
		{
			// Start by checking the config
			$siteUrl = $this->config->getLocalized('siteUrl');

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
					$siteUrl = $this->config->parseEnvironmentString($siteUrl);
				}
				else
				{
					// Figure it out for ourselves, then
					$siteUrl = $this->getRequest()->getBaseUrl(true);
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$this->_siteUrl = rtrim($siteUrl, '/').'/';
	}

	/**
	 * Returns the site UID.
	 *
	 * @return string
	 */
	public function getSiteUid()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return $this->getInfo('uid');
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @return bool
	 */
	public function isSystemOn()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if (is_bool($on = $this->config->get('isSystemOn')))
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return (bool) $this->getInfo('maintenance');
	}

	/**
	 * Enables Maintenance Mode.
	 *
	 * @return bool
	 */
	public function enableMaintenanceMode()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return $this->_setMaintenanceMode(1);
	}

	/**
	 * Disables Maintenance Mode.
	 *
	 * @return bool
	 */
	public function disableMaintenanceMode()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return $this->_setMaintenanceMode(0);
	}

	/**
	 * Returns the info model, or just a particular attribute.
	 *
	 * @param string|null $attribute
	 *
	 * @throws Exception
	 * @return Info|string
	 */
	public function getInfo($attribute = null)
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if (!isset($this->_info))
		{
			if ($this->isInstalled())
			{
				$row = (new Query())
					->from('{{%info}}')
					->one();

				if (!$row)
				{
					throw new Exception(Craft::t('app', 'Craft appears to be installed but the info table is empty.'));
				}

				$this->_info = new Info($row);
			}
			else
			{
				$this->_info = new Info();
			}
		}

		if ($attribute)
		{
			return $this->_info->$attribute;
		}
		else
		{
			return $this->_info;
		}
	}

	/**
	 * Updates the info row.
	 *
	 * @param Info $info
	 *
	 * @return bool
	 */
	public function saveInfo(Info $info)
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if ($info->validate())
		{
			$attributes = $info->toArray();

			if ($this->isInstalled())
			{
				$this->getDb()->createCommand()->update('{{%info}}', $attributes)->execute();
			}
			else
			{
				$this->getDb()->createCommand()->insert('{{%info}}', $attributes)->execute();

				// Set the new id
				$info->id = $this->getDb()->getLastInsertID();
			}

			// Use this as the new cached Info
			$this->_info = $info;

			return true;
		}
		else
		{
			return false;
		}
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if ($this->_isDbConfigValid === null)
		{
			$messages = [];

			$databaseServerName = $this->config->get('server', ConfigCategory::Db);
			$databaseAuthName = $this->config->get('user', ConfigCategory::Db);
			$databaseName = $this->config->get('database', ConfigCategory::Db);
			$databasePort = $this->config->get('port', ConfigCategory::Db);
			$databaseCharset = $this->config->get('charset', ConfigCategory::Db);
			$databaseCollation = $this->config->get('collation', ConfigCategory::Db);

			if (!$databaseServerName)
			{
				$messages[] = Craft::t('app', 'The database server name isn’t set in your db config file.');
			}

			if (!$databaseAuthName)
			{
				$messages[] = Craft::t('app', 'The database user name isn’t set in your db config file.');
			}

			if (!$databaseName)
			{
				$messages[] = Craft::t('app', 'The database name isn’t set in your db config file.');
			}

			if (!$databasePort)
			{
				$messages[] = Craft::t('app', 'The database port isn’t set in your db config file.');
			}

			if (!$databaseCharset)
			{
				$messages[] = Craft::t('app', 'The database charset isn’t set in your db config file.');
			}

			if (!$databaseCollation)
			{
				$messages[] = Craft::t('app', 'The database collation isn’t set in your db config file.');
			}

			if (!empty($messages))
			{
				$this->_isDbConfigValid = false;
				throw new DbConnectException(Craft::t('app', 'Database configuration errors: {errors}', ['errors' => implode(PHP_EOL, $messages)]));
			}

			$this->_isDbConfigValid = true;
		}

		return $this->_isDbConfigValid;
	}

	/**
	 * Don't even think of moving this check into Connection->init().
	 *
	 * @return bool
	 */
	public function getIsDbConnectionValid()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return $this->_isDbConnectionValid;
	}

	/**
	 * Don't even think of moving this check into Connection->init().
	 *
	 * @param $value
	 */
	public function setIsDbConnectionValid($value)
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$this->_isDbConnectionValid = $value;
	}

	/**
	 * Configures the available log targets.
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	public function processLogTargets()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$dispatcher = $this->getLog();

		// Don't setup a target if it's an enabled session.
		if ($this->getUser()->enableSession)
		{
			$fileTarget = new FileTarget();
			$fileTarget->logFile = Craft::getAlias('@storage/logs/craft.log');
			$fileTarget->fileMode = Craft::$app->config->get('defaultFilePermissions');
			$fileTarget->dirMode = Craft::$app->config->get('defaultFolderPermissions');

			if (!Craft::$app->config->get('devMode') || !$this->_isCraftUpdating())
			{
				//$fileTarget->setLevels(array(Logger::LEVEL_ERROR, Logger::LEVEL_WARNING));
			}

			$dispatcher->targets[] = $fileTarget;
		}

		$this->set('log', $dispatcher);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the definition for a given application component ID, in which we need to take special care on.
	 *
	 * @param string $id
	 * @return mixed
	 */
	private function _getComponentDefinition($id)
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		switch ($id)
		{
			case 'cache':
				return $this->_getCacheDefinition();
			case 'db':
				return $this->_getDbDefinition();
			case 'formatter':
				return $this->getLocale()->getFormatter();
			case 'locale':
				return $this->_getLocaleDefinition();
		}
	}

	/**
	 * Returns the definition for the [[\yii\caching\Cache]] object that will be available from Craft::$app->cache.
	 *
	 * @return string|array
	 * @throws InvalidConfigException
	 */
	private function _getCacheDefinition()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$configService = Craft::$app->config;
		$cacheMethod = $configService->get('cacheMethod');

		switch ($cacheMethod)
		{
			case CacheMethod::APC:
			{
				return 'craft\app\cache\ApcCache';
			}

			case CacheMethod::Db:
			{
				return [
					'class' => 'craft\app\cache\DbCache',
					'gcProbability' => $configService->get('gcProbability', ConfigCategory::DbCache),
					'cacheTableName' => $this->_getNormalizedTablePrefix().$configService->get('cacheTableName', ConfigCategory::DbCache),
					'autoCreateCacheTable' => true,
				];
			}

			case CacheMethod::File:
			{
				return [
					'class' => 'craft\app\cache\FileCache',
					'cachePath' => $configService->get('cachePath', ConfigCategory::FileCache),
					'gcProbability' => $configService->get('gcProbability', ConfigCategory::FileCache),
				];
			}

			case CacheMethod::MemCache:
			{
				return [
					'class' => 'craft\app\cache\MemCache',
					'servers' => $configService->get('servers', ConfigCategory::Memcache),
					'useMemcached' => $configService->get('useMemcached', ConfigCategory::Memcache),
				];
			}

			case CacheMethod::WinCache:
			{
				return 'craft\app\cache\WinCache';
			}

			case CacheMethod::XCache:
			{
				return 'craft\app\cache\XCache';
			}

			case CacheMethod::ZendData:
			{
				return 'craft\app\cache\ZendDataCache';
			}

			default:
			{
				throw new InvalidConfigException('Unsupported cacheMethod config setting value: '.$cacheMethod);
			}
		}
	}

	/**
	 * Returns the definition for the [[Command]] object that will be available from Craft::$app->db.
	 *
	 * @return Connection
	 * @throws DbConnectException
	 */
	private function _getDbDefinition()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$configService = $this->config;

		try
		{
			$config = [
				'class' => 'craft\app\db\Connection',
				'dsn' => $this->_processConnectionString(),
				'emulatePrepare' => true,
				'username' => $configService->get('user', ConfigCategory::Db),
				'password' => $configService->get('password', ConfigCategory::Db),
				'charset' => $configService->get('charset', ConfigCategory::Db),
				'tablePrefix' => $this->_getNormalizedTablePrefix(),
				'schemaMap' => [
					'mysql' => '\\craft\\app\\db\\mysql\\Schema',
				],
			];

			$db = Craft::createObject($config);
			$db->open();
		}
		// Most likely missing PDO in general or the specific database PDO driver.
		catch(\yii\db\Exception $e)
		{
			Craft::error($e->getMessage(), __METHOD__);

			// TODO: Multi-db driver check.
			if (!extension_loaded('pdo'))
			{
				throw new DbConnectException(Craft::t('app', 'Craft requires the PDO extension to operate.'));
			}
			else if (!extension_loaded('pdo_mysql'))
			{
				throw new DbConnectException(Craft::t('app', 'Craft requires the PDO_MYSQL driver to operate.'));
			}
			else
			{
				Craft::error($e->getMessage(), __METHOD__);
				throw new DbConnectException(Craft::t('app', 'Craft can’t connect to the database with the credentials in craft/config/db.php.'));
			}
		}
		catch (\Exception $e)
		{
			Craft::error($e->getMessage(), __METHOD__);
			throw new DbConnectException(Craft::t('app', 'Craft can’t connect to the database with the credentials in craft/config/db.php.'));
		}

		$this->setIsDbConnectionValid(true);

		return $db;
	}

	/**
	 * Returns the definition for the [[Locale]] object that will be available from Craft::$app->locale.
	 *
	 * @return Locale
	 */
	private function _getLocaleDefinition()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		return new Locale($this->language);
	}

	/**
	 * Returns the application’s configured DB table prefix.
	 *
	 * @return string
	 */
	private function _getNormalizedTablePrefix()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		// Table prefixes cannot be longer than 5 characters
		$tablePrefix = rtrim($this->config->get('tablePrefix', ConfigCategory::Db), '_');

		if ($tablePrefix)
		{
			if (StringHelper::length($tablePrefix) > 5)
			{
				$tablePrefix = substr($tablePrefix, 0, 5);
			}

			$tablePrefix .= '_';
		}
		else
		{
			$tablePrefix = '';
		}

		return $tablePrefix;
	}

	/**
	 * Returns the target application language.
	 *
	 * @return string
	 */
	private function _getLanguage()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		if (!isset($this->_language))
		{
			// Defend against an infinite getLanguage() loop
			if (!$this->_gettingLanguage)
			{
				$this->_gettingLanguage = true;
				$request = $this->getRequest();
				$useUserLanguage = $request->getIsConsoleRequest() || $request->getIsCpRequest();
				$targetLanguage = $this->getTargetLanguage($useUserLanguage);
				$this->setLanguage($targetLanguage);
			}
			else
			{
				// We tried to get the language, but something went wrong. Use fallback to prevent infinite loop.
				$fallbackLanguage = $this->_getFallbackLanguage();
				$this->setLanguage($fallbackLanguage);
				$this->_gettingLanguage = false;
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
	private function _setLanguage($language)
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$this->_language = $language;
	}

	/**
	 * Returns the system timezone.
	 *
	 * @return string
	 */
	private function _getTimeZone()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$timezone = $this->config->get('timezone');

		if ($timezone)
		{
			return $timezone;
		}

		return $this->getInfo('timezone');
	}

	/**
	 * Enables or disables Maintenance Mode
	 *
	 * @param bool $value
	 *
	 * @return bool
	 */
	private function _setMaintenanceMode($value)
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$unixSocket = $this->config->get('unixSocket', ConfigCategory::Db);

		if (!empty($unixSocket))
		{
			return strtolower('mysql:unix_socket='.$unixSocket.';dbname=').$this->config->get('database', ConfigCategory::Db).';';
		}
		else
		{
			return strtolower('mysql:host='.$this->config->get('server', ConfigCategory::Db).';dbname=').$this->config->get('database', ConfigCategory::Db).strtolower(';port='.$this->config->get('port', ConfigCategory::Db).';');
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
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		// See if we have the CP translated in one of the user's browsers preferred language(s)
		$language = $this->getTranslatedBrowserLanguage();

		// Default to the source language.
		if (!$language)
		{
			$language = $this->sourceLanguage;
		}

		return $language;
	}

	/**
	 * Sets the edition components.
	 *
	 * @return null
	 */
	private function _setEditionComponents()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		// Set the appropriate edition components
		$edition = $this->getEdition();

		if ($edition == Craft::Client || $edition == Craft::Pro)
		{
			$pathService = $this->path;

			$this->setComponents(require $pathService->getAppPath().'/config/components/client.php');

			if ($edition == Craft::Pro)
			{
				$this->setComponents(require $pathService->getAppPath().'/config/components/pro.php');
			}
		}
	}

	/**
	 * Returns whether Craft is in the middle of an update.
	 *
	 * @return bool
	 */
	private function _isCraftUpdating()
	{
		/* @var $this \craft\app\web\Application|\craft\app\console\Application */
		$request = $this->getRequest();

		if ($this->updates->isCraftDbMigrationNeeded() ||
			($this->isInMaintenanceMode() && $request->getIsCpRequest()) ||
			$request->getActionSegments() == ['update', 'cleanUp'] ||
			$request->getActionSegments() == ['update', 'rollback']
		)
		{
			return true;
		}

		return false;
	}
}
