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
	public function cached()
	{
		return blx()->updates->getIsUpdateInfoCached();
	}

	/**
	 * Returns whether a critical update is available.
	 *
	 * @return bool
	 */
	public function critical()
	{
		return blx()->updates->getIsCriticalUpdateAvailable();
	}

	/**
	 * Returns the directories that need to be set to writable.
	 *
	 * @return array
	 */
	public function unwritabledirectories()
	{
		return blx()->updates->getUnwritableDirectories();
	}

	/**
	 * @param bool $forceRefresh
	 * @return mixed
	 */
	public function updateinfo($forceRefresh = false)
	{
		return blx()->updates->getUpdateInfo($forceRefresh);
	}
}
