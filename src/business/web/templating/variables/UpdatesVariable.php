<?php
namespace Blocks;

/**
 * Update functions
 */
class UpdatesVariable
{
	/**
	 * Returns whether the update info is cached.
	 */
	public function cached()
	{
		return b()->updates->getIsUpdateInfoCached();
	}

	/**
	 * Returns all available updates.
	 */
	public function all($forceRefresh = false)
	{
		return b()->updates->getAllAvailableUpdates($forceRefresh);
	}

	/**
	 * Returns whether a critical update is available.
	 */
	public function critical()
	{
		return b()->updates->getIsCriticalUpdateAvailable();
	}

	/**
	 * Retuns the directories that need to be set to writable.
	 */
	public function unwritabledirectories()
	{
		return b()->updates->getUnwritableDirectories();
	}
}
