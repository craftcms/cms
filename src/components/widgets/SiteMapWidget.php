<?php
namespace Blocks;

/**
 *
 */
class SiteMapWidget extends BaseWidget
{
	protected $bodyTemplate = '_components/widgets/SiteMapWidget/body';

	/**
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Site Map');
	}
}
