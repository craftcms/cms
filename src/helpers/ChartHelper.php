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
				break;
			}
			case 'month':
			{
				$sqlDateFormat = '%Y-%m-01';
				$phpDateFormat = 'Y-m-01';
				$sqlGroup = "YEAR({$dateColumn}), MONTH({$dateColumn})";
				break;
			}
			case 'day':
			{
				$sqlDateFormat = '%Y-%m-%d';
				$phpDateFormat = 'Y-m-d';
				$sqlGroup = "YEAR({$dateColumn}), MONTH({$dateColumn}), DAY({$dateColumn})";
				break;
			}
			case 'hour':
			{
				$sqlDateFormat = '%Y-%m-%d %H:00:00';
				$phpDateFormat = 'Y-m-d H:00:00';
				$sqlGroup = "YEAR({$dateColumn}), MONTH({$dateColumn}), DAY({$dateColumn}), HOUR({$dateColumn})";
				break;
			}
		}

		// Execute the query
		$results = $query
			->addSelect("DATE_FORMAT({$dateColumn}, '{$sqlDateFormat}') as date")
			->andWhere(
				array('and', $dateColumn.' >= :startDate', $dateColumn.' < :endDate'),
				array(':startDate' => $startDate->format(DateTime::MYSQL_DATETIME, DateTime::UTC), ':endDate' => $endDate->format(DateTime::MYSQL_DATETIME, DateTime::UTC)))
			->group($sqlGroup)
			->order($dateColumn.' asc')
			->queryAll();

		// Assembe the data
		$rows = array();

		$cursorDate = $startDate;
		$endTimestamp = $endDate->getTimestamp();

		while ($cursorDate->getTimestamp() < $endTimestamp)
		{
			// Do we have a record for this date?
			$cursorDateUtc = $cursorDate->format($phpDateFormat, DateTime::UTC);
			$formattedCursorDate = $cursorDate->format($phpDateFormat);

			if (isset($results[0]) && $results[0]['date'] == $cursorDateUtc)
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
					'type' => ($intervalUnit === 'hour' ? 'datetime' : 'date'),
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

	/**
	 * Returns the short date, decimal, percent and currency D3 formats based on Craft's locale settings
	 *
	 * @return array
	 */
	public static function getFormats()
	{
		return array(
			'shortDateFormats' => self::getShortDateFormats(),
			'decimalFormat' => self::getDecimalFormat(),
			'percentFormat' => self::getPercentFormat(),
			'currencyFormat' => self::getCurrencyFormat(),
		);
	}

	/**
	 * Returns the D3 short date formats based on Yii's short date format
	 *
	 * @return array
	 */
	public static function getShortDateFormats()
	{
		$format = craft()->locale->getDateFormat('short');

		// Some of these are RTL versions
		$removals = array(
			'day' => array('y'),
			'month' => array('d', 'd‏'),
			'year' => array('d', 'd‏', 'm', 'M‏'),
		);

		$shortDateFormats = array();

		foreach($removals as $unit => $chars)
		{
			$shortDateFormats[$unit] = $format;

			foreach($chars as $char)
			{
				$shortDateFormats[$unit] = preg_replace("/(^[{$char}]+\W+|\W+[{$char}]+)/iu", '', $shortDateFormats[$unit]);
			}
		}


		// yii formats to d3 formats

		$yiiToD3Formats = array(
			'day' => array('dd' => '%-d','d' => '%-d'),
			'month' => array('MM' => '%-m','M' => '%-m'),
			'year' => array('yyyy' => '%Y','yy' => '%y','y' => '%y')
		);

		foreach($shortDateFormats as $unit => $format)
		{
			foreach($yiiToD3Formats as $_unit => $_formats)
			{
				foreach($_formats as $yiiFormat => $d3Format)
				{
					$pattern = "/({$yiiFormat})/i";

					preg_match($pattern, $shortDateFormats[$unit], $matches);

					if(count($matches) > 0)
					{
						$shortDateFormats[$unit] = preg_replace($pattern, $d3Format, $shortDateFormats[$unit]);

						break;
					}

				}
			}
		}

		return $shortDateFormats;
	}

	/**
	 * Returns the D3 decimal format based on Yii's decimal format
	 *
	 * @return array
	 */
	public static function getDecimalFormat()
	{
		$format = craft()->locale->getDecimalFormat();

		$yiiToD3Formats = array(
			'#,##,##0.###' => ',.3f',
			'#,##0.###' => ',.3f',
			'#0.######' => '.6f',
			'#0.###;#0.###-' => '.3f',
			'0 mil' => ',.3f',
		);

		if(isset($yiiToD3Formats[$format]))
		{
			return $yiiToD3Formats[$format];
		}
	}

	/**
	 * Returns the D3 percent format based on Yii's percent format
	 *
	 * @return array
	 */
	public static function getPercentFormat()
	{
		$format = craft()->locale->getPercentFormat();

		$yiiToD3Formats = array(
			'#,##,##0%' => ',.2%',
			'#,##0%' => ',.2%',
			'#,##0 %' => ',.2%',
			'#0%' => ',.0%',
			'%#,##0' => ',.2%',
		);

		if(isset($yiiToD3Formats[$format]))
		{
			return $yiiToD3Formats[$format];
		}
	}

	/**
	 * Returns the D3 currency format based on Yii's currency format
	 *
	 * @return array
	 */
	public static function getCurrencyFormat()
	{
		$format = craft()->locale->getCurrencyFormat();

		$yiiToD3Formats = array(

			'#,##0.00 ¤' => '$,.2f',
			'#,##0.00 ¤;(#,##0.00 ¤)' => '$,.2f',
			'¤#,##0.00' => '$,.2f',
			'¤#,##0.00;(¤#,##0.00)' => '$,.2f',
			'¤#,##0.00;¤-#,##0.00' => '$,.2f',
			'¤#0.00' => '$.2f',
			'¤ #,##,##0.00' => '$,.2f',
			'¤ #,##0.00' => '$,.2f',
			'¤ #,##0.00;¤-#,##0.00' => '$,.2f',
			'¤ #0.00' => '$.2f',
			'¤ #0.00;¤ #0.00-' => '$.2f',
		);

		if(isset($yiiToD3Formats[$format]))
		{
			return $yiiToD3Formats[$format];
		}
	}

	/**
	 * Returns the predefined date ranges with their label, start date and end date.
	 *
	 * @return array
	 */
	public static function getDateRanges()
	{
		$dateRanges = array(
			'd7' => array('label' => Craft::t('Last 7 days'), 'startDate' => '-7 days', 'endDate' => null),
			'd30' => array('label' => Craft::t('Last 30 days'), 'startDate' => '-30 days', 'endDate' => null),
			'lastweek' => array('label' => Craft::t('Last Week'), 'startDate' => '-2 weeks', 'endDate' => '-1 week'),
			'lastmonth' => array('label' => Craft::t('Last Month'), 'startDate' => '-2 months', 'endDate' => '-1 month'),
		);

		return $dateRanges;
	}
}
