<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\enums\DateRange;
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
    public const RANGE = 'range';
    public const BEFORE = 'before';
    public const AFTER = 'after';

    /**
     * @return array
     */
    public static function getDateRangeOptions(): array
    {
        return [
            DateRange::Today => Craft::t('app', 'Today'),
            DateRange::ThisWeek => Craft::t('app', 'This week'),
            DateRange::ThisMonth => Craft::t('app', 'This month'),
            DateRange::ThisYear => Craft::t('app', 'This year'),
            DateRange::Past7Days => Craft::t('app', 'Past {num} days', ['num' => 7]),
            DateRange::Past30Days => Craft::t('app', 'Past {num} days', ['num' => 30]),
            DateRange::Past90Days => Craft::t('app', 'Past {num} days', ['num' => 90]),
            DateRange::PastYear => Craft::t('app', 'Past year'),
            self::RANGE => Craft::t('app', 'Date range'),
            self::BEFORE => Craft::t('app', 'Before…'),
            self::AFTER => Craft::t('app', 'After…'),
        ];
    }

    /**
     * @return array
     */
    public static function getPeriodTypeOptions(): array
    {
        return [
            PeriodType::Minutes => Craft::t('app', 'Minutes'),
            PeriodType::Hours => Craft::t('app', 'Hours'),
            PeriodType::Days => Craft::t('app', 'Days'),
        ];
    }

    /**
     * Returns the start or end name of the day for the week based on user preference.
     *
     * @param bool $start
     * @return int|string
     */
    public static function getWeekDayString(bool $start = true): int|string
    {
        $dayMap = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $startDay = 1;
        $user = Craft::$app->getUser()->getIdentity();
        if ($user && ($user->getPreference('weekDayStart') || $user->getPreference('weekDayStart') === 0)) {
            $startDay = $user->getPreference('weekDayStart');
        }

        // We are looking for the end day of the week
        if ($start === false) {
            --$startDay;
            $startDay = $startDay < 0 ? count($dayMap) - 1 : $startDay;
        }

        return $dayMap[$startDay];
    }

    /**
     * Returns the start and end date for a date range.
     *
     * @param string $dateRange
     * @param DateTime|null $date If no date is passed, returned dates are based on the current timestamp
     * @return array
     */
    public static function getDatesByDateRange(string $dateRange, ?DateTime $date = null): array
    {
        $startDate = $date ?? DateTimeHelper::now();
        $endDate = clone $startDate;
        switch ($dateRange) {
            case DateRange::Today:
                $startDate->setTime(0, 0);
                $endDate->setTime(23, 59, 59);
                break;
            case DateRange::ThisWeek:
                if (DateTimeHelper::now()->format('l') != self::getWeekDayString()) {
                    $startDate->modify('last ' . self::getWeekDayString());
                }
                $startDate->setTime(0, 0);

                if (DateTimeHelper::now()->format('l') != self::getWeekDayString(false)) {
                    $endDate->modify('next ' . self::getWeekDayString(false));
                }
                $endDate->setTime(23, 59, 59);
                break;
            case DateRange::ThisMonth:
                $startDate->modify('first day of this month');
                $startDate->setTime(0, 0);

                $endDate->modify('last day of this month');
                $endDate->setTime(23, 59, 59);
                break;
            case DateRange::ThisYear:
                $startDate->setDate((int)$startDate->format('Y'), 1, 1);
                $startDate->setTime(0, 0);

                $endDate->setDate((int)$endDate->format('Y'), 12, 31);
                $endDate->setTime(23, 59, 59);
                break;
            case DateRange::Past7Days:
                $startDate->sub(DateTimeHelper::toDateInterval('P7D'));
                break;
            case DateRange::Past30Days:
                $startDate->sub(DateTimeHelper::toDateInterval('P30D'));
                break;
            case DateRange::Past90Days:
                $startDate->sub(DateTimeHelper::toDateInterval('P90D'));
                break;
            case DateRange::PastYear:
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
