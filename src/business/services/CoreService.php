<?php

class CoreService extends CApplicationComponent implements ICoreService
{
	public function versionCheck()
	{
		$versionCheckInfo['blocksClientBuildNo'] = Blocks::getBuildNumber();

		// We're working on the source.  No need to check.
		if(strpos($versionCheckInfo['blocksClientBuildNo'], '@@@') !== false)
			return null;

		$versionCheckInfo['blocksClientVersionNo'] = Blocks::getVersion();
		$versionCheckInfo['blocksClientEdition'] = Blocks::getEdition();
		$versionCheckInfo['pluginNamesAndVersions'] = Blocks::app()->plugins->getAllInstalledPluginHandlesAndVersions();
		$versionCheckInfo['key'] = Blocks::app()->config->getSiteLicenseKey();
		$versionCheckInfo['requestingDomain'] = Blocks::app()->request->getServerName();

		try
		{
			$client = new HttpClient(APIWebServiceEndPoints::VersionCheck, array(
					'timeout'       =>  6,
					'maxredirects'  =>  0
					));

			$client->setRawData(CJSON::encode($versionCheckInfo), 'json')->request('POST');
			$response = $client->request('POST');

			if ($response->isSuccessful())
			{
				$responseBody = $response->getBody();
				$responseVersionInfo = CJSON::decode($responseBody);
				return $responseVersionInfo;
			}
			else
			{
				Blocks::log('Error in calling '.APIWebServiceEndPoints::VersionCheck.' Response: '.$response->getBody(), 'warning');
			}
		}
		catch(Exception $e)
		{
			Blocks::log('Error in VersionCheckFilter. Message: '.$e->getMessage(), 'error');
		}

		return null;
	}
}
