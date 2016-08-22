<?php
namespace Craft;

/**
 * AppPathCacheDependency is used to determine if the path to the `craft/app` folder has changed.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.cache.dependencies
 * @since     2.6
 */
class AppPathCacheDependency extends \CCacheDependency
{
	/**
	 * @var boolean Whether this dependency is reusable or not.
	 */
	public $reuseDependentData = true;

	/**
	 * @inheritDoc \CCacheDependency::generateDependentData()
	 *
	 * @return mixed
	 */
	protected function generateDependentData()
	{
		return craft()->path->getAppPath();
	}
}
