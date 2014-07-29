<?php
namespace Craft;

/**
 * Class DeprecatorVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     2.0
 */
class DeprecatorVariable
{
	/**
	 * Returns the total number of deprecation errors that have been logged.
	 *
	 * @return int
	 */
	public function getTotalLogs()
	{
		return craft()->deprecator->getTotalLogs();
	}
}
