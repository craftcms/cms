<?php
namespace Blocks;

/**
 * Update functions
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
		return blx()->updates->isUpdateInfoCached();
	}

	/**
	 * Returns whether a critical update is available.
	 *
	 * @return bool
	 */
	public function isCriticalUpdateAvailable()
	{
		return blx()->updates->isCriticalUpdateAvailable();
	}

	/**
	 * Returns the folders that need to be set to writable.
	 *
	 * @return array
	 */
	public function getUnwritableFolders()
	{
		return blx()->updates->getUnwritableFolders();
	}

	/**
	 * @param bool $forceRefresh
	 * @return mixed
	 */
	public function getUpdates($forceRefresh = false)
	{
		return blx()->updates->getUpdates($forceRefresh);
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
		if (blx()->updates->isBlocksDbUpdateNeeded())
		{
			return 'Blocks';
		}

		$plugins = blx()->updates->getPluginsThatNeedDbUpdate();

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
