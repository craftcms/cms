<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\db\Query;
use DateTime;
use yii\base\Exception;


/**
 * Class ChartHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ChartHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the data for a run chart, based on a given DB query, start/end dates, and the desired time interval unit.
     *
     * The query’s SELECT clause should already be set to a column aliased as `value`.
     * The $options array can override the following defaults:
     *
     *  - `intervalUnit`  - The time interval unit to use ('hour', 'day', 'month', or 'year').
     *                      By default, a unit will be decided automatically based on the start/end date duration.
     *  - `categoryLabel` - The label to use for the chart categories (times). Defaults to "Date".
     *  - `valueLabel`    - The label to use for the chart values. Defaults to "Value".
     *  - `valueType`     - The type of values that are being plotted ('number', 'currency', 'percent', 'time'). Defaults to 'number'.
     *
     * @param Query $query The DB query that should be used. It will be executed for each time interval,
     * with additional conditions on the $dateColumn, via [[\craft\db\Query::scalar()]].
     * @param DateTime $startDate The start of the time duration to select (inclusive)
     * @param DateTime $endDate The end of the time duration to select (exclusive)
     * @param string $dateColumn The column that represents the date
     * @param string $func The aggregate function to call for each date interval ('count', 'sum', 'average', 'min', or 'max')
     * @param string $q The column name or expression to pass into the aggregate function (make sure column names are `[[quoted]]`)
     * @param array $options Any customizations that should be made over the default options
     * @return array
     * @throws Exception
     */
    public static function getRunChartDataFromQuery(Query $query, DateTime $startDate, DateTime $endDate, string $dateColumn, string $func, string $q, array $options = []): array
    {
        // Setup
        $options = array_merge([
            'intervalUnit' => null,
            'categoryLabel' => Craft::t('app', 'Date'),
            'valueLabel' => Craft::t('app', 'Value'),
            'valueType' => 'number',
        ], $options);

        if ($options['intervalUnit'] && in_array($options['intervalUnit'], ['year', 'month', 'day', 'hour'], true)) {
            $intervalUnit = $options['intervalUnit'];
        } else {
            $intervalUnit = self::getRunChartIntervalUnit($startDate, $endDate);
        }

        // Prepare the query
        switch ($intervalUnit) {
            case 'year':
                $phpDateFormat = 'Y-01-01';
                break;
            case 'month':
                $phpDateFormat = 'Y-m-01';
                break;
            case 'day':
                $phpDateFormat = 'Y-m-d';
                break;
            case 'hour':
                $phpDateFormat = 'Y-m-d H:00:00';
                break;
            default:
                throw new Exception('Invalid interval unit: ' . $intervalUnit);
        }

        // Assemble the data
        $rows = [];

        $cursorDate = clone $startDate;
        $endTimestamp = $endDate->getTimestamp();

        while ($cursorDate->getTimestamp() < $endTimestamp) {
            $cursorEndDate = clone $cursorDate;
            $cursorEndDate->modify('+1 ' . $intervalUnit);
            $total = (float)(clone $query)
                ->andWhere(['>=', $dateColumn, Db::prepareDateForDb($cursorDate)])
                ->andWhere(['<', $dateColumn, Db::prepareDateForDb($cursorEndDate)])
                ->$func($q);
            $rows[] = [$cursorDate->format($phpDateFormat), $total];
            $cursorDate = $cursorEndDate;
        }

        return [
            'columns' => [
                [
                    'type' => $intervalUnit === 'hour' ? 'datetime' : 'date',
                    'label' => $options['categoryLabel']
                ],
                [
                    'type' => $options['valueType'],
                    'label' => $options['valueLabel']
                ]
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Returns the interval unit that should be used in a run chart, based on the given start and end dates.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return string The unit that the chart should use ('hour', 'day', 'month', or 'year')
     */
    public static function getRunChartIntervalUnit(DateTime $startDate, DateTime $endDate): string
    {
        // Get the total number of days between the two dates
        $days = $endDate->diff($startDate)->format('%a');

        if ($days >= 730) {
            return 'year';
        }

        if ($days >= 60) {
            return 'month';
        }

        if ($days >= 2) {
            return 'day';
        }

        return 'hour';
    }

    /**
     * Returns the short date, decimal, percent and currency D3 formats based on Craft's locale settings
     *
     * @return array
     */
    public static function formats(): array
    {
        return [
            'shortDateFormats' => self::shortDateFormats(),
        ];
    }

    /**
     * Returns the D3 short date formats based on Yii's short date format
     *
     * @return array
     */
    public static function shortDateFormats(): array
    {
        $format = Craft::$app->getLocale()->getDateFormat('short');

        // Some of these are RTL versions
        $removals = [
            'day' => ['y'],
            'month' => ['d', 'd‏'],
            'year' => ['d', 'd‏', 'm', 'M‏'],
        ];

        $shortDateFormats = [];

        foreach ($removals as $unit => $chars) {
            $shortDateFormats[$unit] = $format;

            foreach ($chars as $char) {
                $shortDateFormats[$unit] = preg_replace("/(^[{$char}]+\W+|\W+[{$char}]+)/iu", '', $shortDateFormats[$unit]);
            }
        }


        // yii formats to d3 formats

        $yiiToD3Formats = [
            'day' => ['dd' => '%-d', 'd' => '%-d'],
            'month' => ['MM' => '%-m', 'M' => '%-m'],
            'year' => ['yyyy' => '%Y', 'yy' => '%y', 'y' => '%y']
        ];

        foreach ($shortDateFormats as $unit => $format) {
            foreach ($yiiToD3Formats as $_unit => $_formats) {
                foreach ($_formats as $yiiFormat => $d3Format) {
                    $pattern = "/({$yiiFormat})/i";

                    preg_match($pattern, $shortDateFormats[$unit], $matches);

                    if (count($matches) > 0) {
                        $shortDateFormats[$unit] = preg_replace($pattern, $d3Format, $shortDateFormats[$unit]);

                        break;
                    }
                }
            }
        }

        return $shortDateFormats;
    }

    /**
     * Returns the predefined date ranges with their label, start date and end date.
     *
     * @return array
     */
    public static function dateRanges(): array
    {
        $dateRanges = [
            'd7' => ['label' => Craft::t('app', 'Last 7 days'), 'startDate' => '-7 days', 'endDate' => null],
            'd30' => ['label' => Craft::t('app', 'Last 30 days'), 'startDate' => '-30 days', 'endDate' => null],
            'lastweek' => ['label' => Craft::t('app', 'Last Week'), 'startDate' => '-2 weeks', 'endDate' => '-1 week'],
            'lastmonth' => ['label' => Craft::t('app', 'Last Month'), 'startDate' => '-2 months', 'endDate' => '-1 month'],
        ];

        return $dateRanges;
    }
}
