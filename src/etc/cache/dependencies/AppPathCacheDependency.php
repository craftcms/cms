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
	 * Generates the data needed to determine if dependency has been changed.
	 * Derived classes should override this method to generate actual dependent data.
	 *
	 * @return mixed The data needed to determine if dependency has been changed.
	 */
	protected function generateDependentData()
	{
		return craft()->path->getAppPath();
	}
}
