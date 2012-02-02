<?php
namespace Blocks;

/**
 *
 */
class SitesTag extends Tag
{
	/**
	 *
	 */
	function getById($siteId)
	{
		return Blocks::app()->site->getSiteById($siteId);
	}
}
