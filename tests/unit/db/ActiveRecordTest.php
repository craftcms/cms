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
use craft\test\TestCase;
use DateTime;
use DateTimeZone;
use Exception;
use UnitTester;

/**
 * Unit tests for the ActiveRecord class Craft implements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ActiveRecordTest extends TestCase
{
    /**
     * @var UnitTester
     */
    public UnitTester $tester;

    /**
     * Note this test is just here to verify that these are indeed craft\db\ActiveRecord classes.
     */
    public function testIsCraftAr(): void
    {
        self::assertInstanceOf(ActiveRecord::class, new Volume());
        self::assertInstanceOf(ActiveRecord::class, new Session());
    }

    /**
     * @throws Exception
     */
    public function testDateCreated(): void
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));
        $session = $this->ensureSession();

        $this->tester->assertEqualDates($this, $session->dateCreated, $date->format('Y-m-d H:i:s'), 2);

        $session->delete();
    }

    /**
     * @throws Exception
     */
    public function testDateUpdated(): void
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
    public function testUuid(): void
    {
        $session = $this->ensureSession();

        self::assertTrue(StringHelper::isUUID($session->uid));

        $session->delete();
    }

    /**
     *
     */
    public function testUUIDThatIsntValid(): void
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
    public function testNoUUid(): void
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
