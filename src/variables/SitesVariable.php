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
		return b()->sites->getLicenseKeys();
	}

	/**
	 * Returns a site by its ID.
	 * @param int $siteId
	 * @return Site
	 */
	public function getSiteById($siteId)
	{
		return b()->sites->getSiteById($siteId);
	}

	/**
	 * Returns all sites.
	 * @return array
	 */
	public function all()
	{
		return b()->sites->getAllSites();
	}
}
