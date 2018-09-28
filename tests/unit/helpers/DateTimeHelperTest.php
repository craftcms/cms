<?php
namespace app\helpers;

use craft\helpers\DateTimeHelper;

class DateTimeHelperTest extends \Codeception\TestCase\Test
{
    public function testContsants()
    {
        $this->assertSame(DateTimeHelper::SECONDS_DAY, 86400);
        $this->assertSame(DateTimeHelper::SECONDS_HOUR, 3600);
        $this->assertSame(DateTimeHelper::SECONDS_MINUTE, 60);
        $this->assertSame(DateTimeHelper::SECONDS_MONTH, 2629740);
        $this->assertSame(DateTimeHelper::SECONDS_YEAR, 31556874);
    }

    public function testCurrentUtcDateTime()
    {
        $this->assertSame(DateTimeHelper::currentUTCDateTime(), (new DateTime(null, new DateTimeZone('UTC'))));
    }

    public function testCurrentUtcDateTimeStamp()
    {
        $this->assertSame(DateTimeHelper::currentTimeStamp(),
            (new DateTime(null, new DateTimeZone('UTC')))->getTimestamp()
        );
    }

    public function testSecondsToHumanTimeDuration()
    {
        $this->assertSame(DateTimeHelper::secondsToHumanTimeDuration(22), '22 seconds');
        $this->assertSame(DateTimeHelper::secondsToHumanTimeDuration(60), '1 minute');
        $this->assertSame(DateTimeHelper::secondsToHumanTimeDuration(120), '2 minutes');
        $this->assertSame(DateTimeHelper::secondsToHumanTimeDuration(125), '2 minutes, 5 seconds');
        $this->assertSame(DateTimeHelper::secondsToHumanTimeDuration(121), '2 minutes, 1 second');
    }

    public function testToDateTime()
    {
        $systemTimezone = new \DateTimeZone(ini_get('date.timezone'));
        $naturalDateTimeValue = new \DateTime('2018-08-08 20:00:00', $systemTimezone);
        $naturalDateTimeValue2 = new \DateTime('2018-08-08 20:00:00', $systemTimezone);

        // Does toDateTime make changes to variable if it is passed as a \DateTime object
        $this->assertSame($naturalDateTimeValue, DateTimeHelper::toDateTime($naturalDateTimeValue2));

        // Does the to date time equal the same as set with string.
        $this->assertSame(DateTimeHelper::toDateTime('2018-08-08 20:00:00'), $naturalDateTimeValue);

        // Does a null string, array or void return false
        $this->assertFalse(DateTimeHelper::toDateTime(''));
        $this->assertFalse(DateTimeHelper::toDateTime([]));
        $this->assertFalse(DateTimeHelper::toDateTime(null));
    }

    public function testNormalizeTimezone()
    {
        // TODO: is this right?
        $this->assertSame('Eastern standard time', DateTimeHelper::normalizeTimeZone('EST'));
        $this->assertSame('Europe/Paris', DateTimeHelper::normalizeTimeZone('CET'));
    }

    public function testIso86()
    {
        $dateTimeObject = new \DateTime('2018-09-21');
        // Too easy.
        $this->assertSame(DateTimeHelper::isIso8601(DateTimeHelper::toIso8601($dateTimeObject)), true);
        $this->assertSame(DateTimeHelper::isIso8601(DateTimeHelper::toIso8601('2018')), true);
        $this->assertSame(DateTimeHelper::isIso8601(DateTimeHelper::toIso8601('2018-09-09')), true);
    }

    public function testHumanIntervalDuration()
    {
        $dateTimeInterval = new \DateInterval('P1D');
        $this->assertTrue(DateTimeHelper::humanDurationFromInterval($dateTimeInterval), '1 Day');
    }

    public function testYesterday()
    {
        $this->testDateTimeIs('isYesterday', 'day');
    }

    public function testThisYearCheck()
    {
        $this->testDateTimeIs('isThisYear', 'year');
    }

    public function testThisWeek()
    {
        $this->testDateTimeIs('isThisWeek', 'week');
    }

    public function testIsInThePast()
    {
        $this->testDateTimeIs('isInThePast', 'hour');
    }

    public function testSecondsToInterval()
    {
        $dateTimeInterval = DateTimeHelper::secondsToInterval(10);
        $this->assertSame($dateTimeInterval->s, 10);
        $this->assertSame($dateTimeInterval->format('sdhm'), 10000);

        $dateTimeInterval = DateTimeHelper::secondsToInterval(0);
        $this->assertSame($dateTimeInterval->s, 0);
        $this->assertSame($dateTimeInterval->format('sdhm'), 0000);


        $dateTimeInterval = DateTimeHelper::secondsToInterval(92817295781282);
        $this->assertSame($dateTimeInterval->s, 92817295781282);
        $this->assertSame($dateTimeInterval->format('sdhm'), 92817295781282000);
    }


    public function intervalToSeconds()
    {
        $seconds = DateTimeHelper::intervalToSeconds(new \DateInterval('P1D'));
        $this->assertSame($seconds, 86400);

        $seconds = DateTimeHelper::intervalToSeconds(new \DateInterval('P1D2H'));
        $this->assertSame($seconds, 93600);
    }
    
    private function testEmptyDateTimeClass($function)
    {
        $this->assertFalse(DateTimeHelper::$function(null));
        $this->assertFalse(DateTimeHelper::$function(false));
        $this->assertFalse(DateTimeHelper::$function(''));
    }

    private function testDateTimeIs(string $function, $timeParam)
    {
        $this->testEmptyDateTimeClass($function);
        $dateTime = new \DateTime('now');

        $this->assertTrue(DateTimeHelper::$function($dateTime));

        $dateTime->modify('+2 '.$timeParam.'');
        $this->assertFalse(DateTimeHelper::$function($dateTime));

        $dateTime->modify('-4 '.$timeParam.'');
        $this->assertFalse(DateTimeHelper::$function($dateTime));
    }

}
