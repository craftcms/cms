<?php

/**
 *
 */
class bUpdateService extends CApplicationComponent
{
	private $_updateInfo;

	/**
	 * @param $blocksReleases
	 * @return bool
	 */
	public function criticalBlocksUpdateAvailable($blocksReleases)
	{
		foreach ($blocksReleases as $blocksRelease)
		{
			if ($blocksRelease->critical !== null)
				return true;
		}

		return false;
	}

	/**
	 * @param $plugins
	 * @return bool
	 */
	public function criticalPluginUpdateAvailable($plugins)
	{
		foreach ($plugins as $plugin)
		{
			if ($plugin->status == bPluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
			{
				foreach ($plugin->newerReleases as $release)
				{
					if ($release->critical)
						return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isUpdateInfoCached()
	{
		return (isset($this->_updateInfo) || Blocks::app()->fileCache->get('updateInfo') !== false);
	}

	/**
	 * @param bool $forceRefresh
	 * @return mixed
	 */
	public function getUpdateInfo($forceRefresh = false)
	{
		if (!isset($this->_updateInfo) || $forceRefresh)
		{
			$updateInfo = new bUpdateInfo();
			// no update info if we can't find the license keys.

			if (!$forceRefresh)
			{
				// get the update info from the cache if it's there
				$updateInfo = Blocks::app()->fileCache->get('updateInfo');
			}

			// fetch it if it wasn't cached, or if we're forcing a refresh
			if ($forceRefresh || $updateInfo === false)
			{
				$updateInfo = $this->check();

				if ($updateInfo == null)
					$updateInfo = new bUpdateInfo();

				// cache it and set it to expire according to config
				Blocks::app()->fileCache->set('updateInfo', $updateInfo, Blocks::app()->getConfig('cacheTimeSeconds'));
			}

			$this->_updateInfo = $updateInfo;
		}

		return $this->_updateInfo;
	}

	/**
	 * @return bool
	 */
	public function doCoreUpdate()
	{
		$coreUpdater = new bCoreUpdater();
		//if ($coreUpdater->start())
			return true;

		return false;
	}

	/**
	 * @param $pluginHandle
	 * @return bool
	 */
	public function doPluginUpdate($pluginHandle)
	{
		$pluginUpdater = new bPluginUpdater($pluginHandle);
		if ($pluginUpdater->start())
			return true;

		return false;
	}

	/**
	 * @return bUpdateInfo
	 */
	public function check()
	{
		$blocksUpdateInfo = new bUpdateInfo();
		$blocksUpdateInfo->localBuild = Blocks::getBuild();
		$blocksUpdateInfo->localVersion = Blocks::getVersion();

		$plugins = Blocks::app()->plugins->allInstalledPluginHandlesAndVersions;
		foreach ($plugins as $plugin)
			$blocksUpdateInfo->plugins[$plugin['handle']] = new bPluginUpdateData($plugin);

		$et = new bEt(bEtEndPoints::Check());
		$et->getPackage()->data = $blocksUpdateInfo;
		$response = $et->phoneHome();

		$blocksUpdateInfo = $response == null ? new bUpdateInfo() : new bUpdateInfo($response->data);
		return $blocksUpdateInfo;
	}
}
