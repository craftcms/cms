<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\DateTimeHelper;
use craft\test\TestCase;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use UnitTester;

/**
 * Unit tests for the DateTime Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class DateTimeHelperTest extends TestCase
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
     * @dataProvider constantsDataProvider
     * @param int $expected
     * @param int $actual
     */
    public function testConstants(int $expected, int $actual): void
    {
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    public function testCurrentUtcDateTime(): void
    {
        self::assertSame(
            (new DateTime('now', $this->utcTimezone))->format('Y-m-d H:i:s'),
            DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @throws Exception
     */
    public function testCurrentUtcDateTimeStamp(): void
    {
        self::assertSame(
            DateTimeHelper::currentTimeStamp(),
            (new DateTime('now', $this->utcTimezone))->getTimestamp()
        );
    }

    /**
     * @dataProvider secondsToHumanTimeDurationDataProvider
     * @param string $expected
     * @param int $seconds
     * @param bool $showSeconds
     */
    public function testSecondsToHumanTimeDuration(string $expected, int $seconds, bool $showSeconds = true): void
    {
        self::assertSame($expected, DateTimeHelper::secondsToHumanTimeDuration($seconds, $showSeconds));
    }

    /**
     * What we are testing here is that if we tell the DtHelper to not assume a timezone and set it to system.
     * That all formats are converted to the system timezone from the inputted system timezone. ie an array like this:
     *
     * ['date' => '2018-08-08', 'timezone' => 'Asia/Tokyo']
     *
     * toDateTime must start the DateTime from Asia/Tokyo instead of UTC(The default starting point) and then convert it to the system timezone.
     *
     * @dataProvider formatsWithTimezoneDataProvider
     * @param mixed $value
     * @param callable $expectedResult
     * @throws Exception
     */
    public function testUtcIgnorance(mixed $value, callable $expectedResult): void
    {
        $expectedResult = $expectedResult();

        $toDateTime = DateTimeHelper::toDateTime($value);
        $systemTz = $this->systemTimezone->getName();

        self::assertInstanceOf(DateTime::class, $toDateTime);
        self::assertSame($systemTz, $toDateTime->getTimezone()->getName());

        // Ensure the expected result is in the same timezone as the system.
        self::assertSame($systemTz, $expectedResult->getTimezone()->getName());

        // Are they the same?
        self::assertSame($expectedResult->format('Y-m-d H:i:s'), $toDateTime->format('Y-m-d H:i:s'));
    }

    /**
     * @dataProvider toDateTimeDataProvider
     * @param callable|DateTime|false $expected
     * @param mixed $value
     * @throws Exception
     */
    public function testToDateTime(callable|DateTime|false $expected, mixed $value): void
    {
        if (is_callable($expected)) {
            $expected = $expected();
        }

        if ($expected === false) {
            self::assertFalse(DateTimeHelper::toDateTime($value));
        } else {
            $timestamp = $expected->getTimestamp();
            $date = DateTimeHelper::toDateTime($value);
            self::assertInstanceOf(DateTime::class, $date);
            self::assertEqualsWithDelta($timestamp, $date->getTimestamp(), 1);
        }
    }

    /**
     * Test that if we set the $setToSystemTimezone value to false that toDateTime creates a tz in UTC.
     *
     * @dataProvider simpleDateTimeFormatsDataProvider
     * @param mixed $value
     * @throws Exception
     */
    public function testUtcDefault(mixed $value): void
    {
        $toDateTime = DateTimeHelper::toDateTime($value, false, false);
        self::assertSame($this->utcTimezone->getName(), $toDateTime->getTimezone()->getName());
    }

    /**
     * Test that dateTime is created with the passed in timezone IF $setSystemTimezone is set to false.
     *
     * @dataProvider toDateTimeWithTzFormatsDataProvider
     * @param mixed $value
     * @param DateTime $expectedResult
     * @param DateTimeZone $expectedTimezone
     * @throws Exception
     */
    public function testToDateTimeRespectsTz(mixed $value, DateTime $expectedResult, DateTimeZone $expectedTimezone): void
    {
        $toDateTime = DateTimeHelper::toDateTime($value, false, false);

        self::assertInstanceOf(DateTime::class, $toDateTime);
        self::assertSame($expectedTimezone->getName(), $toDateTime->getTimezone()->getName());
        self::assertSame($expectedTimezone->getName(), $expectedResult->getTimezone()->getName());
        self::assertSame($expectedResult->format('Y-m-d H:i:s'), $toDateTime->format('Y-m-d H:i:s'));
    }

    /**
     * @dataProvider toDateTimeFormatsDataProvider
     * @param mixed $value
     * @param callable $expectedResult
     * @param string|null $closureParam
     * @throws Exception
     */
    public function testToDateTimeCreation(mixed $value, callable $expectedResult, ?string $closureParam = null): void
    {
        $expectedResult = $closureParam ? $expectedResult($closureParam) : $expectedResult();

        $toDateTime = DateTimeHelper::toDateTime($value);

        self::assertSame($expectedResult->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime($value)->format('Y-m-d H:i:s'));
        self::assertInstanceOf(DateTime::class, $toDateTime);
    }

    /**
     * DateTimeHelper::toDateTime:145-148
     */
    public function testEmptyArrayDateDefault(): void
    {
        $dt = DateTimeHelper::toDateTime(['date' => '', 'time' => '08:00PM']);

        $created = new DateTime('now', $this->utcTimezone);
        $comparable = new DateTime($created->format('Y-m-d') . ' 20:00:00', $this->utcTimezone);
        $comparable->setTimezone($this->systemTimezone);

        self::assertSame($comparable->format('Y-m-d H:i:s'), $dt->format('Y-m-d H:i:s'));
    }

    /**
     * @dataProvider normalizeTimeZoneDataProvider
     * @param string|false $expected
     * @param string $timeZone
     */
    public function testNormalizeTimeZone(string|false $expected, string $timeZone): void
    {
        self::assertSame($expected, DateTimeHelper::normalizeTimeZone($timeZone));
    }

    /**
     * @dataProvider isIsIso8601DataProvider
     * @param bool $expected
     * @param mixed $value
     */
    public function testIsIso8601(bool $expected, mixed $value): void
    {
        self::assertSame($expected, DateTimeHelper::isIso8601($value));
    }

    /**
     * @dataProvider humanIntervalFromDurationDataProvider
     * @param string $expected
     * @param string $duration
     * @param bool $showSeconds
     * @throws Exception
     */
    public function testHumanIntervalFromDuration(string $expected, string $duration, bool $showSeconds = true): void
    {
        $dateInterval = new DateInterval($duration);
        self::assertSame($expected, DateTimeHelper::humanDurationFromInterval($dateInterval, $showSeconds));
    }

    /**
     * @throws Exception
     */
    public function testIsToday(): void
    {
        $dateTime = new DateTime('now');
        self::assertTrue(DateTimeHelper::isToday($dateTime));

        $dateTime->modify('-1 days');
        self::assertFalse(DateTimeHelper::isToday($dateTime));

        $dateTime->modify('-1 days');
        self::assertFalse(DateTimeHelper::isToday($dateTime));

        $dateTime->modify('+2 days');
        self::assertTrue(DateTimeHelper::isToday($dateTime));
    }

    /**
     * @throws Exception
     */
    public function testYesterday(): void
    {
        $dateTime = new DateTime('now');

        $dateTime->modify('-1 days');
        self::assertTrue(DateTimeHelper::isYesterday($dateTime));

        $dateTime->modify('-1 days');
        self::assertFalse(DateTimeHelper::isYesterday($dateTime));

        $dateTime->modify('+2 days');
        self::assertFalse(DateTimeHelper::isYesterday($dateTime));

        $dateTime = new DateTime('yesterday');
        self::assertTrue(DateTimeHelper::isYesterday($dateTime));
    }

    /**
     * @throws Exception
     */
    public function testThisYearCheck(): void
    {
        $dateTime = new DateTime('now');
        self::assertTrue(DateTimeHelper::isThisYear($dateTime));

        $dateTime->modify('-1 years');
        self::assertFalse(DateTimeHelper::isThisYear($dateTime));

        $dateTime->modify('+2 years');
        self::assertFalse(DateTimeHelper::isThisYear($dateTime));
    }

    /**
     * @throws Exception
     */
    public function testThisWeek(): void
    {
        $dateTime = new DateTime('now');
        self::assertTrue(DateTimeHelper::isThisWeek($dateTime));

        $dateTime->modify('-1 weeks');
        self::assertFalse(DateTimeHelper::isThisWeek($dateTime));


        $dateTime->modify('+1 weeks');
        self::assertTrue(DateTimeHelper::isThisWeek($dateTime));

        $dateTime->modify('+2 weeks');
        self::assertFalse(DateTimeHelper::isYesterday($dateTime));
    }

    /**
     * @throws Exception
     */
    public function testIsInThePast(): void
    {
        $systemTz = new DateTimeZone(Craft::$app->getTimeZone());
        $dateTime = new DateTime('now', $systemTz);
        $dateTime->modify('-5 seconds');
        self::assertTrue(DateTimeHelper::isInThePast($dateTime));

        $dateTime->modify('-1 minutes');
        self::assertTrue(DateTimeHelper::isInThePast($dateTime));

        $dateTime->modify('+2 minutes');
        self::assertFalse(DateTimeHelper::isInThePast($dateTime));
    }

    /**
     * @throws Exception
     */
    public function testIsThisMonth(): void
    {
        $dateTime = new DateTime('now');
        self::assertTrue(DateTimeHelper::isThisMonth($dateTime));

        $dateTime->modify('-35 days');
        self::assertFalse(DateTimeHelper::isThisMonth($dateTime));
    }

    /**
     * @dataProvider secondsToIntervalDataProvider
     * @param int $shortResult
     * @param int $longResult
     * @param int $input
     */
    public function testSecondsToInterval(int $shortResult, int $longResult, int $input): void
    {
        $interval = DateTimeHelper::secondsToInterval($input);
        self::assertSame($shortResult, $interval->s);
        self::assertSame($longResult, (int)$interval->format('%s%d%h%m'));
    }

    /**
     * @dataProvider intervalToSecondsDataProvider
     * @param int $expected
     * @param string $duration
     * @throws Exception
     */
    public function testIntervalToSeconds(int $expected, string $duration): void
    {
        $dateInterval = new DateInterval($duration);
        self::assertSame($expected, DateTimeHelper::intervalToSeconds($dateInterval));
    }

    /**
     * @dataProvider toIso8601DataProvider
     * @param string|false $expected
     * @param mixed $date
     */
    public function testToIso8601(string|false $expected, mixed $date): void
    {
        self::assertSame($expected, DateTimeHelper::toIso8601($date));
    }

    /**
     * @dataProvider timeZoneAbbreviationDataProvider
     * @param string $expected
     * @param string $timeZone
     */
    public function testTimeZoneAbbreviation(string $expected, string $timeZone): void
    {
        self::assertSame($expected, DateTimeHelper::timeZoneAbbreviation($timeZone));
    }

    /**
     * @dataProvider isValidTimeStampDataProvider
     * @param bool $expected
     * @param mixed $timestamp
     */
    public function testIsValidTimeStamp(bool $expected, mixed $timestamp): void
    {
        self::assertSame($expected, DateTimeHelper::isValidTimeStamp($timestamp));
    }

    /**
     * @dataProvider isInvalidIntervalStringDataProvider
     * @param bool $expected
     * @param string $intervalString
     */
    public function testIsValidIntervalString(bool $expected, string $intervalString): void
    {
        self::assertSame($expected, DateTimeHelper::isValidIntervalString($intervalString));
    }

    /**
     * @dataProvider timeZoneOffsetDataDataProvider
     * @param string|string[] $expected
     * @param string $timeZone
     */
    public function testTimeZoneOffset(string|array $expected, string $timeZone): void
    {
        if (is_string($expected)) {
            self::assertSame($expected, DateTimeHelper::timeZoneOffset($timeZone));
        } else {
            self::assertContains(DateTimeHelper::timeZoneOffset($timeZone), $expected);
        }
    }

    /**
     * @return array
     */
    public function constantsDataProvider(): array
    {
        return [
            [86400, DateTimeHelper::SECONDS_DAY],
            [3600, DateTimeHelper::SECONDS_HOUR],
            [60, DateTimeHelper::SECONDS_MINUTE],
            [2629740, DateTimeHelper::SECONDS_MONTH],
            [31556874, DateTimeHelper::SECONDS_YEAR],
        ];
    }

    /**
     * @return array
     */
    public function secondsToHumanTimeDurationDataProvider(): array
    {
        return [
            ['22 seconds', 22],
            ['1 second', 1],
            ['2 minutes', 120],
            ['2 minutes, 5 seconds', 125],
            ['2 minutes, 1 second', 121],
            ['2 minutes', 121, false],
            ['3 minutes', 179, false],
            ['1 hour', 3600],
            ['1 day', 86400],
            ['1 week', 604800],
        ];
    }

    /**
     * @return array
     */
    public function toDateTimeDataProvider(): array
    {
        return [
            'timestamp' => [new DateTime('@1625575906'), 1625575906],
            'now' => [
                function() {
                    return new DateTime();
                },
                'now',
            ],
            'no-params' => [false, ['date' => '', 'time' => '']],
            'invalid-separator' => [false, '2018/08/09 20:00:00'],
            'invalid-separator-2' => [false, '2018.08.09 20:00:00'],
            'null-type' => [false, null],
            'empty-string' => [false, ''],
            'empty-array' => [false, []],
            'year' => [
                function() {
                    return new DateTime('2021-01-01 00:00:00', new DateTimeZone('UTC'));
                },
                '2021',
            ],
            'datetime-with-timezone' => [
                function() {
                    return new DateTime('2021-09-01T12:00', new DateTimeZone('Europe/Berlin'));
                },
                ['datetime' => '2021-09-01T12:00', 'timezone' => 'Europe/Berlin'],
            ],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function simpleDateTimeFormatsDataProvider(): array
    {
        return [
            'mysql' => ['2018-08-08 20:00:00'],
            'array' => [['date' => '08-09-2018', 'time' => '08:00 PM']],
            'w3c-format' => ['2018-08-09T20:00:00'],
            'dtobject' => [new DateTime('2018-08-09', new DateTimeZone('UTC'))],
        ];
    }

    /**
     * @return array
     */
    public function isInvalidIntervalStringDataProvider(): array
    {
        return [
            [true, '1 day'],
            [true, '1 hour'],
            [true, '1 hour + 1 day'],
            [true, '1 second'],
            [true, '1 year'],
            [true, '1 month'],
            [true, '1 minutes'],

            [false, ''],
            [false, 'random string'],

        ];
    }

    /**
     * @return array
     */
    public function timeZoneOffsetDataDataProvider(): array
    {
        return [
            ['+00:00', 'UTC'],
            ['+00:00', 'GMT'],
            [['-05:00', '-04:00'], 'America/New_York'],
            ['+09:00', '+09:00'],
        ];
    }

    /**
     * @return array
     */
    public function formatsWithTimezoneDataProvider(): array
    {
        $dt = function() {
            $dt = new DateTime('2018-08-09 20:00:00', new DateTimeZone('Asia/Tokyo'));
            $dt->setTimezone(new DateTimeZone(Craft::$app->getTimeZone()));

            return $dt;
        };

        return [
            'array-format' => [
                ['date' => '08-09-2018', 'time' => '08:00 PM', 'timezone' => 'Asia/Tokyo'],
                $dt,
            ],
            'w3c-format' => [
                '2018-08-09T20:00:00+09:00',
                $dt,
            ],
        ];
    }

    /**
     * @return array
     */
    public function toDateTimeWithTzFormatsDataProvider(): array
    {
        $basicDateTimeCreator = function($timezone) {
            $tz = new DateTimezone($timezone);
            return new DateTime('2018-08-09 20:00:00', $tz);
        };

        return [
            'mysql-format' => [
                '2018-08-09 20:00:00',
                $basicDateTimeCreator('UTC'),
                new DateTimeZone('UTC'),
            ],
            'array-format' => [
                ['date' => '08-09-2018', 'time' => '08:00 PM', 'timezone' => 'Asia/Tokyo'],
                $basicDateTimeCreator('Asia/Tokyo'),
                new DateTimeZone('Asia/Tokyo'),
            ],
            'w3c-format' => [
                '2018-08-09T20:00:00+09:00',
                $basicDateTimeCreator('+09:00'),
                new DateTimeZone('+09:00'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function toDateTimeFormatsDataProvider(): array
    {
        // Because we dont have access to Craft::$app here we smuggle this in via callback and call it in the test function. Which does have access to Craft::$app.
        $dt = function($dateParam = '2018-08-09 20:00:00') {
            $systemTimezone = new DateTimezone(Craft::$app->getTimeZone());
            $utcTz = new DateTimeZone('UTC');

            // Crafts toDateTime sets the input time as utc. Then converts to system tz unless overridden by variables $assumeSystemTimeZone and $setToSystemTimeZone.
            $dt = new DateTime($dateParam, $utcTz);
            $dt->setTimezone($systemTimezone);
            return $dt;
        };

        return [
            'was-invalid-date-valid-time' => [['date' => '2018-08-09', 'time' => '08:00 PM'], $dt],
            'was-invalid-date-format' => [['date' => '2018-08-09'], $dt, '2018-08-09 00:00:00'],

            'basic-mysql-format' => [
                '2018-08-09 20:00:00',
                $dt,
            ],
            'array-diff-separator' => [
                ['date' => '08/09/2018', 'time' => '08:00 PM'],
                $dt,
            ],
            'array-diff-separator-2' => [
                ['date' => '08.09.2018', 'time' => '08:00 PM'],
                $dt,
            ],
            'array-format' => [
                ['date' => '08-09-2018', 'time' => '08:00 PM'],
                $dt,
            ],
            'w3c-format' => [
                '2018-08-09T20:00:00',
                $dt,
            ],
            'unix-timestamp' => [
                '1533844800',
                $dt,
            ],
        ];
    }

    /**
     * @return array
     */
    public function normalizeTimeZoneDataProvider(): array
    {
        return [
            ['America/New_York', 'EST'],
            ['Europe/Berlin', 'CEST'],
            ['+09:00', '+0900'],
            ['-02:00', '-02:00'],
            ['UTC', 'UTC'],
            ['UTC', 'GMT'],
            ['Europe/Amsterdam', 'Europe/Amsterdam'],
            [false, 'NotATz'],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function isIsIso8601DataProvider(): array
    {
        return [
            [true, '2018-09-30T13:41:06+00:00'],
            [false, 'YYYY-MM-DDTHH:MM:SS+HH:MM'],
            [false, '2008-09-15'],
            [false, 'I am not a string'],
            [false, new DateTime('2018-09-21')],
            [false, false],
            [false, null],
        ];
    }

    /**
     * @return array
     */
    public function humanIntervalFromDurationDataProvider(): array
    {
        return [
            ['1 day', 'P1D'],
            ['1 year', 'P1Y'],
            ['1 month', 'P1M'],
            ['1 hour', 'PT1H'],
            ['1 second', 'PT1S'],
            ['2 months, 1 day, and 1 hour', 'P2M1DT1H'],
            ['1 hour and 1 minute', 'PT1H1M25S', false],
            ['1 hour and 2 minutes', 'PT1H1M55S', false],
            ['less than a minute', 'PT1S', false],
        ];
    }

    /**
     * @return array
     */
    public function secondsToIntervalDataProvider(): array
    {
        return [
            [10, 10000, 10],
            [0, 0000, 0],
            [928172, 928172000, 928172],

        ];
    }

    /**
     * @return array
     */
    public function intervalToSecondsDataProvider(): array
    {
        return [
            [86400, 'P1D'],
            [90000, 'P1DT1H'],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function toIso8601DataProvider(): array
    {
        $amsterdamTime = new DateTime('2018-08-08 20:00:00', new DateTimeZone('Europe/Amsterdam'));
        $tokyoTime = new DateTime('2018-08-08 20:00:00', new DateTimeZone('Asia/Tokyo'));

        return [
            ['2018-08-08T20:00:00+09:00', $tokyoTime],
            ['2018-08-08T20:00:00+02:00', $amsterdamTime],
            'invalid-format-returns-false' => [false, ['date' => '']],
        ];
    }

    /**
     * @return array
     */
    public function timeZoneAbbreviationDataProvider(): array
    {
        return [
            ['GMT', 'Etc/GMT+0'],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function isValidTimeStampDataProvider(): array
    {
        $amsterdamTime = new DateTime('2018-12-30 20:00:00', new DateTimeZone('Europe/Amsterdam'));
        $tokyoTime = new DateTime('2018-12-30 20:00:00', new DateTimeZone('Asia/Tokyo'));

        return [
            [true, $amsterdamTime->getTimestamp()],
            [true, $tokyoTime->getTimestamp()],
            [true, '1539520249'],
            [true, 0000000000],
            [false, '2018-10-14T21:30:49+09:00'],
            [false, true],
            [false, 'string'],
            [false, null],
            [false, false],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        Craft::$app->setTimeZone('America/Los_Angeles');
        $this->systemTimezone = new DateTimeZone(Craft::$app->getTimeZone());
        $this->utcTimezone = new DateTimeZone('UTC');
        $this->asiaTokyoTimezone = new DateTimeZone('Asia/Tokyo');
    }
}
