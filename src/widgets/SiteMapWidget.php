<?php

/**
 *
 */
class SiteMapWidget extends Widget
{
	public $title = 'Site Map';
	public $className = 'sitemap';

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function displayBody()
	{
		return Blocks::app()->controller->loadTemplate('_widgets/SiteMapWidget/body', null, true);
	}
}
