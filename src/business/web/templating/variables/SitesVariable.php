<?php
namespace Blocks;

/**
 * Site functions
 */
class SitesVariable
{
	/**
	 * Returns the license keys
	 */
	public function licensekeys()
	{
		return b()->sites->getLicenseKeys();
	}

	/**
	 * Returns a site by its ID
	 */
	public function getSiteById($siteId)
	{
		return b()->sites->getSiteById($siteId);
	}

	/**
	 * Returns all sites
	 */
	public function all()
	{
		return b()->sites->getAll();
	}
}
