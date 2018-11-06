<?php

namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\Db;
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
        if (!\Craft::$app->getDb()->getIsPgsql()) {
            $this->markTestSkipped();
        }
    }

    /**
     * @dataProvider sqlTypesData
     */
    public function testTypeSupport($type, $supported)
    {
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

    /**
     * @dataProvider textualStorageData
     * @param $result
     * @param $input\
     */
    public function testGetTextualColumnStorageCapacity($result, $input)
    {
        $capacity = Db::getTextualColumnStorageCapacity($input);
        $this->assertSame($result, $capacity);
    }
    public function textualStorageData()
    {
        return [
            [null, \craft\db\pgsql\Schema::TYPE_TEXT],
        ];
    }

    /**
     * @dataProvider parseParamData
     */
    public function testParseParamGeneral($result, $collumn, $value, $defaultOperator = '=', $caseInsensitive = false)
    {
        $this->assertSame($result, Db::parseParam($collumn, $value, $defaultOperator, $caseInsensitive));
    }

    public function parseParamData()
    {
        return [
            'multi-:empty:-param' => [
                [
                    'or',
                    [ 'not', ['content_table' => null], ],
                    ['!=', 'content_table', 'field_2']
                ],
                'content_table', ':empty:, field_2', '!='
            ],
        ];
    }

    /**
     * @dataProvider getTextualCollumnType
     */
    public function testGetTextualCollumnTypeByContentLength($result, $input)
    {
        $textualCapacity = Db::getTextualColumnStorageCapacity($input);
        $this->assertSame($result, $textualCapacity);
    }

    public function getTextualCollumnType()
    {
        return [
            ['text', 254],
            ['text', 65534],
            ['text', 16777214],
            ['text', 4294967294],
            ['text', false],
            ['text', null],
        ];
    }

}