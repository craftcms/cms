<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Dashboard\Chart;
use DateTime;
use Illuminate\Database\Query\Builder;
use function CraftCms\Cms\t;

/**
 * Class ChartHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Dashboard\Chart} instead.
 */
class ChartHelper extends Chart
{
    public static function getRunChartDataFromQuery(
        Builder $query,
        DateTime $startDate,
        DateTime $endDate,
        string $dateColumn,
        string $func,
        string $q,
        array $options = [],
    ): array {
        return parent::get($query, $startDate, $endDate, $dateColumn, $func, $q, $options);
    }

    public static function getRunChartIntervalUnit(DateTime $startDate, DateTime $endDate): string
    {
        return parent::getIntervalUnit($startDate, $endDate);
    }

    /**
     * Returns the predefined date ranges with their label, start date and end date.
     *
     * @return array
     */
    public static function dateRanges(): array
    {
        return [
            'd7' => [
                'label' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 7]),
                'startDate' => '-7 days',
                'endDate' => null,
            ],
            'd30' => [
                'label' => t('Last {num, number} {num, plural, =1{day} other{days}}', ['num' => 30]),
                'startDate' => '-30 days',
                'endDate' => null,
            ],
            'lastweek' => ['label' => t('Last Week'), 'startDate' => '-2 weeks', 'endDate' => '-1 week'],
            'lastmonth' => [
                'label' => t('Last Month'),
                'startDate' => '-2 months',
                'endDate' => '-1 month',
            ],
        ];
    }
}
