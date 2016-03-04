<?php
namespace Craft;

/**
 * Class ChartHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     2.6
 */
class ChartHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the unit that a date chart should use, based on the given start/end dates.
	 *
	 * @param DateTime $startDate
	 * @param DateTime $endDate
	 *
	 * @return string The unit that the chart should use ('hour', 'day', 'month', or 'year')
	 */
	public function getDateChartScale(DateTime $startDate, DateTime $endDate)
	{
		// Get the total number of days between the two dates
		$days = floor(($endDate->getTimestamp() - $startDate->getTimestamp()) / 86400);

		if ($days >= 730)
		{
			return 'year';
		}

		if ($days >= 60)
		{
			return 'month';
		}

		if ($days >= 2)
		{
			return 'day';
		}

		return 'hour';
	}
}
