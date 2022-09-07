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
 * Class DateRangeHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DateRangeHelper
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
     * @phpstan-param int<0,6> $day
     */
    private static function _dayName(int $day): string
    {
        return match ($day) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        };
    }

    /**
     * Returns the start and end date for a date range.
     *
     * @param string $rangeType
     * @param DateTime|null $date If no date is passed, returned dates are based on the current timestamp
     * @return array
     */
    public static function getDatesByDateRange(string $rangeType, ?DateTime $date = null): array
    {
        $startDate = $date ?? DateTimeHelper::now();
        $endDate = clone $startDate;
        switch ($rangeType) {
            case DateRangeType::Today:
                $startDate->setTime(0, 0);
                $endDate->setTime(23, 59, 59);
                break;
            case DateRangeType::ThisWeek:
                $dayName = DateTimeHelper::now()->format('l');

                $startDayName = self::_dayName(DateTimeHelper::weekStartDay());
                if ($dayName != $startDayName) {
                    $startDate->modify("last $startDayName");
                }
                $startDate->setTime(0, 0);

                $endDayName = self::_dayName(DateTimeHelper::weekEndDay());
                if ($dayName != $endDayName) {
                    $endDate->modify("next $endDayName");
                }
                $endDate->setTime(23, 59, 59);
                break;
            case DateRangeType::ThisMonth:
                $startDate->modify('first day of this month');
                $startDate->setTime(0, 0);

                $endDate->modify('last day of this month');
                $endDate->setTime(23, 59, 59);
                break;
            case DateRangeType::ThisYear:
                $startDate->setDate((int)$startDate->format('Y'), 1, 1);
                $startDate->setTime(0, 0);

                $endDate->setDate((int)$endDate->format('Y'), 12, 31);
                $endDate->setTime(23, 59, 59);
                break;
            case DateRangeType::Past7Days:
                $startDate->sub(DateTimeHelper::toDateInterval('P7D'));
                break;
            case DateRangeType::Past30Days:
                $startDate->sub(DateTimeHelper::toDateInterval('P30D'));
                break;
            case DateRangeType::Past90Days:
                $startDate->sub(DateTimeHelper::toDateInterval('P90D'));
                break;
            case DateRangeType::PastYear:
                $startDate->sub(DateTimeHelper::toDateInterval('P1Y'));
                break;
        }

        return compact('startDate', 'endDate');
    }

    /**
     * @param float|int $length
     * @param string $periodType
     * @return DateInterval
     * @since 4.3.0
     */
    public static function getDateIntervalByTimePeriod(float|int $length, string $periodType): DateInterval
    {
        // Cannot support months or years as they are variable in length
        if (!in_array($periodType, [
            PeriodType::Seconds,
            PeriodType::Minutes,
            PeriodType::Hours,
            PeriodType::Days,
            PeriodType::Weeks,
        ], true)) {
            throw new InvalidArgumentException('Invalid period type');
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
