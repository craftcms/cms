<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\DateTimeHelper;
use craft\test\TestCase;
use CraftCms\Cms\Support\DateTimeHelper as SupportDateTimeHelper;
use DateInterval;
use DateTime;
use DateTimeZone;
use UnitTester;

class DateTimeHelperTest extends TestCase
{
    protected UnitTester $tester;

    public function testLegacyAndSupportHelpersSharePausedTime(): void
    {
        $utc = new DateTimeZone('UTC');
        $now = new DateTime('2024-04-06 10:43:12', $utc);

        DateTimeHelper::pause($now);

        self::assertEquals($now, SupportDateTimeHelper::now($utc));
        self::assertSame($now->getTimestamp(), SupportDateTimeHelper::currentTimeStamp());

        SupportDateTimeHelper::resume();
    }

    public function testDeprecatedLegacyOnlyMethodsStillExist(): void
    {
        $interval = new DateInterval('PT90S');

        self::assertSame('PT90S', DateTimeHelper::secondsToInterval(90)->format('PT%SS'));
        self::assertSame('UTC', DateTimeHelper::timeZoneAbbreviation('UTC'));
        self::assertSame('+00:00', DateTimeHelper::timeZoneOffset('UTC'));
        self::assertSame('1 minute and 30 seconds', DateTimeHelper::secondsToHumanTimeDuration(90));
        self::assertTrue(DateTimeHelper::isValidIntervalString('1 day'));
        self::assertSame('90 seconds', DateTimeHelper::humanDurationFromInterval($interval));
    }
}
