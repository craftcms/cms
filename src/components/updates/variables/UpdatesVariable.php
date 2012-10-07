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
		return blx()->updates->isUpdateInfoCached();
	}

	/**
	 * Returns whether a critical update is available.
	 *
	 * @return bool
	 */
	public function critical()
	{
		return blx()->updates->isCriticalUpdateAvailable();
	}

	/**
	 * Returns the folders that need to be set to writable.
	 *
	 * @return array
	 */
	public function unwritablefolders()
	{
		return blx()->updates->getUnwritableFolders();
	}

	/**
	 * @param bool $forceRefresh
	 * @return mixed
	 */
	public function updateinfo($forceRefresh = false)
	{
		return blx()->updates->getUpdateModel($forceRefresh);
	}
}
