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
	public function getManualUpdateHandle()
	{
		if (blx()->updates->isBlocksDbUpdateNeeded())
		{
			return 'Blocks';
		}

		$plugins = blx()->updates->getPluginsThatNeedDbUpdate();

		if (!empty($plugins) && isset($plugins[0]))
		{
			return $plugins[0]->getClassHandle();
		}

		return null;
	}
}
