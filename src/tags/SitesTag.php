<?php
namespace Blocks;

/**
 *
 */
class SitesTag extends Tag
{
	/**
	 * Get site by ID
	 *
	 * @param $siteId
	 * @return mixed
	 */
	function getById($siteId)
	{
		return Blocks::app()->site->getSiteById($siteId);
	}

	/**
	 * Get all sites
	 * @return
	 */
	function __toArray()
	{
		return Blocks::app()->site->getAllSites();
	}
}
