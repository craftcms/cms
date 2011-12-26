<?php

class SiteService extends CApplicationComponent implements ISiteService
{
	private $_currentSite = null;
	private $_licenseKeyStatus = null;

	public function getLicenseKeys()
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

	public function getCurrentSiteByUrl()
	{
		if ($this->_currentSite == null)
		{
			$serverName = Blocks::app()->request->getServerName();
			$httpServerName = 'http://'.$serverName;
			$httpsServerName = 'https://'.$serverName;

			$site = Sites::model()->find(
				'url=:url OR url=:httpUrl OR url=:httpsUrl', array(':url' => $serverName, ':httpUrl' => $httpServerName, ':httpsUrl' => $httpsServerName)
			);

			$this->_currentSite = $site;
		}

		return $this->_currentSite;
	}

	public function getSiteByUrl($url)
	{
		$url = ltrim('http://', $url);
		$url = ltrim('https://', $url);

		$httpServerName = 'http://'.$url;
		$httpsServerName = 'https://'.$url;

		$site = Sites::model()->find(
			'url=:url OR url=:httpUrl OR url=:httpsUrl', array(':url' => $url, ':httpUrl' => $httpServerName, ':httpsUrl' => $httpsServerName)
		);

		return $site;
	}

	public function getSiteById($id)
	{
		$site = Sites::model()->findByPk($id);
		return $site;
	}

	public function getSiteByHandle($handle)
	{
		$site = Sites::model()->findByAttributes(array(
			'handle' => $handle,
		));

		return $site;
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

	public function getLicenseKeyStatus()
	{
		if (!isset($this->_licenseKeyStatus))
			$this->_licenseKeyStatus = $this->_getLicenseKeyStatus();

		return $this->_licenseKeyStatus;

	}

	public function setLicenseKeyStatus($licenseKeyStatus)
	{
		$this->_licenseKeyStatus = $licenseKeyStatus;
	}

	private function _getLicenseKeyStatus()
	{
		$licenseKeys = Blocks::app()->site->getLicenseKeys();

		if (!$licenseKeys)
			return LicenseKeyStatus::MissingKey;

		$package = Blocks::app()->et->ping();
		$this->_licenseKeyStatus = $package->licenseKeyStatus;
		return $this->_licenseKeyStatus;
	}
}
