<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;

use Codeception\Test\Unit;
use craft\helpers\DateTimeHelper;
use yii\base\ErrorException;

/**
 * Unit tests for the DateTime Helper class.
 *
 * TODO: This suite must be more effecient with some global variables.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class DateTimeHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected $systemTimezone;
    protected $utcTimezone;
    protected $asiaTokyoTimezone;

    protected function _before()
    {
        $this->systemTimezone = new \DateTimeZone(\Craft::$app->getTimeZone());
        $this->utcTimezone = new \DateTimeZone('UTC');
        $this->asiaTokyoTimezone = new \DateTimeZone('Asia/Tokyo');
    }

    protected function _after()
    {
    }

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
        $this->assertSame(DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s'), (new \DateTime(null, $this->utcTimezone))->format('Y-m-d H:i:s'));
    }

    public function testCurrentUtcDateTimeStamp()
    {
        $dateTime = new \DateTime(null, $this->utcTimezone);
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

    /**
     * An empty array will return an ErrorException
     */
    public function testEmptyArrayReturnsException()
    {
        $this->tester->expectException(ErrorException::class, function (){
            return DateTimeHelper::toDateTime([]);
        });
    }


    /**
     * What we are testing here is that if we tell the DtHelper to not assume a timezone and set it to system.
     * That all formats are converted to the system timezone from the inputted system timezone. ie an array like this:
     *
     * ['date' => '2018-08-08', 'timezone' => 'Asia/Tokyo']
     *
     * toDateTime must start the DateTime from utc instead starting at Asia/Tokyo and then convert it to system.
     *
     * @dataProvider formatsWithTimezone
     *
     * @param           $format
     * @param \DateTime $expectedResult
     */
    public function testUtcIgnorance($format, \DateTime $expectedResult)
    {
        $toDateTime = DateTimeHelper::toDateTime($format);
        $systemTz = $this->systemTimezone->getName();

        $this->assertInstanceOf(\DateTime::class, $toDateTime);
        $this->assertSame($systemTz, $toDateTime->getTimezone()->getName());

        // Ensure the expected result is in the same timezone as the system.
        $this->assertSame($systemTz, $expectedResult->getTimezone()->getName());

        // Are they the same?
        $this->assertSame($expectedResult->format('Y-m-d H:i:s'), $toDateTime->format('Y-m-d H:i:s'));
    }

    public function formatsWithTimezone()
    {
        $dt = new \DateTime('2018-08-09 20:00:00', new \DateTimeZone('Asia/Tokyo'));
        $dt->setTimezone(new \DateTimeZone(\Craft::$app->getTimeZone()));

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
     * @dataProvider invalidToDateTimeFormatsData
     * @param $format
     */
    public function testToDateTimeInvalidFormats($format)
    {
        $this->assertFalse(DateTimeHelper::toDateTime($format));
    }

    public function invalidToDateTimeFormatsData()
    {
        return [
            'no-params' => [['date' => '', 'time' => '']],
            'invalid-date-format' => [['date' => '2018-08-08']],
            'invalid-date-valid-time' => [['date' => '2018-08-08', 'time' => '08:00 PM']],
            'null-type' => [null],
            'empty-string' => [''],
        ];
    }

    /**
     * @dataProvider simpleDateTimeFormats
     * @param $format
     */
    public function testUtcDefault($format)
    {
        $utc = new \DateTimeZone('UTC');
        $toDateTime = DateTimeHelper::toDateTime($format, false, false);
        $this->assertSame($this->utcTimezone->getName(), $toDateTime->getTimezone()->getName());
    }

    public function simpleDateTimeFormats()
    {
        return [
            'mysql' => ['2018-08-08 20:00:00'],
            'array' => [['date' => '08-09-2018', 'time' => '08:00 PM']],
            'w3c-format' => ['2018-08-09T20:00:00'],
            'dtobject' => [new \DateTime('2018-08-09', new \DateTimeZone('UTC'))]
        ];
    }


    /**
     *@dataProvider toDateTimeWithTzFormats
     * @param               $format
     * @param \DateTime      $expectedResult
     * @param \DateTimeZone $expectedTimezone
     */
    public function testToDateTimeRespectsTz($format, \DateTime $expectedResult, \DateTimeZone $expectedTimezone)
    {
        $toDateTime = DateTimeHelper::toDateTime($format, false, false);

        $this->assertInstanceOf(\DateTime::class, $toDateTime);
        $this->assertSame($expectedTimezone->getName(), $toDateTime->getTimezone()->getName());
        $this->assertSame($expectedTimezone->getName(), $expectedResult->getTimezone()->getName());
        $this->assertSame($expectedResult->format('Y-m-d H:i:s'), $toDateTime->format('Y-m-d H:i:s'));
    }

    public function toDateTimeWithTzFormats()
    {
        $basicDateTimeCreator = function ($timezone){
            $tz = new \DateTimezone($timezone);
            // Crafts toDateTime sets the format as utc.
            $dt = new \DateTime('2018-08-09 20:00:00', $tz);
            return $dt;
        };

        return [
            'mysql-format' => [
               '2018-08-09 20:00:00',
                $basicDateTimeCreator('UTC'),
                new \DateTimeZone('UTC')
            ],
            'array-format' => [
                ['date' => '08-09-2018', 'time' => '08:00 PM', 'timezone' => 'Asia/Tokyo'],
                $basicDateTimeCreator('Asia/Tokyo'),
                new \DateTimeZone('Asia/Tokyo')
            ],
            'w3c-format' => [
                '2018-08-09T20:00:00+09:00',
                $basicDateTimeCreator('+09:00'),
                new \DateTimeZone('+09:00')
            ],
        ];
    }

    /**
     * @dataProvider toDateTimeFormats
     * @param          $format
     * @param \Closure $expectedResult
     */
    public function testToDateTimeCreation($format, \Closure $expectedResult)
    {
        $toDateTime = DateTimeHelper::toDateTime($format);
        $this->assertSame($expectedResult()->format('Y-m-d H:i:s'), DateTimeHelper::toDateTime($format)->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(\DateTime::class, $toDateTime);
    }

    public function toDateTimeFormats()
    {
        $basicDateTimeCreator = function (){
            $systemTimezone = new \DateTimezone(\Craft::$app->getTimeZone());
            $utcTz = new \DateTimeZone('UTC');

            // Crafts toDateTime sets the format as utc. Then converts to system tz unless overridden by variables.
            $dt = new \DateTime('2018-08-09 20:00:00', $utcTz);
            $dt->setTimezone($systemTimezone);
            return $dt;
        };

        return [
            'basic-mysql-format' => [
                '2018-08-09 20:00:00',
                $basicDateTimeCreator,
            ],
            'array-format' => [
                ['date' => '08-09-2018', 'time' => '08:00 PM'],
                $basicDateTimeCreator,
            ],
            'w3c-format' => [
                '2018-08-09T20:00:00',
                $basicDateTimeCreator,
            ],
            'unix-timestamp' => [
                '1533844800',
                $basicDateTimeCreator,
            ],
        ];
    }

    public function testNormalizeTimezone()
    {
        $this->assertSame('America/New_York', DateTimeHelper::normalizeTimeZone('EST'));
        $this->assertSame('Europe/Berlin', DateTimeHelper::normalizeTimeZone('CET'));
        $this->assertSame('+09:00', DateTimeHelper::normalizeTimeZone('+0900'));
        $this->assertSame('-02:00', DateTimeHelper::normalizeTimeZone('-02:00'));
        $this->assertSame('UTC', DateTimeHelper::normalizeTimeZone('UTC'));
        $this->assertSame('UTC', DateTimeHelper::normalizeTimeZone('GMT'));
        $this->assertSame('Europe/Amsterdam', DateTimeHelper::normalizeTimeZone('Europe/Amsterdam'));

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
        $systemTz = new \DateTimeZone(\Craft::$app->getTimeZone());
        $dateTime = new \DateTime('now');

        $dateTime->modify('-1 days');
        $this->assertTrue(DateTimeHelper::isYesterday($dateTime));


        $dateTime->modify('-1 days');
        $this->assertFalse(DateTimeHelper::isYesterday($dateTime));

        $dateTime->modify('+2 days');
        $this->assertFalse(DateTimeHelper::isYesterday($dateTime));

        $dateTime = new \DateTime('yesterday', $systemTz);
        $this->assertTrue(DateTimeHelper::isYesterday($dateTime));
    }

    public function testThisYearCheck()
    {
        $dateTime = new \DateTime('now');
        $this->assertTrue(DateTimeHelper::isThisYear($dateTime));

        $dateTime->modify('-1 years');
        $this->assertFalse(DateTimeHelper::isThisYear($dateTime));

        $dateTime->modify('+2 years');
        $this->assertFalse(DateTimeHelper::isThisYear($dateTime));
    }

    public function testThisWeek()
    {
        $dateTime = new \DateTime('now');
        $this->assertTrue(DateTimeHelper::isThisWeek($dateTime));

        $dateTime->modify('-1 weeks');
        $this->assertFalse(DateTimeHelper::isThisWeek($dateTime));


        $dateTime->modify('+1 weeks');
        $this->assertTrue(DateTimeHelper::isThisWeek($dateTime));

        $dateTime->modify('+2 weeks');
        $this->assertFalse(DateTimeHelper::isYesterday($dateTime));
    }

    public function testIsInThePast()
    {
        $systemTz = new \DateTimeZone(\Craft::$app->getTimeZone());
        $dateTime = new \DateTime('now', $systemTz);
        $dateTime->modify('-5 seconds');
        $this->assertTrue(DateTimeHelper::isInThePast($dateTime));

        $dateTime->modify('-1 minutes');
        $this->assertTrue(DateTimeHelper::isInThePast($dateTime));

        $dateTime->modify('+2 minutes');
        $this->assertFalse(DateTimeHelper::isInThePast($dateTime));
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


    public function testIntervalToSeconds()
    {
        $seconds = DateTimeHelper::intervalToSeconds(new \DateInterval('P1D'));
        $this->assertSame($seconds, 86400);

        $seconds = DateTimeHelper::intervalToSeconds(new \DateInterval('P1DT1H'));
        $this->assertSame($seconds, 90000);
    }
}
