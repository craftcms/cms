<?php
namespace Craft;

/**
 * Class AppBehavior
 *
 * @package Craft
 */
class AppBehavior extends BaseBehavior
{
	private $_isInstalled;
	private $_info;
	private $_siteName;
	private $_siteUrl;
	private $_isDbConfigValid = false;
	private $_isDbConnectionValid = false;

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
	 */
	public function setIsInstalled()
	{
		// If you say so!
		$this->_isInstalled = true;
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
	 * @return string
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
		return $this->_getEditionName($this->getEdition());
	}

	/**
	 * Sets the Craft edition.
	 *
	 * @param string $edition
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
	 * @param string $edition
	 * @throws Exception
	 */
	public function requireEdition($edition)
	{
		if ($this->isInstalled() && $this->getEdition() < $edition)
		{
			throw new Exception(Craft::t('Craft {edition} is required to perform this action.', array(
				'edition' => $this->_getEditionName($edition)
			)));
		}
	}

	/**
	 * Returns whether Craft is running on a domain that is elligible to test out the editions.
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
			$siteName = $this->getInfo('siteName');
			$this->_siteName = craft()->config->parseEnvironmentString($siteName);
		}

		return $this->_siteName;
	}

	/**
	 * Returns the site URL (with a trailing slash).
	 *
	 * @param string|null $protocol The protocol to use (http or https). If none is specified, it will default to whatever's in the Site URL setting.
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
	 */
	public function setSiteUrl($siteUrl)
	{
		$this->_siteUrl = rtrim($siteUrl, '/').'/';
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
	 * Returns the Yii framework version.
	 *
	 * @return mixed
	 */
	public function getYiiVersion()
	{
		return \Yii::getVersion();
	}

	/**
	 * Make sure the basics are in place in the db connection file before we actually try to connect later on.
	 *
	 * @throws DbConnectException
	 */
	public function validateDbConfigFile()
	{
		if ($this->_isDbConfigValid === null)
		{
			$messages = array();

			$databaseServerName = craft()->config->getDbItem('server');
			$databaseAuthName = craft()->config->getDbItem('user');
			$databaseName = craft()->config->getDbItem('database');
			$databasePort = craft()->config->getDbItem('port');
			$databaseCharset = craft()->config->getDbItem('charset');
			$databaseCollation = craft()->config->getDbItem('collation');

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
	 * @return bool
	 * @deprecated Deprecated in 2.0
	 */
	public function hasPackage($packageName)
	{
		return $this->getEdition() == Craft::Pro;
	}

	// Private methods

	/**
	 * Returns the name of the given Craft edition.
	 *
	 * @param int $edition
	 * @return string
	 */
	private function _getEditionName($edition)
	{
		switch ($edition)
		{
			case Craft::Client:
			{
				return 'Client';
			}
			case Craft::Pro:
			{
				return 'Pro';
			}
			default:
			{
				return 'Personal';
			}
		}
	}

	/**
	 * Enables or disables Maintenance Mode
	 *
	 * @access private
	 * @param bool $value
	 * @return bool
	 */
	private function _setMaintenanceMode($value)
	{
		$info = $this->getInfo();
		$info->maintenance = $value;
		return $this->saveInfo($info);
	}
}
