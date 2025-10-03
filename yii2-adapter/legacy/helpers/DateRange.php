<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

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
    public const TYPE_TODAY = 'today';
    public const TYPE_THIS_WEEK = 'thisWeek';
    public const TYPE_THIS_MONTH = 'thisMonth';
    public const TYPE_THIS_YEAR = 'thisYear';
    public const TYPE_PAST_7_DAYS = 'past7Days';
    public const TYPE_PAST_30_DAYS = 'past30Days';
    public const TYPE_PAST_90_DAYS = 'past90Days';
    public const TYPE_PAST_YEAR = 'pastYear';
    public const TYPE_BEFORE = 'before';
    public const TYPE_AFTER = 'after';
    public const TYPE_RANGE = 'range';

    public const PERIOD_SECONDS_AGO = 'secondsAgo';
    public const PERIOD_MINUTES_AGO = 'minutesAgo';
    public const PERIOD_HOURS_AGO = 'hoursAgo';
    public const PERIOD_DAYS_AGO = 'daysAgo';
    public const PERIOD_WEEKS_AGO = 'weeksAgo';
    public const PERIOD_SECONDS_FROM_NOW = 'secondsFromNow';
    public const PERIOD_MINUTES_FROM_NOW = 'minutesFromNow';
    public const PERIOD_HOURS_FROM_NOW = 'hoursFromNow';
    public const PERIOD_DAYS_FROM_NOW = 'daysFromNow';
    public const PERIOD_WEEKS_FROM_NOW = 'weeksFromNow';

    /**
     * Returns the start and end dates for a date range by its type.
     *
     * @param string $rangeType
     * @phpstan-param self::TYPE_* $rangeType
     * @return DateTime[]
     * @phpstan-return array{DateTime,DateTime}
     */
    public static function dateRangeByType(string $rangeType): array
    {
        return match ($rangeType) {
            self::TYPE_TODAY => [
                DateTimeHelper::today(),
                DateTimeHelper::tomorrow(),
            ],
            self::TYPE_THIS_WEEK => [
                DateTimeHelper::thisWeek(),
                DateTimeHelper::nextWeek(),
            ],
            self::TYPE_THIS_MONTH => [
                DateTimeHelper::thisMonth(),
                DateTimeHelper::nextMonth(),
            ],
            self::TYPE_THIS_YEAR => [
                DateTimeHelper::thisYear(),
                DateTimeHelper::nextYear(),
            ],
            self::TYPE_PAST_7_DAYS => [
                DateTimeHelper::today()->modify('-7 days'),
                DateTimeHelper::now(),
            ],
            self::TYPE_PAST_30_DAYS => [
                DateTimeHelper::today()->modify('-30 days'),
                DateTimeHelper::now(),
            ],
            self::TYPE_PAST_90_DAYS => [
                DateTimeHelper::today()->modify('-90 days'),
                DateTimeHelper::now(),
            ],
            self::TYPE_PAST_YEAR => [
                DateTimeHelper::today()->modify('-1 year'),
                DateTimeHelper::now(),
            ],
            default => throw new InvalidArgumentException("Invalid range type: $rangeType"),
        };
    }

    /**
     * @param float|int $length
     * @param string $periodType
     * @phpstan-param DateRange::PERIOD_* $periodType
     * @return DateInterval
     * @since 4.3.0
     */
    public static function dateIntervalByTimePeriod(float|int $length, string $periodType): DateInterval
    {
        // Cannot support months or years as they are variable in length
        if (!in_array($periodType, [
            DateRange::PERIOD_SECONDS_AGO,
            DateRange::PERIOD_MINUTES_AGO,
            DateRange::PERIOD_HOURS_AGO,
            DateRange::PERIOD_DAYS_AGO,
            DateRange::PERIOD_WEEKS_AGO,
            DateRange::PERIOD_SECONDS_FROM_NOW,
            DateRange::PERIOD_MINUTES_FROM_NOW,
            DateRange::PERIOD_HOURS_FROM_NOW,
            DateRange::PERIOD_DAYS_FROM_NOW,
            DateRange::PERIOD_WEEKS_FROM_NOW,
        ], true)) {
            throw new InvalidArgumentException("Invalid period type: $periodType");
        }

        if (in_array($periodType, [DateRange::PERIOD_SECONDS_AGO, DateRange::PERIOD_SECONDS_FROM_NOW])) {
            $length = $intLength = round($length);
        } else {
            $intLength = floor($length);
        }

        $pos = in_array($periodType, [
            DateRange::PERIOD_WEEKS_FROM_NOW,
            DateRange::PERIOD_DAYS_FROM_NOW,
            DateRange::PERIOD_HOURS_FROM_NOW,
            DateRange::PERIOD_MINUTES_FROM_NOW,
            DateRange::PERIOD_SECONDS_FROM_NOW,
        ]);

        $str = sprintf('%s%s %s', $pos ? '' : '-', $intLength, match ($periodType) {
            DateRange::PERIOD_WEEKS_AGO, DateRange::PERIOD_WEEKS_FROM_NOW => 'weeks',
            DateRange::PERIOD_DAYS_AGO, DateRange::PERIOD_DAYS_FROM_NOW => 'days',
            DateRange::PERIOD_HOURS_AGO, DateRange::PERIOD_HOURS_FROM_NOW => 'hours',
            DateRange::PERIOD_MINUTES_AGO, DateRange::PERIOD_MINUTES_FROM_NOW => 'minutes',
            DateRange::PERIOD_SECONDS_AGO, DateRange::PERIOD_SECONDS_FROM_NOW => 'seconds',
        });

        $rem = $length - $intLength;

        if ($rem) {
            $str .= sprintf(" %s %s", $pos ? '+' : '-', match ($periodType) {
                DateRange::PERIOD_WEEKS_AGO, DateRange::PERIOD_WEEKS_FROM_NOW => sprintf('%s days', round($rem * 7)),
                DateRange::PERIOD_DAYS_AGO, DateRange::PERIOD_DAYS_FROM_NOW => sprintf('%s hours', round($rem * 24)),
                DateRange::PERIOD_HOURS_AGO, DateRange::PERIOD_HOURS_FROM_NOW => sprintf('%s minutes', round($rem * 60)),
                DateRange::PERIOD_MINUTES_AGO, DateRange::PERIOD_MINUTES_FROM_NOW => sprintf('%s seconds', round($rem * 60)),
                DateRange::PERIOD_SECONDS_AGO, DateRange::PERIOD_SECONDS_FROM_NOW => sprintf('%s seconds', round($rem)),
            });
        }

        return DateInterval::createFromDateString($str);
    }
}
