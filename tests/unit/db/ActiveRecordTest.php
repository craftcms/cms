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
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    public $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * Note this test is just here to verify that these are indeed craft\db\ActiveRecord classes.
     */
    public function testIsCraftAr()
    {
        $this->assertInstanceOf(ActiveRecord::class, new Volume());
        $this->assertInstanceOf(ActiveRecord::class, new Session());
    }

    /**
     * @throws Exception
     */
    public function testDateCreated()
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));
        $session = $this->ensureSession();

        $this->tester->assertEqualDates($this, $session->dateCreated, $date->format('Y-m-d H:i:s'), 2);
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
        $this->assertSame($session->dateUpdated, $oldDate->format('Y-m-d H:i:s'));

        // Save it again with a new value. Ensure dateUpdated is now current.
        $date = new DateTime('now', $dateTimeZone);
        $this->assertGreaterThan($oldDate, $date);

        $session->token = 'test2';
        $session->save();
        $this->assertSame($session->dateUpdated, $date->format('Y-m-d H:i:s'));
    }

    /**
     *
     */
    public function testUuid()
    {
        $session = $this->ensureSession();

        $this->assertTrue(StringHelper::isUUID($session->uid));
    }

    /**
     * @dataProvider dataForDbPrepareDataProvider
     *
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

    /**
     * @return array
     * @throws Exception
     */
    public function dataForDbPrepareDataProvider(): array
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
            [false, false],
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

        $this->assertTrue($save);
        $this->assertSame('00000000|0000|0000|0000|000000000000', $session->uid);
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

        $this->assertTrue($save);
        $this->assertTrue(StringHelper::isUUID($session->uid));
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

        $this->assertTrue($save);
        return $session;
    }
}
