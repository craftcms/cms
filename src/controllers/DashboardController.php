<?php

class DashboardController extends BaseController
{
	public function actionVersionCheck()
	{
		$blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo(true);

		$ret['alerts'] = array();

		if ($blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::InvalidKey)
			$ret['alerts'][] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.Blocks::app()->request->getServerName().'. <a href="">Manage my licenses</a>';

		if ($this->criticalUpdateAvailable($blocksUpdateInfo))
			$ret['alerts'][] = 'There is a critical update for Blocks available. <a href="">Update now</a>';

		echo CJSON::encode($ret);
	}

	private function criticalUpdateAvailable($blocksUpdateInfo)
	{
		foreach ($blocksUpdateInfo['blocksLatestCoreReleases'] as $release)
		{
			if ($release['critical'])
			{
				return true;
			}
		}

		/*foreach ($blocksUpdateInfo['pluginNamesAndVersions'] as $plugin)
		{
			foreach ($plugin['newerReleases'] as $release)
			{
				if ($release['critical'])
				{
					return true;
				}
			}
		}*/
	}
}
