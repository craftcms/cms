<?php
namespace Blocks;

/**
 *
 */
class SitesService extends BaseService
{
	private $_currentSite = null;
	private $_licenseKeyStatus = null;

	/**
	 * @return array|null
	 */
	public function getLicenseKeys()
	{
		$keysArr = array();
		$licenseKeys = LicenseKey::model()->findAll();

		foreach ($licenseKeys as $licenseKey)
			$keysArr[] = $licenseKey->key;

		if (count($keysArr) > 0)
			return $keysArr;

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getSiteName()
	{
		if (isset(Blocks::app()->params['config']['siteName']))
			return Blocks::app()->params['config']['siteName'];

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getSiteLanguage()
	{
		if (isset(Blocks::app()->params['config']['language']))
			return Blocks::app()->params['config']['language'];

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getSiteUrl()
	{
		if (isset(Blocks::app()->params['config']['siteUrl']))
			return Blocks::app()->params['config']['siteUrl'];

		return null;
	}

	/**
	 * Gets the current site model by Url
	 * @return Site
	 */
	public function getCurrentSite()
	{
		if ($this->_currentSite == null)
		{
			// Try to find the site that matches the request URL
			$serverName = Blocks::app()->request->serverName;
			$httpServerName = 'http://'.$serverName;
			$httpsServerName = 'https://'.$serverName;

			$site = Site::model()->find(
				'url=:url OR url=:httpUrl OR url=:httpsUrl', array(':url' => $serverName, ':httpUrl' => $httpServerName, ':httpsUrl' => $httpsServerName)
			);

			// Get the primary site if we can't find a site with a URL match
			if (!$site)
			{
				$site = Site::model()->findByAttributes(array(
					'primary' => true
				));
			}

			$this->_currentSite = $site;
		}

		return $this->_currentSite;
	}

	/**
	 * Returns all sites
	 * @return
	 */
	public function getAll()
	{
		return Site::model()->findAll();
	}

	/**
	 * @param $url
	 * @return Site
	 */
	public function getSiteByUrl($url)
	{
		$url = ltrim('http://', $url);
		$url = ltrim('https://', $url);

		$httpServerName = 'http://'.$url;
		$httpsServerName = 'https://'.$url;

		$site = Site::model()->find(
			'url=:url OR url=:httpUrl OR url=:httpsUrl', array(':url' => $url, ':httpUrl' => $httpServerName, ':httpsUrl' => $httpsServerName)
		);

		return $site;
	}

	/**
	 * @param $id
	 * @return Site
	 */
	public function getSiteById($id)
	{
		$site = Site::model()->findByPk($id);
		return $site;
	}

	/**
	 * @param $handle
	 * @return Site
	 */
	public function getSiteByHandle($handle)
	{
		$site = Site::model()->findByAttributes(array(
			'handle' => $handle,
		));

		return $site;
	}

	/**
	 * @return string
	 */
	public function getLicenseKeyStatus()
	{
		$licenseKeyStatus = Blocks::app()->fileCache->get('licenseKeyStatus');
		if ($licenseKeyStatus == false)
			$licenseKeyStatus = $this->_getLicenseKeyStatus();

		return $licenseKeyStatus;

	}

	/**
	 * @param $licenseKeyStatus
	 */
	public function setLicenseKeyStatus($licenseKeyStatus)
	{
		// cache it and set it to expire according to config
		Blocks::app()->fileCache->set('licenseKeyStatus', $licenseKeyStatus, Blocks::app()->config->getItem('cacheTimeSeconds'));
	}

	/**
	 * @access private
	 * @return string
	 */
	private function _getLicenseKeyStatus()
	{
		$licenseKeys = Blocks::app()->sites->licenseKeys;

		if (!$licenseKeys)
			return LicenseKeyStatus::MissingKey;

		$package = Blocks::app()->et->ping();
		$licenseKeyStatus = $package->licenseKeyStatus;
		$this->setLicenseKeyStatus($licenseKeyStatus);
		return $licenseKeyStatus;
	}
}
