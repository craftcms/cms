<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard;

use CraftCms\Cms\Support\Facades\I18N;
use DateTime;
use Exception;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

class Chart
{
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
     * @param  Builder  $query  The DB query that should be used. It will be executed for each time interval,
     *                          with additional conditions on the $dateColumn, via [[\craft\db\Query::scalar()]].
     * @param  DateTime  $startDate  The start of the time duration to select (inclusive)
     * @param  DateTime  $endDate  The end of the time duration to select (exclusive)
     * @param  string  $dateColumn  The column that represents the date
     * @param  string  $func  The aggregate function to call for each date interval ('count', 'sum', 'average', 'min', or 'max')
     * @param  string  $q  The column name or expression to pass into the aggregate function (make sure column names are `[[quoted]]`)
     * @param  array{intervalUnit?: 'hour'|'day'|'month'|'year'|null, categoryLabel?: string, valueLabel?: string, valueType?: string}  $options  Any customizations that should be made over the default options
     * @return array{columns: list<array{type: string, label: string}>, rows: list<array{string, float}>}
     */
    public static function get(
        Builder $query,
        DateTime $startDate,
        DateTime $endDate,
        string $dateColumn,
        string $func,
        string $q,
        array $options = [],
    ): array {
        // Setup
        $options = array_merge([
            'intervalUnit' => null,
            'categoryLabel' => t('Date'),
            'valueLabel' => t('Value'),
            'valueType' => 'number',
        ], $options);

        if (in_array($options['intervalUnit'], ['year', 'month', 'day', 'hour'], true)) {
            $intervalUnit = $options['intervalUnit'];
        } else {
            $intervalUnit = self::getIntervalUnit($startDate, $endDate);
        }

        // Prepare the query
        $phpDateFormat = match ($intervalUnit) {
            'year' => 'Y-01-01',
            'month' => 'Y-m-01',
            'day' => 'Y-m-d',
            'hour' => 'Y-m-d H:00:00',
            default => throw new Exception('Invalid interval unit: '.$intervalUnit),
        };

        // Assemble the data
        $rows = [];

        $cursorDate = clone $startDate;
        $endTimestamp = $endDate->getTimestamp();

        while ($cursorDate->getTimestamp() < $endTimestamp) {
            $cursorEndDate = clone $cursorDate;
            $cursorEndDate->modify('+1 '.$intervalUnit);
            $total = (float) (clone $query)
                ->where($dateColumn, '>=', $cursorDate)
                ->where($dateColumn, '<', $cursorEndDate)
                ->$func($q);
            $rows[] = [$cursorDate->format($phpDateFormat), $total];
            $cursorDate = $cursorEndDate;
        }

        return [
            'columns' => [
                [
                    'type' => $intervalUnit === 'hour' ? 'datetime' : 'date',
                    'label' => $options['categoryLabel'],
                ],
                [
                    'type' => $options['valueType'],
                    'label' => $options['valueLabel'],
                ],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Returns the interval unit that should be used in a run chart, based on the given start and end dates.
     *
     *
     * @return string The unit that the chart should use ('hour', 'day', 'month', or 'year')
     */
    public static function getIntervalUnit(DateTime $startDate, DateTime $endDate): string
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
     */
    /** @return array{shortDateFormats: array<string, string>} */
    public static function formats(): array
    {
        return [
            'shortDateFormats' => self::shortDateFormats(),
        ];
    }

    /**
     * Returns the D3 short date formats based on Yii's short date format
     */
    /** @return array<string, string> */
    public static function shortDateFormats(): array
    {
        $format = I18N::getFormattingLocale()->getDateFormat('short');

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
                $shortDateFormats[$unit] = preg_replace("/(^[$char]+\W+|\W+[$char]+)/iu", '', (string) $shortDateFormats[$unit]);
            }
        }

        // yii formats to d3 formats

        $yiiToD3Formats = [
            'day' => ['dd' => '%-d', 'd' => '%-d'],
            'month' => ['MM' => '%-m', 'M' => '%-m'],
            'year' => ['yyyy' => '%Y', 'yy' => '%y', 'y' => '%y'],
        ];

        foreach ($shortDateFormats as $unit => $format) {
            foreach ($yiiToD3Formats as $_formats) {
                foreach ($_formats as $yiiFormat => $d3Format) {
                    $pattern = "/($yiiFormat)/i";

                    preg_match($pattern, (string) $shortDateFormats[$unit], $matches);

                    if (count($matches) > 0) {
                        $shortDateFormats[$unit] = preg_replace($pattern, $d3Format, (string) $shortDateFormats[$unit]);

                        break;
                    }
                }
            }
        }

        return $shortDateFormats;
    }
}
