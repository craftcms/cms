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

    public function testYesterday()
    {
        $dateTimeOjbect = new \DateTime('now');

        // Is today yesterday?
        $this->assertFalse(DateTimeHelper::isYesterday($dateTimeOjbect->format('Y-m-d')));

        // Is yesterday yesterda
        $dateTimeOjbect->modify('-1 day');
        $this->assertTrue(DateTimeHelper::isYesterday($dateTimeOjbect->format('Y-m-d')));

        // Is 2 days ago yesterday
        $dateTimeOjbect->modify('-1 day');
        $this->assertTrue(DateTimeHelper::isYesterday($dateTimeOjbect->format('Y-m-d')));

        $this->assertFalse(DateTimeHelper::isYesterday(''));

    }

}
