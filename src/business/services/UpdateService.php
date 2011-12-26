<?php

class UpdateService extends CApplicationComponent implements IUpdateService
{
	private $_updateInfo;

	public function criticalBlocksUpdateAvailable($blocksReleases)
	{
		foreach ($blocksReleases as $blocksRelease)
		{
			if ($blocksRelease->critical !== null)
				return true;
		}

		return false;
	}

	public function criticalPluginUpdateAvailable($plugins)
	{
		foreach ($plugins as $plugin)
		{
			if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
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

	public function isUpdateInfoCached()
	{
		return (isset($this->_updateInfo) || Blocks::app()->fileCache->get('updateInfo') !== false);
	}

	public function getUpdateInfo($forceRefresh = false)
	{
		if (!isset($this->_updateInfo) || $forceRefresh)
		{
			$updateInfo = new BlocksUpdateInfo();
			// no update info if we can't find the license keys.
			//if (($keys = Blocks::app()->site->getLicenseKeys()) == null || empty($keys))
			//	$updateInfo-> licenseKeyStatus = LicenseKeyStatus::MissingKey;
			//else
			//{
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
					$updateInfo = new BlocksUpdateInfo();

				// cache it and set it to expire in 24 hours (86400 seconds) or 5 seconds if dev mode
				$expire = Blocks::app()->config('devMode') ? 5 : 86400;
				Blocks::app()->fileCache->set('updateInfo', $updateInfo, $expire);
			}
		//}

			$this->_updateInfo = $updateInfo;
		}

		return $this->_updateInfo;
	}

	public function doCoreUpdate()
	{
		$coreUpdater = new CoreUpdater();
		//if ($coreUpdater->start())
			return true;

		return false;
	}

	public function doPluginUpdate($pluginHandle)
	{
		$pluginUpdater = new PluginUpdater($pluginHandle);
		if ($pluginUpdater->start())
			return true;

		return false;
	}

	public function check()
	{
		$blocksUpdateInfo = new BlocksUpdateInfo();
		$blocksUpdateInfo->localBuild = Blocks::getBuild();
		$blocksUpdateInfo->localVersion = Blocks::getVersion();

		$plugins = Blocks::app()->plugins->getAllInstalledPluginHandlesAndVersions();
		foreach ($plugins as $plugin)
			$blocksUpdateInfo->plugins[$plugin['handle']] = new PluginUpdateData($plugin);

		$et = new ET(ETEndPoints::Check);
		$et->getPackage()->data = $blocksUpdateInfo;
		$response = $et->phoneHome();

		$blocksUpdateInfo = $response == null ? new BlocksUpdateInfo() : new BlocksUpdateInfo($response->data);
		return $blocksUpdateInfo;
	}
}
