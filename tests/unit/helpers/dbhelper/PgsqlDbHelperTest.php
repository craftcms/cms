<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\db\pgsql\Schema;
use craft\helpers\Db;
use UnitTester;

/**
 * Unit tests for the DB Helper class where its output may need to be pgsql specific. Will be skipped if db isn't pgsql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class PgsqlDbHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;
    public function _before()
    {
        if (!Craft::$app->getDb()->getIsPgsql()) {
            $this->markTestSkipped();
        }
    }

    /**
     * @dataProvider sqlTypesData
     * @param $type
     * @param $supported
     */
    public function testTypeSupport($type, $supported)
    {
        $isSupported = Db::isTypeSupported($type);
        $this->assertSame($supported, Db::isTypeSupported($type));
        $this->assertIsBool($isSupported);
    }

    public function sqlTypesData(): array
    {
        // TODO: This is the best way to test it but is it worth 20mb and 3 seconds of time?
        $mysqlSchema = new \craft\db\mysql\Schema();
        $pgsqlSchema = new Schema();
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
     * @param $input
     */
    public function testGetTextualColumnStorageCapacity($result, $input)
    {
        $capacity = Db::getTextualColumnStorageCapacity($input);
        $this->assertSame($result, $capacity);
    }
    public function textualStorageData(): array
    {
        return [
            [null, Schema::TYPE_TEXT],
        ];
    }

    /**
     * @dataProvider parseParamData
     * @param $result
     * @param $column
     * @param $value
     * @param string $defaultOperator
     * @param bool $caseInsensitive
     */
    public function testParseParamGeneral($result, $column, $value, $defaultOperator = '=', $caseInsensitive = false)
    {
        $this->assertSame($result, Db::parseParam($column, $value, $defaultOperator, $caseInsensitive));
    }

    public function parseParamData(): array
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
     * @dataProvider getTextualColumnType
     * @param $result
     * @param $input
     */
    public function testGetTextualColumnTypeByContentLength($result, $input)
    {
        $textualCapacity = Db::getTextualColumnStorageCapacity($input);
        $this->assertSame($result, $textualCapacity);
    }

    public function getTextualColumnType(): array
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