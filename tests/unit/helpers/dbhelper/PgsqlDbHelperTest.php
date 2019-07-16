<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\db\pgsql\Schema;
use craft\helpers\Db;
use UnitTester;
use yii\base\Exception;
use yii\base\NotSupportedException;

/**
 * Unit tests for the DB Helper class where its output may need to be pgsql specific. Will be skipped if db isn't pgsql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PgsqlDbHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider sqlTypesDataProvider
     *
     * @param $type
     * @param $supported
     * @throws NotSupportedException
     */
    public function testTypeSupport($type, $supported)
    {
        $isSupported = Db::isTypeSupported($type);
        $this->assertSame($supported, Db::isTypeSupported($type));
        $this->assertIsBool($isSupported);
    }

    /**
     * @dataProvider textualStorageDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testGetTextualColumnStorageCapacity($result, $input)
    {
        $capacity = Db::getTextualColumnStorageCapacity($input);
        $this->assertSame($result, $capacity);
    }

    /**
     * @dataProvider parseParamDataProvider
     *
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

    /**
     * @dataProvider getTextualColumnTypeDataProvider
     *
     * @param $result
     * @param $input
     * @throws Exception
     */
    public function testGetTextualColumnTypeByContentLength($result, $input)
    {
        $textualCapacity = Db::getTextualColumnTypeByContentLength((int)$input);
        $this->assertSame($result, $textualCapacity);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function parseParamDataProvider(): array
    {
        return [
            'multi-:empty:-param' => [
                [
                    'or',
                    ['not', ['content_table' => null],],
                    ['!=', 'content_table', 'field_2']
                ],
                'content_table', ':empty:, field_2', '!='
            ],
        ];
    }

    /**
     * @return array
     */
    public function getTextualColumnTypeDataProvider(): array
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

    /**
     * @return array
     */
    public function sqlTypesDataProvider(): array
    {
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

        return $returnArray;
    }

    /**
     * @return array
     */
    public function textualStorageDataProvider(): array
    {
        return [
            [null, Schema::TYPE_TEXT],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        if (!Craft::$app->getDb()->getIsPgsql()) {
            $this->markTestSkipped();
        }
    }

}
