<?php
namespace craftunit\helpers;

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
        $this->assertSame(DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s'), (new \DateTime(null, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
    }

    public function testCurrentUtcDateTimeStamp()
    {
        $dateTime = new \DateTime(null, new \DateTimeZone('UTC'));
        $this->assertSame(
            DateTimeHelper::currentTimeStamp(),
            $dateTime->getTimestamp()
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
        $craftTimezone = \Craft::$app->getTimeZone();
        $systemTimezone = new \DateTimeZone($craftTimezone);
        $utcTimezone = new \DateTimeZone('UTC');

        $systemTime1 = new \DateTime('2018-08-08 20:00:00', $systemTimezone);
        $systemTime2 = new \DateTime('2018-08-08 20:00:00', $systemTimezone);

        $utcToSytemTime = new \DateTime('2018-08-08 20:00:00', $utcTimezone);
        $utcToSytemTime->setTimezone($systemTimezone);

        // Does toDateTime make changes to variable if it is passed as a \DateTime object
        $this->assertSame($systemTime1->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime($systemTime2)->format('Y-m-d H:i:s'));
        $this->assertSame($systemTime1->getTimezone()->getName(), DateTimeHelper::toDateTime($systemTime2)->getTimezone()->getName());


        $this->dateTimeTester('2018-08-08 20:00:00', '2018-08-08 20:00:00');

        // Does the date time helper not set to system timezone when being told to. Reverting back to utc
        $this->assertSame($utcTimezone->getName(), DateTimeHelper::toDateTime('2018-08-08 20:00:00', false, false)->getTimezone()->getName());

        // Does the system set the timezone if asked.
        $this->assertSame($systemTimezone->getName(), DateTimeHelper::toDateTime('2018-08-08 20:00:00', false, true)->getTimezone()->getName());

        // Does the dateTime create a format in utc and then set the tz to the system. If we dont overide setSystemTzToFalse.
        // Test that this happens explicitly by adding assume set sytem timezone to false and not defining it.
        $this->assertSame($utcToSytemTime->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime('2018-08-08 20:00:00', false)->format('Y-m-d H:i:s'));
        $this->assertSame($utcToSytemTime->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime('2018-08-08 20:00:00')->format('Y-m-d H:i:s'));

        $arrayFormat = [
            'date' => '08-08-2018',
            'time' => '08:00 PM',
        ];
        $toDtValue = DateTimeHelper::toDateTime($arrayFormat);
        $this->assertInstanceOf(\DateTime::class, $toDtValue);
        $this->assertSame($systemTimezone->getName(), $toDtValue->getTimezone()->getName());
        $this->assertSame($utcToSytemTime->format('Y-m-d H:i:s'), $toDtValue->format('Y-m-d H:i:s'));

        $this->dateTimeTester($arrayFormat, '2018-08-08 20:00:00');

        // TODO Create a bunch more with various input formats.

        // Does a null string, array or void or invalid
        $this->assertFalse(DateTimeHelper::toDateTime(['2018-08-08']));
        $this->assertFalse(DateTimeHelper::toDateTime(['date' => '2018-08-08']));
        $this->assertFalse(DateTimeHelper::toDateTime(['date' => '30-02-2018', 'time' => '08:00 PM']));
        $this->assertFalse(DateTimeHelper::toDateTime(['date' => '2018-08-08', 'time' => '08:00 PM']));
        $this->assertFalse(DateTimeHelper::toDateTime(['date' => '2018-08-08', 'time' => '8:00']));
        $this->assertFalse(DateTimeHelper::toDateTime(['date' => '2018-08-08', 'time' => '08:00 AM', 'timezone' => 'raaaa']));
        $this->assertFalse(DateTimeHelper::toDateTime(''));
        $this->assertFalse(DateTimeHelper::toDateTime([]));
        $this->assertFalse(DateTimeHelper::toDateTime(null));
    }

    public function dateTimeTester(
        $toDateTimeFormat,
        string $dateTimeCreatableFormat
    ) {
        $craftTimezone = \Craft::$app->getTimeZone();
        $systemTimezone = new \DateTimeZone($craftTimezone);
        $utcTimezone = new \DateTimeZone('UTC');

        $dateTimeWithSystemTz = new \DateTime($dateTimeCreatableFormat, $systemTimezone);

        // Does the system TZ get keepen when told to assume it is correct.
        $this->assertSame($dateTimeWithSystemTz->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime($toDateTimeFormat, true)->format('Y-m-d H:i:s'));

        // Does the system time get keepen when
        $this->assertSame($dateTimeWithSystemTz->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime($toDateTimeFormat, true, true)->format('Y-m-d H:i:s'));

        // Does the system set the timezone if asked and not assume the system tz
        $dateTimeWithSystemTzAdjusted = new \DateTime($dateTimeCreatableFormat, $utcTimezone);
        $dateTimeWithSystemTzAdjusted->setTimezone($systemTimezone);
        $this->assertSame($dateTimeWithSystemTzAdjusted->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime($toDateTimeFormat, false, true)->format('Y-m-d H:i:s'));
    }
    public function testNormalizeTimezone()
    {
        // TODO: is this right?
        $this->assertSame('America/New_York', DateTimeHelper::normalizeTimeZone('EST'));
        $this->assertSame('Europe/Berlin', DateTimeHelper::normalizeTimeZone('CET'));
    }

    public function testIso86()
    {
        $dateTimeObject = new \DateTime('2018-09-21');
        // Too easy.
        $this->assertTrue(DateTimeHelper::isIso8601(DateTimeHelper::toIso8601($dateTimeObject)));
        $this->assertTrue(DateTimeHelper::isIso8601(DateTimeHelper::toIso8601('2018')));
        $this->assertTrue(DateTimeHelper::isIso8601(DateTimeHelper::toIso8601('2018-09-09')));
        $this->assertTrue(DateTimeHelper::isIso8601('2018-09-30T13:41:06+00:00'));

        $this->assertFalse(DateTimeHelper::isIso8601('YYYY-MM-DDTHH:MM:SS+HH:MM'));
        $this->assertFalse(DateTimeHelper::isIso8601('2008-09-15'));
        $this->assertFalse(DateTimeHelper::isIso8601('2008-09-15T15:53:00'));
        $this->assertFalse(DateTimeHelper::isIso8601('Iam not a string'));
        $this->assertFalse(DateTimeHelper::isIso8601($dateTimeObject));
    }

    public function testHumanIntervalDuration()
    {
        $dateTimeInterval = new \DateInterval('P1D');
        $this->assertSame('1 day', DateTimeHelper::humanDurationFromInterval($dateTimeInterval));

        // TODO: Need more.
    }

    public function testYesterday()
    {
        $this->testDateTimeIs('isYesterday', 'days');
    }

    public function testThisYearCheck()
    {
        $this->testDateTimeIs('isThisYear', 'years');
    }

    public function testThisWeek()
    {
        $this->testDateTimeIs('isThisWeek', 'weeks');
    }

    public function testIsInThePast()
    {
        $this->testDateTimeIs('isInThePast', 'hours');
    }

    public function testSecondsToInterval()
    {
        $interval = DateTimeHelper::secondsToInterval(10);
        $this->assertSame(10, $interval->s);
        $this->assertSame(10000, (int)$interval->format('%s%d%h%m'));

        $interval = DateTimeHelper::secondsToInterval(0);
        $this->assertSame(0, $interval->s);
        $this->assertSame(0000, (int)$interval->format('%s%d%h%m'));


        $interval = DateTimeHelper::secondsToInterval(928172);
        $this->assertSame(928172, $interval->s);
        $this->assertSame(928172000, (int)$interval->format('%s%d%h%m'));
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
