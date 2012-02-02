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
		return Blocks::app()->sites->getSiteById($siteId);
	}

	/**
	 * Get all sites
	 */
	function __toArray()
	{
		return Blocks::app()->sites->getAllSites();
	}
}
