<?php
namespace Blocks;

/**
 *
 */
class SitesTag extends Tag
{
	/**
	 * Get site by ID
	 */
	function getById($siteId)
	{
		return Blocks::app()->site->getSiteById($siteId);
	}

	/**
	 * Get all sites
	 */
	function __toArray()
	{
		return Blocks::app()->site->getAllSites();
	}
}
