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
	 * Returns the data for a run chart, based on a given DB query, start/end dates, and the desired time interval unit.
	 *
	 * The query’s SELECT clause should already be set to a column aliased as `value`.
	 *
	 * The $options array can override the following defaults:
	 *
	 *  - `intervalUnit` - The time interval unit to use ('hour', 'day', 'month', or 'year').
	 *                     By default, a unit will be decided automatically based on the start/end date duration.
	 *  - `categoryLabel` - The label to use for the chart categories (times). Defaults to "Date".
	 *  - `valueLabel` - The label to use for the chart values. Defaults to "Value".
	 *  - `valueType` - The type of values that are being plotted ('number', 'currency', 'percent', 'time'). Defaults to 'number'.
	 *
	 * @param DbCommand  $query        The DB query that should be used
	 * @param DateTime   $startDate    The start of the time duration to select (inclusive)
	 * @param DateTime   $endDate      The end of the time duratio to select (exclusive)
	 * @param string     $dateColumn   The column that represents the date
	 * @param array|null $options      Any customizations that should be made over the default options
	 *
	 * @return array
	 */
	public static function getRunChartDataFromQuery(DbCommand $query, DateTime $startDate, DateTime $endDate, $dateColumn, $options = array())
	{
		// Setup
		$options = array_merge(array(
			'intervalUnit' => null,
			'categoryLabel' => Craft::t('Date'),
			'valueLabel' => Craft::t('Value'),
			'valueType' => 'number',
		), $options);

		$craftTimezone = new \DateTimeZone(craft()->timezone);
		$utc = new \DateTimeZone(DateTime::UTC);

		if ($options['intervalUnit'] && in_array($options['intervalUnit'], array('year', 'month', 'day', 'hour')))
		{
			$intervalUnit = $options['intervalUnit'];
		}
		else
		{
			$intervalUnit = self::getRunChartIntervalUnit($startDate, $endDate);
		}

		switch ($intervalUnit)
		{
			case 'year':
			{
				$sqlDateFormat = '%Y-01-01';
				$phpDateFormat = 'Y-01-01';
				$sqlGroup = "YEAR({$dateColumn})";
				$cursorDate = new DateTime($startDate->format('Y-01-01'), $craftTimezone);
				break;
			}
			case 'month':
			{
				$sqlDateFormat = '%Y-%m-01';
				$phpDateFormat = 'Y-m-01';
				$sqlGroup = "YEAR({$dateColumn}), MONTH({$dateColumn})";
				$cursorDate = new DateTime($startDate->format('Y-m-01'), $craftTimezone);
				break;
			}
			case 'day':
			{
				$sqlDateFormat = '%Y-%m-%d';
				$phpDateFormat = 'Y-m-d';
				$sqlGroup = "YEAR({$dateColumn}), MONTH({$dateColumn}), DAY({$dateColumn})";
				$cursorDate = new DateTime($startDate->format('Y-m-d'), $craftTimezone);
				break;
			}
			case 'hour':
			{
				$sqlDateFormat = '%Y-%m-%d %H:00:00';
				$phpDateFormat = 'Y-m-d H:00:00';
				$sqlGroup = "YEAR({$dateColumn}), MONTH({$dateColumn}), DAY({$dateColumn}), HOUR({$dateColumn})";
				$cursorDate = new DateTime($startDate->format('Y-m-d'), $craftTimezone);
				break;
			}
		}

		// Execute the query
		$results = $query
			->addSelect("DATE_FORMAT({$dateColumn}, '{$sqlDateFormat}') as date")
			->andWhere(
				array('and', $dateColumn.' >= :startDate', $dateColumn.' < :endDate'),
				array(':startDate' => $startDate->mySqlDateTime(), ':endDate' => $endDate->mySqlDateTime()))
			->group($sqlGroup)
			->order($dateColumn.' asc')
			->queryAll();

		// Assembe the data
		$rows = [];

		$endTimestamp = $endDate->getTimestamp();

		while ($cursorDate->getTimestamp() < $endTimestamp)
		{
			// Do we have a record for this date?
			$formattedCursorDate = $cursorDate->format($phpDateFormat, $utc);

			if (isset($results[0]) && $results[0]['date'] == $formattedCursorDate)
			{
				$value = (float) $results[0]['value'];
				array_shift($results);
			}
			else
			{
				$value = 0;
			}

			$rows[] = array($formattedCursorDate, $value);
			$cursorDate->modify('+1 '.$intervalUnit);
		}

		return array(
			'columns' => array(
				array(
					'type' => ($intervalUnit == 'hour' ? 'datetime' : 'date'),
					'label' => $options['categoryLabel']
				),
				array(
					'type' => $options['valueType'],
					'label' => $options['valueLabel']
				)
			),
			'rows' => $rows,
		);
	}

	/**
	 * Returns the interval unit that should be used in a run chart, based on the given start and end dates.
	 *
	 * @param DateTime $startDate
	 * @param DateTime $endDate
	 *
	 * @return string The unit that the chart should use ('hour', 'day', 'month', or 'year')
	 */
	public static function getRunChartIntervalUnit(DateTime $startDate, DateTime $endDate)
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
