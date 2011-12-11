<?php

class UpdateService extends CApplicationComponent implements IUpdateService
{
	private $_blocksUpdateData;

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

	public function blocksUpdateInfo($fetch = false)
	{
		if (!isset($this->_blocksUpdateData) || ($this->_blocksUpdateData === false && $fetch))
		{
			$blocksUpdateData = new BlocksUpdateData();
			// no update info if we can't find the license keys.
			if (($keys = Blocks::app()->site->getLicenseKeys()) == null || empty($keys))
				$blocksUpdateData->licenseStatus = LicenseKeyStatus::MissingKey;
			else
			{
				// get the update info from the cache if it's there
				$blocksUpdateData = Blocks::app()->fileCache->get('blocksUpdateData');

				// if it wasn't cached, should we fetch it?
				if ($blocksUpdateData === false && $fetch)
				{
					$blocksUpdateData = $this->check();

					if ($blocksUpdateData == null)
						$blocksUpdateData = new BlocksUpdateData();

					// cache it and set it to expire in 24 hours (86400 seconds) or 5 seconds if dev mode
					$expire = Blocks::app()->config('devMode') ? 5 : 86400;
					Blocks::app()->fileCache->set('blocksUpdateData', $blocksUpdateData, $expire);
				}
			}

			$this->_blocksUpdateData = $blocksUpdateData;
		}

		return $this->_blocksUpdateData;
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
		$blocksUpdateData = new BlocksUpdateData();
		$blocksUpdateData->localEdition = Blocks::getEdition();
		$blocksUpdateData->localBuild = Blocks::getBuild();
		$blocksUpdateData->localVersion = Blocks::getVersion();

		$plugins = Blocks::app()->plugins->getAllInstalledPluginHandlesAndVersions();
		foreach ($plugins as $plugin)
			$blocksUpdateData->plugins[$plugin['handle']] = new PluginUpdateData($plugin);

		$blocksUpdateData->licenseKeys = Blocks::app()->site->getLicenseKeys();
		$blocksUpdateData->requestingDomain = Blocks::app()->request->getServerName();

		try
		{
			$client = new HttpClient(APIWebServiceEndPoints::VersionCheck, array(
					'timeout'       =>  6,
					'maxredirects'  =>  0
					));

			$client->setRawData(CJSON::encode($blocksUpdateData), 'json')->request('POST');
			$response = $client->request('POST');

			if ($response->isSuccessful())
			{
				$responseBody = CJSON::decode($response->getBody());
				return new BlocksUpdateData($responseBody);
			}
			else
			{
				Blocks::log('Error in calling '.APIWebServiceEndPoints::VersionCheck.' Response: '.$response->getBody(), 'warning');
			}
		}
		catch(Exception $e)
		{
			Blocks::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), 'error');
		}

		return null;
	}
}
