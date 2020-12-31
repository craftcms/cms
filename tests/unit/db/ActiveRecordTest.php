<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

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
 * Unit tests for the ActiveRecord class Craft implements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
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
        self::assertInstanceOf(ActiveRecord::class, new Volume());
        self::assertInstanceOf(ActiveRecord::class, new Session());
    }

    /**
     * @throws Exception
     */
    public function testDateCreated()
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));
        $session = $this->ensureSession();

        $this->tester->assertEqualDates($this, $session->dateCreated, $date->format('Y-m-d H:i:s'), 2);

        $session->delete();
    }

    /**
     * @throws Exception
     */
    public function testDateUpdated()
    {
        $session = $this->ensureSession();

        // Ensure that there is a diff in dates....
        sleep(5);

        $dateTimeZone = new DateTimeZone('UTC');
        $oldDate = new DateTime($session->dateUpdated, $dateTimeZone);

        // Save it again. Ensure dateUpdated is the same, as nothing has changed.
        $session->save();
        $this->tester->assertEqualDates($this, $session->dateUpdated, $oldDate->format('Y-m-d H:i:s'), 1);

        // Save it again with a new value. Ensure dateUpdated is now current.
        $date = new DateTime('now', $dateTimeZone);
        self::assertGreaterThan($oldDate, $date);

        $session->token = 'test2';
        $session->save();
        $this->tester->assertEqualDates($this, $session->dateUpdated, $date->format('Y-m-d H:i:s'), 1);

        $session->delete();
    }

    /**
     *
     */
    public function testUuid()
    {
        $session = $this->ensureSession();

        self::assertTrue(StringHelper::isUUID($session->uid));

        $session->delete();
    }

    /**
     * @dataProvider prepValForDbDataProvider
     *
     * @param string $expected
     * @param mixed $input
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function testPrepValForDb(string $expected, $input)
    {
        $vol = new Volume();
        $vol->name = 'NaN';
        $vol->handle = 'NaN';
        $vol->name = 'nan';
        $vol->type = Local::class;
        $vol->settings = $input;

        $save = $vol->save();

        self::assertTrue($save);
        self::assertSame($expected, $vol->settings);

        $vol->delete();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function prepValForDbDataProvider(): array
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $expectedDateTime = new DateTime('2018-06-06 18:00:00');
        $expectedDateTime->setTimezone(new DateTimeZone('UTC'));

        $dateTime = new DateTime('2018-06-06 18:00:00');

        return [
            [$expectedDateTime->format('Y-m-d H:i:s'), $dateTime],
            ['{"name":"name"}', $jsonableClass],
            ['{"JsonArray":"SomeArray"}', $jsonableArray],
            ['Serialized data', $serializable],
            ['', ''],
        ];
    }

    /**
     *
     */
    public function testUUIDThatIsntValid()
    {
        $session = new Session();
        $session->userId = 1;
        $session->token = 'test';
        $session->uid = '00000000|0000|0000|0000|000000000000';
        $save = $session->save();

        self::assertTrue($save);
        self::assertSame('00000000|0000|0000|0000|000000000000', $session->uid);

        $session->delete();
    }

    /**
     *
     */
    public function testNoUUid()
    {
        $session = new Session();
        $session->userId = 1;
        $session->token = 'test';
        $save = $session->save();

        self::assertTrue($save);
        self::assertTrue(StringHelper::isUUID($session->uid));

        $session->delete();
    }

    /**
     * @return Session
     */
    public function ensureSession(): Session
    {
        $session = new Session();
        $session->userId = 1;
        $session->token = 'test' . StringHelper::randomString();
        $save = $session->save();

        self::assertTrue($save);
        return $session;
    }
}
