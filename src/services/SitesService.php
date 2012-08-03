<?php
namespace Blocks;

/**
 *
 */
class SitesService extends \CApplicationComponent
{
	private $_currentSite;
	private $_allSites;
	private $_enabledSites;

	/**
	 * @return array|null
	 */
	public function getAllLicenseKeys()
	{
		$sites = $this->getAllSites();

		$keysArr = array();

		foreach ($sites as $site)
			$keysArr[] = $site->license_key;

		if (count($keysArr) > 0)
			return $keysArr;

		return null;
	}

	/**
	 * @return array
	 */
	public function getEnabledSitesAndKeys()
	{
		$resultArr = array();
		$sites = $this->getEnabledSites();

		foreach ($sites as $site)
		{
			$domain = $site->url;
			$domain = str_replace('https://', '', $domain);
			$domain = str_replace('http://', '', $domain);

			$resultArr[$domain] = array('key' => $site->license_key, 'status' => '');
		}

		return $resultArr;
	}

	/**
	 * Saves a site.
	 * @param array $siteSettings
	 * @param int   $siteId The site ID, if saving an existing site.
	 * @throws \Exception
	 * @throws Exception
	 * @return Site
	 */
	public function saveSite($siteSettings, $siteId)
	{
		if ($siteId)
		{
			$site = Site::model()->with('sections')->findById($siteId);

			if (!$site)
				throw new Exception(Blocks::t(TranslationCategory::App, 'No site exists with the ID '.$siteId));

			$isNewSite = false;
			$oldSiteHandle = $site->handle;
		}
		else
		{
			$site = new Site();
			$isNewSite = true;
			$oldSiteHandle = null;
		}

		$site->name     = (isset($siteSettings['name']) ? $siteSettings['name'] : null);
		$site->handle   = (isset($siteSettings['handle']) ? $siteSettings['handle'] : null);
		$site->url      = (isset($siteSettings['url']) ? $siteSettings['url'] : null);
		$site->language = (isset($siteSettings['language']) ? $siteSettings['language'] : null);

		if ($site->validate())
		{
			// Start a transaction
			$transaction = blx()->db->beginTransaction();
			try
			{
				// Did the site handle change?
				$siteHandleChanged = (!$isNewSite && $site->handle !== $oldSiteHandle);
				if ($siteHandleChanged)
				{
					// Remember the old section table names
					foreach ($site->sections as $section)
					{
						$oldTableNames[] = $section->getContentTableName();
					}
				}

				// Attempt to save the site
				$siteSaved = $site->save(false);

				if ($siteSaved && $siteHandleChanged)
				{
					// Rename section tables
					foreach ($site->sections as $i => $section)
					{
						// Update the section's site reference so it knows the new site handle
						$section->site = $site;
						$newTableName = $section->getContentTableName();
						blx()->db->createCommand()->renameTable($oldTableNames[$i], $newTableName);
					}
				}

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}
		}

		return $site;
	}

	/**
	 * @return string|null
	 */
	public function getSiteName()
	{
		if (isset(blx()->params['config']['siteName']))
			return blx()->params['config']['siteName'];

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getSiteLanguage()
	{
		if (isset(blx()->params['config']['language']))
			return blx()->params['config']['language'];

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getSiteUrl()
	{
		if (isset(blx()->params['config']['siteUrl']))
			return blx()->params['config']['siteUrl'];

		return null;
	}

	/**
	 * Returns the current site.
	 * @throws Exception
	 * @return Site
	 */
	public function getCurrentSite()
	{
		if ($this->_currentSite === null)
		{
			// Is a site being requested index.php?
			if (defined('BLOCKS_SITE'))
			{
				$site = Site::model()->findByAttributes(array(
					'handle' => BLOCKS_SITE
				));
			}

			if (empty($site))
			{
				// Try to find the site that matches the request URL
				$serverName = blx()->request->getServerName();
				$httpServerName = 'http://'.$serverName;
				$httpsServerName = 'https://'.$serverName;

				$site = Site::model()->find(
					'url=:url OR url=:httpUrl OR url=:httpsUrl', array(':url' => $serverName, ':httpUrl' => $httpServerName, ':httpsUrl' => $httpsServerName)
				);

				if (empty($site))
				{
					// Just get the primary site
					$site = Site::model()->findByAttributes(array(
						'primary' => true
					));

					if (empty($site))
						throw new Exception(Blocks::t(TranslationCategory::App, 'There is no primary site.'));
				}
			}

			$this->_currentSite = $site;
		}

		return $this->_currentSite;
	}

	/**
	 * @param Site $site
	 */
	public function setCurrentSite($site)
	{
		$this->_currentSite = $site;
	}

	/**
	 * Returns all sites
	 * @return mixed
	 */
	public function getAllSites()
	{
		if (!$this->_allSites)
			$this->_allSites = Site::model()->findAll();

		return $this->_allSites;
	}

	/**
	 * @return mixed
	 */
	public function getEnabledSites()
	{
		if (!$this->_enabledSites)
			$this->_enabledSites = Site::model()->findAllByAttributes(array(
				'enabled' => true
			));

		return $this->_enabledSites;
	}

	/**
	 * @param $url
	 * @return Site
	 */
	public function getSiteByUrl($url)
	{
		$url = ltrim($url, 'http://');
		$url = ltrim($url, 'https://');

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
		$site = Site::model()->findById($id);
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
	 * @param $siteHandle
	 * @return string
	 */
	public function getLicenseKeyStatusForSite($siteHandle)
	{
		$licenseKeyStatus = blx()->fileCache->get($siteHandle.'licenseKeyStatus');

		if ($licenseKeyStatus == false)
			$this->_getAllLicenseKeyStatuses();

		return blx()->fileCache->get($siteHandle.'licenseKeyStatus');
	}

	/**
	 * @return mixed
	 */
	public function getPrimarySite()
	{
		$site = Site::model()->findByAttributes(array(
			'primary' => 1
		));

		return $site;
	}

	/**
	 * Sets the primary site.
	 *
	 * @param int $siteId
	 */
	public function setPrimarySite($siteId)
	{
		// Set primary=false on the current primary site
		$oldSite = $this->getPrimarySite();
		$oldSite->primary = 0;
		$oldSite->save();

		// Set the new primary site
		$newSite = $this->getSiteById($siteId);
		$newSite->primary = 1;
		$newSite->save();
	}

	/**
	 * @param $siteUrl
	 * @param $status
	 */
	public function setLicenseKeyStatusForSite($siteUrl, $status)
	{
		$site = $this->getSiteByUrl($siteUrl);
		// cache it and set it to expire according to config
		blx()->fileCache->set($site->handle.'licenseKeyStatus', $status, blx()->config->cacheTimeSeconds);
	}

	/**
	 * @access private
	 * @return string
	 */
	private function _getAllLicenseKeyStatuses()
	{
		$package = blx()->et->ping();
		foreach ($package->sitesAndKeys as $site => $keyInfo)
		{
			$this->setLicenseKeyStatusForSite($site, $keyInfo['status']);
		}
	}
}
