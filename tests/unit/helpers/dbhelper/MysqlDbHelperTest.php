<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\helpers\Db;
use UnitTester;
use yii\base\Exception;
use yii\base\NotSupportedException;

/**
 * Unit tests for the DB Helper class where its output may need to be mysql specific. Will be skipped if db isn't mysql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class MysqlDbHelperTest extends Unit
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
     * @return array
     */
    public function textualStorageDataProvider(): array
    {
        return [
            [null, MysqlSchema::TYPE_ENUM],
        ];
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
    public function getTextualColumnTypeDataProvider(): array
    {
        return [
            ['string', 254],
            ['text', 65534],
            ['mediumtext', 16777214],
            ['longtext', 4294967294],
            ['string', false],
            ['string', null],
        ];
    }

    /**
     * @return array
     */
    public function parseParamDataProvider(): array
    {
        return [
            'multi-:empty:-param' => [
                [
                    'or',
                    [
                        'not',
                        [
                            'or',
                            ['content_table' => null],
                            ['content_table' => '']
                        ],
                    ],
                    [
                        '!=',
                        'content_table',
                        'field_2'
                    ]
                ],
                'content_table', ':empty:, field_2', '!='
            ],
        ];
    }

    /**
     * @return array
     */
    public function sqlTypesDataProvider(): array
    {
        $mysqlSchema = new MysqlSchema();
        $pgsqlSchema = new PgsqlSchema();
        $returnArray = [];

        foreach ($mysqlSchema->typeMap as $key => $value) {
            $returnArray[] = [$key, true];
        }

        foreach ($pgsqlSchema->typeMap as $key => $value) {
            if (!isset($mysqlSchema->typeMap[$key])) {
                $returnArray[] = [$key, false];
            }
        }

        return $returnArray;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        if (!Craft::$app->getDb()->getIsMysql()) {
            $this->markTestSkipped();
        }
    }
}
