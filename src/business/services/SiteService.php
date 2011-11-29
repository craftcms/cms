<?php

class SiteService extends CApplicationComponent implements ISiteService
{
	public function getSiteLicenseKeys()
	{
		if (isset(Blocks::app()->params['config']['licenseKeys']))
			return Blocks::app()->params['config']['licenseKeys'];

		return null;
	}

	public function getSiteName()
	{
		if (isset(Blocks::app()->params['config']['siteName']))
			return Blocks::app()->params['config']['siteName'];

		return null;
	}

	public function getSiteLanguage()
	{
		if (isset(Blocks::app()->params['config']['language']))
			return Blocks::app()->params['config']['language'];

		return null;
	}

	public function getSiteUrl()
	{
		if (isset(Blocks::app()->params['config']['siteUrl']))
			return Blocks::app()->params['config']['siteUrl'];

		return null;
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
		$buildNumber = Blocks::getBuildNumber();
		// We're working on the source.  Get build number from the database.
		if(strpos($buildNumber, '@@@') !== false)
			$buildNumber = Blocks::getBuildNumber(true);

		$version = Blocks::GetVersion();
		// We're working on the source.  Get version from the database.
		if(strpos($version, '@@@') !== false)
			$version = Blocks::getVersion(true);

		$edition = Blocks::getEdition();
		// We're working on the source.  Get edition from the database.
		if(strpos($edition, '@@@') !== false)
			$edition = Blocks::getEdition(true);

		$versionCheckInfo['blocksClientBuildNo'] = $buildNumber;
		$versionCheckInfo['blocksClientVersionNo'] = $version;
		$versionCheckInfo['blocksClientEdition'] = $edition;
		$versionCheckInfo['pluginNamesAndVersions'] = Blocks::app()->plugins->getAllInstalledPluginHandlesAndVersions();
		$versionCheckInfo['keys'] = Blocks::app()->site->getSiteLicenseKeys();
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
				$responseBody = CJSON::decode($response->getBody());
				return $responseBody;
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

	public function matchTemplatePathWithAllowedFileExtensions($templatePath, $srcLanguage = 'en-us')
	{
		foreach ($this->getAllowedTemplateFileExtensions() as $allowedExtension)
		{
			$templateFile = Blocks::app()->findLocalizedFile($templatePath.'.'.$allowedExtension, $srcLanguage);
			if (is_file($templateFile))
				return realpath($templateFile);
		}

		return null;
	}
}
