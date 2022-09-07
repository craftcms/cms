<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Closure;
use Codeception\Test\Unit;
use Craft;
use craft\enums\DateRangeType;
use craft\enums\PeriodType;
use craft\helpers\DateRangeHelper;
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
     * @param string $range
     * @param Closure $startDate
     * @param Closure $endDate
     * @return void
     * @dataProvider getDatesByDateRangeDataProvider
     */
    public function testGetDatesByDateRange(string $range, Closure $startDate, Closure $endDate): void
    {
        /** @var DateTime $start */
        $start = $startDate();
        /** @var DateTime $end */
        $end = $endDate();
        $dates = DateRangeHelper::getDatesByDateRange($range);

        self::assertArrayHasKey('startDate', $dates);
        self::assertArrayHasKey('endDate', $dates);

        // Simplify the comparison to avoid any micro differences due to slow tests
        self::assertEquals($start->format('Y-m-d H:i:s'), $dates['startDate']->format('Y-m-d H:i:s'));
        self::assertEquals($end->format('Y-m-d H:i:s'), $dates['endDate']->format('Y-m-d H:i:s'));
    }

    /**
     * @return array[]
     */
    public function getDatesByDateRangeDataProvider(): array
    {
        return [
            'today' => [
                DateRangeType::Today, // Range
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->setTime(0, 0);
                }, // Start Date
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->setTime(23, 59, 59);
                }, // End Date
            ],
            'thisMonth' => [
                DateRangeType::ThisMonth,
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->modify('first day of this month')
                        ->setTime(0, 0);
                },
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->modify('last day of this month')
                        ->setTime(23, 59, 59);
                },
            ],
            'thisYear' => [
                DateRangeType::ThisYear,
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->modify('1st January ' . date('Y'))
                        ->setTime(0, 0);
                },
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->modify('last day of December ' . date('Y'))
                        ->setTime(23, 59, 59);
                },
            ],
            'past7Days' => [
                DateRangeType::Past7Days,
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->sub(new DateInterval('P7D'));
                },
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                },
            ],
            'past30Days' => [
                DateRangeType::Past30Days,
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->sub(new DateInterval('P30D'));
                },
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                },
            ],
            'past90Days' => [
                DateRangeType::Past90Days,
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->sub(new DateInterval('P90D'));
                },
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                },
            ],
            'pastYear' => [
                DateRangeType::PastYear,
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                        ->sub(new DateInterval('P1Y'));
                },
                static function() {
                    return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                },
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
        $dateInterval = DateRangeHelper::getDateIntervalByTimePeriod($length, $periodType);

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
            'daysFull' => [4, PeriodType::Days, $fourDays],
            'daysDecimal' => [4.5, PeriodType::Days, $fourAndHalfDays],
            'hoursFull' => [4, PeriodType::Hours, new DateInterval('PT4H')],
            'hoursDecimal' => [4.5, PeriodType::Hours, $fourAndHalfHours],
            'minutesFull' => [4, PeriodType::Minutes, new DateInterval('PT4M')],
            'minutesDecimal' => [4.5, PeriodType::Minutes, $fourAndHalfMinutes],
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
    }
}
