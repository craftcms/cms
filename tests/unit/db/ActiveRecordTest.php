<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\db;


use Codeception\Lib\Framework;
use Codeception\Test\Unit;
use craft\config\DbConfig;
use craft\db\ActiveRecord;
use craft\db\Connection;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\records\Session;
use craft\test\TestSetup;

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
     * @var \UnitTester
     */
    public $tester;
    public function testIsCraftAr()
    {

        $this->assertInstanceOf(ActiveRecord::class, new Session());
    }

    public function testDateCreated()
    {
        $sesh = $this->ensureSesh();

        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->assertSame($sesh->dateCreated, $date->format('Y-m-d H:i:s'));
    }

    public function testDateUpdated()
    {
        $sesh = $this->ensureSesh();

        // Ensure that there is a diff in dates....
        sleep(5);

        $dateTimeZone = new \DateTimeZone('UTC');
        $date = new \DateTime('now', $dateTimeZone);
        $oldDate  = new \DateTime($sesh->dateUpdated, $dateTimeZone);

        // TODO: can $this->greaterThan be used? Might need more research....
        $this->assertGreaterThan($oldDate, $date);

        // Save it again. Ensure dateUpdated is now this.
        $sesh->save();

        $this->assertSame($sesh->dateUpdated, $date->format('Y-m-d H:i:s'));
    }

    public function testUuid()
    {
        $sesh = $this->ensureSesh();

        $this->assertTrue(StringHelper::isUUID($sesh->uid));
    }

    public function testOverrides()
    {

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