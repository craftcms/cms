<?php
namespace Craft;

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
