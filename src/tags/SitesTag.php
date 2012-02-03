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
		return Blocks::app()->sites->getSiteById($siteId);
	}

	/**
	 * Get all sites
	 * @return
	 */
	function __toArray()
	{
		return Blocks::app()->sites->getAll();
	}
}
