<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\enums\DateRangeType;
use craft\enums\PeriodType;
use DateInterval;
use DateTime;
use yii\base\InvalidArgumentException;

/**
 * Class DateRange
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DateRange
{
    /**
     * @return array
     */
    public static function rangeTypeOptions(): array
    {
        return [
            DateRangeType::Today => Craft::t('app', 'Today'),
            DateRangeType::ThisWeek => Craft::t('app', 'This week'),
            DateRangeType::ThisMonth => Craft::t('app', 'This month'),
            DateRangeType::ThisYear => Craft::t('app', 'This year'),
            DateRangeType::Past7Days => Craft::t('app', 'Past {num} days', ['num' => 7]),
            DateRangeType::Past30Days => Craft::t('app', 'Past {num} days', ['num' => 30]),
            DateRangeType::Past90Days => Craft::t('app', 'Past {num} days', ['num' => 90]),
            DateRangeType::PastYear => Craft::t('app', 'Past year'),
            DateRangeType::Before => Craft::t('app', 'Before…'),
            DateRangeType::After => Craft::t('app', 'After…'),
            DateRangeType::Range => Craft::t('app', 'Range…'),
        ];
    }

    /**
     * @return array
     */
    public static function periodTypeOptions(): array
    {
        return [
            PeriodType::Minutes => Craft::t('app', 'minutes ago'),
            PeriodType::Hours => Craft::t('app', 'hours ago'),
            PeriodType::Days => Craft::t('app', 'days ago'),
        ];
    }

    /**
     * Returns the start and end dates for a date range by its type.
     *
     * @param string $rangeType
     * @phpstan-param DateRangeType::* $rangeType
     * @return DateTime[]
     * @phpstan-return array{DateTime,DateTime}
     */
    public static function dateRangeByType(string $rangeType): array
    {
        return match ($rangeType) {
            DateRangeType::Today => [
                DateTimeHelper::today(),
                DateTimeHelper::tomorrow(),
            ],
            DateRangeType::ThisWeek => [
                DateTimeHelper::thisWeek(),
                DateTimeHelper::nextWeek(),
            ],
            DateRangeType::ThisMonth => [
                DateTimeHelper::thisMonth(),
                DateTimeHelper::nextMonth(),
            ],
            DateRangeType::ThisYear => [
                DateTimeHelper::thisYear(),
                DateTimeHelper::nextYear(),
            ],
            DateRangeType::Past7Days => [
                DateTimeHelper::today()->modify('-7 days'),
                DateTimeHelper::now(),
            ],
            DateRangeType::Past30Days => [
                DateTimeHelper::today()->modify('-30 days'),
                DateTimeHelper::now(),
            ],
            DateRangeType::Past90Days => [
                DateTimeHelper::today()->modify('-90 days'),
                DateTimeHelper::now(),
            ],
            DateRangeType::PastYear => [
                DateTimeHelper::today()->modify('-1 year'),
                DateTimeHelper::now(),
            ],
            default => throw new InvalidArgumentException("Invalid range type: $rangeType"),
        };
    }

    /**
     * @param float|int $length
     * @param string $periodType
     * @return DateInterval
     * @since 4.3.0
     */
    public static function dateIntervalByTimePeriod(float|int $length, string $periodType): DateInterval
    {
        // Cannot support months or years as they are variable in length
        if (!in_array($periodType, [
            PeriodType::Seconds,
            PeriodType::Minutes,
            PeriodType::Hours,
            PeriodType::Days,
            PeriodType::Weeks,
        ], true)) {
            throw new InvalidArgumentException("Invalid period type: $periodType");
        }

        $interval = $length;
        switch ($periodType) {
            case PeriodType::Weeks:
                $interval = ($interval * 7) * 86400;
                break;
            case PeriodType::Days:
                $interval *= 86400;
                break;
            case PeriodType::Hours:
                $interval = ($interval * 60) * 60;
                break;
            case PeriodType::Minutes:
                $interval *= 60;
                break;
        }

        return DateTimeHelper::toDateInterval($interval);
    }
}
