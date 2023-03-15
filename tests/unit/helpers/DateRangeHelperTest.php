<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\helpers\DateRange;
use craft\helpers\DateTimeHelper;
use craft\test\TestCase;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use UnitTester;

/**
 * Unit tests for the DateRange Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DateRangeHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var DateTimeZone
     */
    protected DateTimeZone $systemTimezone;

    /**
     * @var DateTimeZone
     */
    protected DateTimeZone $utcTimezone;

    /**
     * @var DateTimeZone
     */
    protected DateTimeZone $asiaTokyoTimezone;

    /**
     * @param string $rangeType
     * @phpstan-param DateRange::TYPE_* $rangeType
     * @param callable $expectedStartDate
     * @phpstan-param callable():DateTime $expectedStartDate
     * @param callable $expectedEndDate
     * @phpstan-param callable():DateTime $expectedEndDate
     * @return void
     * @dataProvider dateRangeByTypeDataProvider
     */
    public function testDateRangeByType(string $rangeType, callable $expectedStartDate, callable $expectedEndDate): void
    {
        [$startDate, $endDate] = DateRange::dateRangeByType($rangeType);

        // Simplify the comparison to avoid any micro differences due to slow tests
        self::assertEquals($expectedStartDate()->getTimestamp(), $startDate->getTimestamp());
        self::assertEquals($expectedEndDate()->getTimestamp(), $endDate->getTimestamp());
    }

    /**
     * @return array[]
     */
    public function dateRangeByTypeDataProvider(): array
    {
        return [
            'today' => [
                DateRange::TYPE_TODAY,
                fn() => DateTimeHelper::today(),
                fn() => DateTimeHelper::tomorrow(),
            ],
            'thisMonth' => [
                DateRange::TYPE_THIS_MONTH,
                fn() => DateTimeHelper::thisMonth(),
                fn() => DateTimeHelper::nextMonth(),
            ],
            'thisYear' => [
                DateRange::TYPE_THIS_YEAR,
                fn() => DateTimeHelper::thisYear(),
                fn() => DateTimeHelper::nextYear(),
            ],
            'past7Days' => [
                DateRange::TYPE_PAST_7_DAYS,
                fn() => DateTimeHelper::today()->modify('-7 days'),
                fn() => DateTimeHelper::now(),
            ],
            'past30Days' => [
                DateRange::TYPE_PAST_30_DAYS,
                fn() => DateTimeHelper::today()->modify('-30 days'),
                fn() => DateTimeHelper::now(),
            ],
            'past90Days' => [
                DateRange::TYPE_PAST_90_DAYS,
                fn() => DateTimeHelper::today()->modify('-90 days'),
                fn() => DateTimeHelper::now(),
            ],
            'pastYear' => [
                DateRange::TYPE_PAST_YEAR,
                fn() => DateTimeHelper::today()->modify('-1 year'),
                fn() => DateTimeHelper::now(),
            ],
        ];
    }

    /**
     * @param float|int $length
     * @param string $periodType
     * @param DateInterval $expected
     * @return void
     * @dataProvider getDateIntervalByTimePeriodDataProvider
     */
    public function testGetDateIntervalByTimePeriod(float|int $length, string $periodType, DateInterval $expected): void
    {
        $dateInterval = DateRange::dateIntervalByTimePeriod($length, $periodType);
        self::assertEquals($expected, $dateInterval);
    }

    /**
     * @return array[]
     * @throws Exception
     */
    public function getDateIntervalByTimePeriodDataProvider(): array
    {
        $now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
        $then = (clone $now)->modify("+" . (86400 * 4) . " seconds");
        $fourDays = $now->diff($then);
        $then->modify('+43200 seconds');
        $fourAndHalfDays = $now->diff($then);
        $fourAndHalfHours = (new DateInterval('PT4H'));
        $fourAndHalfHours->i = 30;
        $fourAndHalfMinutes = (new DateInterval('PT4M'));
        $fourAndHalfMinutes->s = 30;

        return [
            'daysFull' => [4, DateRange::PERIOD_DAYS_FROM_NOW, $fourDays],
            'daysDecimal' => [4.5, DateRange::PERIOD_DAYS_FROM_NOW, $fourAndHalfDays],
            'hoursFull' => [4, DateRange::PERIOD_HOURS_FROM_NOW, new DateInterval('PT4H')],
            'hoursDecimal' => [4.5, DateRange::PERIOD_HOURS_FROM_NOW, $fourAndHalfHours],
            'minutesFull' => [4, DateRange::PERIOD_MINUTES_FROM_NOW, new DateInterval('PT4M')],
            'minutesDecimal' => [4.5, DateRange::PERIOD_MINUTES_FROM_NOW, $fourAndHalfMinutes],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(
            Craft::$app->getUsers()->getUserById(1)
        );
        Craft::$app->getUser()->getIdentity()->password = '$2y$13$tAtJfYFSRrnOkIbkruGGEu7TPh0Ixvxq0r.XgWqIgNWuWpxpA7SxK';

        Craft::$app->setTimeZone('America/Los_Angeles');
        $this->systemTimezone = new DateTimeZone(Craft::$app->getTimeZone());
        $this->utcTimezone = new DateTimeZone('UTC');
        $this->asiaTokyoTimezone = new DateTimeZone('Asia/Tokyo');

        DateTimeHelper::pause();
    }

    protected function _after(): void
    {
        DateTimeHelper::resume();
    }
}
