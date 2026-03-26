<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Shared\Enums\DateRangePeriod;
use CraftCms\Cms\Shared\Enums\DateRangeType;
use DateInterval;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class DateRange
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 * @deprecated 6.0.0 use {@see DateRangeType} and {@see DateRangePeriod} directly.
 */
class DateRange
{
    public const string TYPE_TODAY = DateRangeType::Today->value;
    public const string TYPE_THIS_WEEK = DateRangeType::ThisWeek->value;
    public const string TYPE_THIS_MONTH = DateRangeType::ThisMonth->value;
    public const string TYPE_THIS_YEAR = DateRangeType::ThisYear->value;
    public const string TYPE_PAST_7_DAYS = DateRangeType::Past7Days->value;
    public const string TYPE_PAST_30_DAYS = DateRangeType::Past30Days->value;
    public const string TYPE_PAST_90_DAYS = DateRangeType::Past90Days->value;
    public const string TYPE_PAST_YEAR = DateRangeType::PastYear->value;
    public const string TYPE_BEFORE = DateRangeType::Before->value;
    public const string TYPE_AFTER = DateRangeType::After->value;
    public const string TYPE_RANGE = DateRangeType::Range->value;

    public const string PERIOD_SECONDS_AGO = DateRangePeriod::SecondsAgo->value;
    public const string PERIOD_MINUTES_AGO = DateRangePeriod::MinutesAgo->value;
    public const string PERIOD_HOURS_AGO = DateRangePeriod::HoursAgo->value;
    public const string PERIOD_DAYS_AGO = DateRangePeriod::DaysAgo->value;
    public const string PERIOD_WEEKS_AGO = DateRangePeriod::WeeksAgo->value;
    public const string PERIOD_SECONDS_FROM_NOW = DateRangePeriod::SecondsFromNow->value;
    public const string PERIOD_MINUTES_FROM_NOW = DateRangePeriod::MinutesFromNow->value;
    public const string PERIOD_HOURS_FROM_NOW = DateRangePeriod::HoursFromNow->value;
    public const string PERIOD_DAYS_FROM_NOW = DateRangePeriod::DaysFromNow->value;
    public const string PERIOD_WEEKS_FROM_NOW = DateRangePeriod::WeeksFromNow->value;

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
        $rangeType = DateRangeType::from($rangeType);

        try {
            return $rangeType->range();
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
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
        $periodType = DateRangePeriod::from($periodType);

        try {
            return $periodType->interval($length);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
