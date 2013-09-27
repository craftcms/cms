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

	private $_packageList = array('Users', 'PublishPro', 'Localize', 'Cloud', 'Rebrand');

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
				// If the db config isn't valid, then we'll assume it's not installed.
				if (!craft()->db->isDbConnectionValid())
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
	 * Returns the packages in this Craft install, as defined in the craft_info table.
	 *
	 * @return array|null
	 */
	public function getPackages()
	{
		return $this->getInfo('packages');
	}

	/**
	 * Returns whether a package is included in this Craft build.
	 *
	 * @param $packageName
	 * @return bool
	 */
	public function hasPackage($packageName)
	{
		return in_array($packageName, $this->getPackages());
	}

	/**
	 * Requires that a given package is installed.
	 *
	 * @param string $packageName
	 * @throws Exception
	 */
	public function requirePackage($packageName)
	{
		if ($this->isInstalled() && !$this->hasPackage($packageName))
		{
			throw new Exception(Craft::t('The {package} package is required to perform this action.', array(
				'package' => Craft::t($packageName)
			)));
		}
	}

	/**
	 * Installs a package.
	 *
	 * @param string $packageName
	 * @throws Exception
	 * @return bool
	 */
	public function installPackage($packageName)
	{
		$this->_validatePackageName($packageName);

		if ($this->hasPackage($packageName))
		{
			throw new Exception(Craft::t('The {package} package is already installed.', array(
				'package' => Craft::t($packageName)
			)));
		}

		$installedPackages = $this->getPackages();
		$installedPackages[] = $packageName;

		$info = $this->getInfo();
		$info->packages = $installedPackages;
		return $this->saveInfo($info);
	}

	/**
	 * Uninstalls a package.
	 *
	 * @param string $packageName
	 * @throws Exception
	 * @return bool
	 */
	public function uninstallPackage($packageName)
	{
		$this->_validatePackageName($packageName);

		if (!$this->hasPackage($packageName))
		{
			throw new Exception(Craft::t('The {package} package isn’t installed.', array(
				'package' => Craft::t($packageName)
			)));
		}

		$installedPackages = $this->getPackages();
		$index = array_search($packageName, $installedPackages);
		array_splice($installedPackages, $index, 1);

		$info = $this->getInfo();
		$info->packages = $installedPackages;
		return $this->saveInfo($info);
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
	 * Returns the site URL.
	 *
	 * @param string|null $protocol The protocol to use (http or https). If none is specified, it will default to whatever's in the Site URL setting.
	 * @return string
	 */
	public function getSiteUrl($protocol = null)
	{
		if (!isset($this->_siteUrl))
		{
			if (defined('CRAFT_SITE_URL'))
			{
				$storedSiteUrl = CRAFT_SITE_URL;
			}
			else
			{
				$storedSiteUrl = $this->getInfo('siteUrl');
			}

			if ($storedSiteUrl)
			{
				// Parse it for environment variables
				$storedSiteUrl = craft()->config->parseEnvironmentString($storedSiteUrl);

				$port = craft()->request->getPort();

				// If this is port 80, or the port is already a part of the stored site URL, don't worry about adding it.
				// i.e. http://localhost:8888/craft
				if ($port == 80 || mb_strpos($storedSiteUrl, ':'.$port) !== false)
				{
					$port = '';
				}
				else
				{
					$port = ':'.$port;
				}

				$this->_siteUrl = rtrim($storedSiteUrl, '/').$port.'/';
			}
			else
			{
				$this->_siteUrl = '';
			}
		}

		switch ($protocol)
		{
			case 'http':
			{
				return str_replace('https://', 'http://', $this->_siteUrl);
			}

			case 'https':
			{
				return str_replace('http://', 'https://', $this->_siteUrl);
			}

			default:
			{
				return $this->_siteUrl;
			}
		}
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
	 * Returns the system time zone.
	 *
	 * @return string
	 */
	public function getTimezone()
	{
		return $this->getInfo('timezone');
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
	 * Turns the system on or off.
	 *
	 * @access private
	 * @param bool $value
	 * @return bool
	 */
	private function _setSystemStatus($value)
	{
		$info = $this->getInfo();
		$info->on = $value;
		return $this->saveInfo($info);
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

	/**
	 * Validates a package name.
	 *
	 * @access private
	 * @throws Exception
	 */
	private function _validatePackageName($packageName)
	{
		if (!in_array($packageName, $this->_packageList))
		{
			throw new Exception(Craft::t('Craft doesn’t have a package named “{package}”', array(
				'package' => Craft::t($packageName)
			)));
		}
	}
}
