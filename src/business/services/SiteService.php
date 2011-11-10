<?php

class SiteService extends CApplicationComponent implements ISiteService
{
	public function getSiteLicenseKey()
	{
		return Blocks::app()->params['config']['licenseKey'];
	}

	public function getSiteName()
	{
		return Blocks::app()->params['config']['siteName'];
	}

	public function getSiteLanguage()
	{
		return Blocks::app()->params['config']['language'];
	}

	public function getSiteUrl()
	{
		return Blocks::app()->params['config']['siteUrl'];
	}

	public function getSiteByUrl()
	{
		$serverName = Blocks::app()->request->getServerName();
		$httpServerName = 'http://'.$serverName;
		$httpsServerName = 'https://'.$serverName;

		$site = Sites::model()->find(
			'url=:url OR url=:httpUrl OR url=:httpsUrl', array(':url' => $serverName, ':httpUrl' => $httpServerName, ':httpsUrl' => $httpsServerName)
		);

		return $site;
	}

	public function versionCheck()
	{
		$versionCheckInfo['blocksClientBuildNo'] = Blocks::getBuildNumber();

		// We're working on the source.  No need to check.
		if(strpos($versionCheckInfo['blocksClientBuildNo'], '@@@') !== false)
			return null;

		$versionCheckInfo['blocksClientVersionNo'] = Blocks::getVersion();
		$versionCheckInfo['blocksClientEdition'] = Blocks::getEdition();
		$versionCheckInfo['pluginNamesAndVersions'] = Blocks::app()->plugins->getAllInstalledPluginHandlesAndVersions();
		$versionCheckInfo['key'] = Blocks::app()->site->getSiteLicenseKey();
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

	public function getAllowedTemplateFileExtensions()
	{
		return array('html', 'php');
	}
}
