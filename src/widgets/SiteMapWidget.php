<?php
namespace Blocks;

/**
 *
 */
class SiteMapWidget extends BaseWidget
{
	public $name = 'Site Map';

	protected $bodyTemplate = '_widgets/SiteMapWidget/body';

	/**
	 * Gets the widget title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return 'Site Map';
	}
}
