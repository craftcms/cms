<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Class Deprecator variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Deprecator
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the total number of deprecation errors that have been logged.
	 *
	 * @return int
	 */
	public function getTotalLogs()
	{
		return \Craft::$app->deprecator->getTotalLogs();
	}
}
