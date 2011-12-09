<?php

class UpdateService extends CApplicationComponent implements IUpdateService
{
	private $_blocksUpdateInfo;

	public function criticalBlocksUpdateAvailable($blocksReleases)
	{
		foreach ($blocksReleases as $blocksRelease)
		{
			if ($blocksRelease['critical'])
				return true;
		}

		return false;
	}

	public function criticalPluginUpdateAvailable($plugins)
	{
		foreach ($plugins as $plugin)
		{
			if ($plugin['status'] == PluginVersionUpdateStatus::UpdateAvailable && count($plugin['newerReleases']) > 0)
			{
				foreach ($plugin['newerReleases'] as $release)
				{
					if ($release['critical'])
						return true;
				}
			}
		}

		return false;
	}

	public function blocksUpdateInfo($fetch = false)
	{
		if (!isset($this->_blocksUpdateInfo) || ($this->_blocksUpdateInfo === false && $fetch))
		{
			// no update info if we can't find the license keys.
			if (($keys = Blocks::app()->site->getLicenseKeys()) == null || empty($keys))
				$blocksUpdateInfo['blocksLicenseStatus'] = LicenseKeyStatus::MissingKey;
			else
			{
				// get the update info from the cache if it's there
				$blocksUpdateInfo = Blocks::app()->fileCache->get('blocksUpdateInfo');

				// if it wasn't cached, should we fetch it?
				if ($blocksUpdateInfo === false && $fetch)
				{
					$blocksUpdateInfo = Blocks::app()->site->versionCheck();

					// cache it and set it to expire in 24 hours (86400 seconds) or 5 seconds if dev mode
					$expire = Blocks::app()->config('devMode') ? 5 : 86400;
					Blocks::app()->fileCache->set('blocksUpdateInfo', $blocksUpdateInfo, $expire);
				}
			}

			$this->_blocksUpdateInfo = $blocksUpdateInfo;
		}

		return $this->_blocksUpdateInfo;
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
}
