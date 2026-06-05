<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\DateTimeHelper;
use craft\test\TestCase;
use DateInterval;
use DateTime;
use DateTimeZone;
use UnitTester;

class DateTimeHelperTest extends TestCase
{
    protected UnitTester $tester;

    public function test_legacy_and_support_helpers_share_paused_time(): void
    {
        $utc = new DateTimeZone('UTC');
        $now = new DateTime('2024-04-06 10:43:12', $utc);

        DateTimeHelper::pause($now);

        self::assertEquals($now, DateTimeHelper::currentUTCDateTime());
        self::assertSame($now->getTimestamp(), DateTimeHelper::currentTimeStamp());

        DateTimeHelper::resume();
    }

    public function test_deprecated_legacy_only_methods_still_exist(): void
    {
        $utc = new DateTimeZone('UTC');
        $now = new DateTime('2024-04-06 10:43:12', $utc);
        $interval = new DateInterval('PT90S');

        DateTimeHelper::pause($now);

        self::assertSame(60, DateTimeHelper::SECONDS_MINUTE);
        self::assertSame(3600, DateTimeHelper::SECONDS_HOUR);
        self::assertSame(86400, DateTimeHelper::SECONDS_DAY);
        self::assertSame(2629740, DateTimeHelper::SECONDS_MONTH);
        self::assertSame(31556874, DateTimeHelper::SECONDS_YEAR);
        self::assertSame('2024-04-06 00:00:00', DateTimeHelper::today($utc)->format('Y-m-d H:i:s'));
        self::assertSame('2024-04-07 00:00:00', DateTimeHelper::tomorrow($utc)->format('Y-m-d H:i:s'));
        self::assertSame('2024-04-05 00:00:00', DateTimeHelper::yesterday($utc)->format('Y-m-d H:i:s'));
        self::assertSame('2024-04-01 00:00:00', DateTimeHelper::thisMonth($utc)->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-01 00:00:00', DateTimeHelper::thisYear($utc)->format('Y-m-d H:i:s'));
        self::assertTrue(DateTimeHelper::isToday($now));
        self::assertSame(90, DateTimeHelper::intervalToSeconds($interval));
        self::assertSame(90, DateTimeHelper::relativeTimeToSeconds(90, 'seconds'));
        self::assertSame('+7 days', DateTimeHelper::relativeTimeStatement(1, 'week'));
        self::assertSame('PT90S', DateTimeHelper::secondsToInterval(90)->format('PT%SS'));
        self::assertSame('UTC', DateTimeHelper::timeZoneAbbreviation('UTC'));
        self::assertSame('+00:00', DateTimeHelper::timeZoneOffset('UTC'));
        self::assertSame('1 minute and 30 seconds', DateTimeHelper::secondsToHumanTimeDuration(90));
        self::assertTrue(DateTimeHelper::isValidIntervalString('1 day'));
        self::assertSame('90 seconds', DateTimeHelper::humanDurationFromInterval($interval));

        DateTimeHelper::resume();
    }
}
