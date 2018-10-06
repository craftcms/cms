<?php

namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\Db;
use yii\db\pgsql\Schema;

/**
 * Unit tests for the DB Helper class where its output may need to be pgsql specific. Will be skipped if db isnt pgsql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class PgsqlDbHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    public function _before()
    {
        if (getenv('TEST_DB_DRIVER') !== 'pgsql') {
            $this->markTestSkipped();
        }
    }

    /**
     * @dataProvider sqlTypesData
     */
    public function testTypeSupport($type, $supported)
    {
        $this->markTestSkipped();
        $isSupported = Db::isTypeSupported($type);
        $this->assertSame($supported, Db::isTypeSupported($type));
        $this->assertInternalType('boolean', $isSupported);
    }

    public function sqlTypesData()
    {
        // TODO: This is the best way to test it but is it worth 20mb and 3 seconds of time?
        $mysqlSchema = new \craft\db\mysql\Schema();
        $pgsqlSchema = new \craft\db\pgsql\Schema();
        $returnArray = [];

        foreach ($pgsqlSchema->typeMap as $key => $value) {
            $returnArray[] = [$key, true];
        }
        foreach ($mysqlSchema->typeMap as $key => $value) {
            if (!isset($pgsqlSchema->typeMap[$key])) {
                $returnArray[] = [$key, false];
            }
        }

        return  $returnArray;
    }

    protected function _after()
    {
    }
}