<?php
namespace Blocks;

/**
 * Update functions
 */
class UpdatesVariable
{
	/**
	 * Returns whether the update info is cached.
	 * @return bool
	 */
	public function cached()
	{
		return b()->updates->getIsUpdateInfoCached();
	}

	/**
	 * Returns all available updates.
	 * @param bool $forceRefresh
	 * @return array
	 */
	public function all($forceRefresh = false)
	{
		return b()->updates->getAllAvailableUpdates($forceRefresh);
	}

	/**
	 * Returns whether a critical update is available.
	 * @return bool
	 */
	public function critical()
	{
		return b()->updates->getIsCriticalUpdateAvailable();
	}

	/**
	 * Returns the directories that need to be set to writable.
	 * @return array
	 */
	public function unwritabledirectories()
	{
		return b()->updates->getUnwritableDirectories();
	}
}
