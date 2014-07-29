<?php
namespace Craft;

/**
 * Update functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     1.0
 */
class UpdatesVariable
{
	/**
	 * Returns whether the update info is cached.
	 *
	 * @return bool
	 */
	public function isUpdateInfoCached()
	{
		return craft()->updates->isUpdateInfoCached();
	}

	/**
	 * Returns whether a critical update is available.
	 *
	 * @return bool
	 */
	public function isCriticalUpdateAvailable()
	{
		return craft()->updates->isCriticalUpdateAvailable();
	}

	/**
	 * Returns the folders that need to be set to writable.
	 *
	 * @return array
	 */
	public function getUnwritableFolders()
	{
		return craft()->updates->getUnwritableFolders();
	}

	/**
	 * @param bool $forceRefresh
	 * @return mixed
	 */
	public function getUpdates($forceRefresh = false)
	{
		return craft()->updates->getUpdates($forceRefresh);
	}

	/**
	 * @return null|string
	 */
	public function getManualUpdateDisplayName()
	{
		return $this->_getManualUpdateInfo('name');
	}

	/**
	 * @return null|string
	 */
	public function getManualUpdateHandle()
	{
		return $this->_getManualUpdateInfo('handle');
	}

	/**
	 * @param $type
	 * @return null|string
	 */
	private function _getManualUpdateInfo($type)
	{
		if (craft()->updates->isCraftDbMigrationNeeded())
		{
			return 'Craft';
		}

		$plugins = craft()->updates->getPluginsThatNeedDbUpdate();

		if (!empty($plugins) && isset($plugins[0]))
		{
			if ($type == 'name')
			{
				return $plugins[0]->getName();
			}
			else
			{
				return $plugins[0]->getClassHandle();
			}
		}

		return null;
	}
}
