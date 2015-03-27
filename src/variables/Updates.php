<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Update functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Updates
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether the update info is cached.
	 *
	 * @return bool
	 */
	public function isUpdateInfoCached()
	{
		return \Craft::$app->updates->isUpdateInfoCached();
	}

	/**
	 * Returns whether a critical update is available.
	 *
	 * @return bool
	 */
	public function isCriticalUpdateAvailable()
	{
		return \Craft::$app->updates->isCriticalUpdateAvailable();
	}

	/**
	 * Returns the folders that need to be set to writable.
	 *
	 * @return array
	 */
	public function getUnwritableFolders()
	{
		return \Craft::$app->updates->getUnwritableFolders();
	}

	/**
	 * @param bool $forceRefresh
	 *
	 * @return mixed
	 */
	public function getUpdates($forceRefresh = false)
	{
		return \Craft::$app->updates->getUpdates($forceRefresh);
	}

	/**
	 * @return string|null
	 */
	public function getManualUpdateDisplayName()
	{
		return $this->_getManualUpdateInfo('name');
	}

	/**
	 * @return string|null
	 */
	public function getManualUpdateHandle()
	{
		return $this->_getManualUpdateInfo('handle');
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param string $type
	 *
	 * @return string|null
	 */
	private function _getManualUpdateInfo($type)
	{
		if (\Craft::$app->updates->isCraftDbMigrationNeeded())
		{
			return 'Craft';
		}

		$plugins = \Craft::$app->updates->getPluginsThatNeedDbUpdate();

		if (!empty($plugins) && isset($plugins[0]))
		{
			if ($type == 'name')
			{
				return $plugins[0]->getName();
			}
			else
			{
				//return $plugins[0]->getClassHandle();
			}
		}

		return null;
	}
}
