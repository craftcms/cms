<?php
namespace Blocks;

/**
 * Site functions
 */
class SitesVariable
{
	/**
	 * Returns the license keys.
	 * @return array
	 */
	public function licensekeys()
	{
		return blx()->sites->getAllLicenseKeys();
	}

	/**
	 * Returns a site by its ID.
	 * @param int $siteId
	 * @return Site
	 */
	public function getSiteById($siteId)
	{
		return blx()->sites->getSiteById($siteId);
	}

	/**
	 * Returns all sites.
	 * @return array
	 */
	public function all()
	{
		return blx()->sites->getAllSites();
	}
}
