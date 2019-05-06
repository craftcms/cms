<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\db;


use Codeception\Test\Unit;
use craft\db\ActiveRecord;
use craft\helpers\StringHelper;
use craft\records\Session;
use craft\records\Volume;
use craft\test\mockclasses\serializable\Serializable;
use craft\volumes\Local;
use DateTime;
use DateTimeZone;
use Exception;
use stdClass;
use UnitTester;

/**
 * Unit tests for the ActiveRecord class craft cms implements
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ActiveRecordTest extends Unit
{

    /**
     * @var UnitTester
     */
    public $tester;

    /**
     * Note this test is just here to verify that these are indeed craft\db\ActiveRecord classes.
     */
    public function testIsCraftAr()
    {
        $this->assertInstanceOf(ActiveRecord::class, new Volume());
        $this->assertInstanceOf(ActiveRecord::class, new Session());
    }

    public function testDateCreated()
    {
        $sesh = $this->ensureSesh();

        $date = new DateTime('now', new DateTimeZone('UTC'));

        $this->assertSame($sesh->dateCreated, $date->format('Y-m-d H:i:s'));
    }

    public function testDateUpdated()
    {
        $sesh = $this->ensureSesh();

        // Ensure that there is a diff in dates....
        sleep(5);

        $dateTimeZone = new DateTimeZone('UTC');
        $date = new DateTime('now', $dateTimeZone);
        $oldDate  = new DateTime($sesh->dateUpdated, $dateTimeZone);

        // TODO: can $this->greaterThan be used? Might need more research....
        $this->assertGreaterThan($oldDate, $date);

        // Save it again. Ensure dateUpdated is now current.
        $sesh->save();

        $this->assertSame($sesh->dateUpdated, $date->format('Y-m-d H:i:s'));
    }

    public function testUuid()
    {
        $sesh = $this->ensureSesh();

        $this->assertTrue(StringHelper::isUUID($sesh->uid));
    }

    /**
     * @dataProvider dataForDbPrepare
     * @param $result
     * @param $input
     */
    public function testPrepValForDb($result, $input)
    {
        $vol = new Volume();
        $vol->name = 'NaN';
        $vol->handle = 'NaN';
        $vol->name = 'nan';
        $vol->type = Local::class;
        $vol->settings = $input;

        $save = $vol->save();

        $this->assertTrue($save);
        $this->assertSame($result, $vol->settings);
    }

    public function dataForDbPrepare(): array
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $excpectedDateTime = new DateTime('2018-06-06 18:00:00');
        $excpectedDateTime->setTimezone(new DateTimeZone('UTC'));

        $dateTime = new DateTime('2018-06-06 18:00:00');

        return [
            [$excpectedDateTime->format('Y-m-d H:i:s'), $dateTime],
            ['{"name":"name"}', $jsonableClass],
            ['{"JsonArray":"SomeArray"}', $jsonableArray],
            ['Serialized data', $serializable],
            [false, false],
        ];
    }
    /**
     * Test that values cannot be overrriden
     *
     * @throws Exception
     */
    public function testOverrides()
    {
        $utcTz = new DateTimeZone('UTC');
        $oneDayAgo = new DateTime('-1 day', $utcTz);
        $now =  new DateTime('now', $utcTz);

        $uuid = StringHelper::UUID();

        $sesh = new Session();
        $sesh->userId = 1;
        $sesh->token = 'test';
        $sesh->dateCreated = $oneDayAgo;
        $sesh->dateUpdated = $oneDayAgo;
        $sesh->uid = $uuid;
        $save = $sesh->save();

        $this->assertTrue($save);

        $this->assertSame($now->format('Y-m-d H:i:s'), $sesh->dateCreated);
        $this->assertSame($now->format('Y-m-d H:i:s'), $sesh->dateUpdated);
        $this->assertSame($uuid, $sesh->uid);
    }

    public function testUUIDThatIsntValid()
    {
        $sesh = new Session();
        $sesh->userId = 1;
        $sesh->token = 'test';
        $sesh->uid = '00000000|0000|0000|0000|000000000000';
        $save = $sesh->save();

        $this->assertTrue($save);
        $this->assertSame('00000000|0000|0000|0000|000000000000', $sesh->uid);
    }

    public function testNoUUid()
    {
        $sesh = new Session();
        $sesh->userId = 1;
        $sesh->token = 'test';
        $save = $sesh->save();

        $this->assertTrue($save);
        $this->assertTrue(StringHelper::isUUID($sesh->uid));
    }

    public function ensureSesh() : Session
    {
        $sesh = new Session();
        $sesh->userId = 1;
        $sesh->token = 'test';
        $save = $sesh->save();

        $this->assertTrue($save);

        return $sesh;
    }
}
