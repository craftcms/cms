<?php
namespace Craft;

/**
 * Class DeprecatorVariable
 *
 * @package craft.app.validators
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
