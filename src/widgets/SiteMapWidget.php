<?php
namespace Blocks;

/**
 *
 */
class SiteMapWidget extends BaseWidget
{
	public $title = 'Site Map';
	public $className = 'sitemap';

	/**
	 * @return mixed
	 */
	public function displayBody()
	{
		return b()->controller->loadTemplate('_widgets/SiteMapWidget/body', null, true);
	}
}
