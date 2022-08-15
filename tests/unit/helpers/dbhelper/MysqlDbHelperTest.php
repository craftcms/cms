<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers\dbhelper;

use Codeception\Test\Unit;
use Craft;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\helpers\Db;
use craft\test\TestCase;
use yii\base\Exception;
use yii\base\NotSupportedException;

/**
 * Unit tests for the DB Helper class where its output may need to be mysql specific. Will be skipped if db isn't mysql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class MysqlDbHelperTest extends TestCase
{
    /**
     * @dataProvider sqlTypesDataProvider
     * @param string $type
     * @param bool $supported
     * @throws NotSupportedException
     */
    public function testTypeSupport(string $type, bool $supported): void
    {
        $isSupported = Db::isTypeSupported($type);
        self::assertSame($supported, Db::isTypeSupported($type));
        self::assertIsBool($isSupported);
    }

    /**
     * @dataProvider getTextualColumnStorageCapacityDataProvider
     * @param int|null|false $expected
     * @param string $columnType
     */
    public function testGetTextualColumnStorageCapacity(int|null|false $expected, string $columnType): void
    {
        self::assertSame($expected, Db::getTextualColumnStorageCapacity($columnType));
    }

    /**
     * @return array
     */
    public function getTextualColumnStorageCapacityDataProvider(): array
    {
        return [
            [null, MysqlSchema::TYPE_ENUM],
        ];
    }

    /**
     * @dataProvider parseParamDataProvider
     * @param mixed $expected
     * @param string $column
     * @param string|int|array $value
     * @param string $defaultOperator
     * @param bool $caseInsensitive
     */
    public function testParseParam(mixed $expected, string $column, mixed $value, string $defaultOperator = '=', bool $caseInsensitive = false): void
    {
        self::assertSame($expected, Db::parseParam($column, $value, $defaultOperator, $caseInsensitive));
    }

    /**
     * @dataProvider getTextualColumnTypeByContentLengthDataProvider
     * @param string $expected
     * @param int $contentLength
     * @throws Exception
     */
    public function testGetTextualColumnTypeByContentLength(string $expected, int $contentLength): void
    {
        self::assertSame($expected, Db::getTextualColumnTypeByContentLength($contentLength));
    }

    /**
     * @return array
     */
    public function getTextualColumnTypeByContentLengthDataProvider(): array
    {
        return [
            ['string', 254],
            ['text', 65534],
            ['mediumtext', 16777214],
            ['longtext', 4294967294],
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
                            ['content_table' => ''],
                        ],
                    ],
                    [
                        '!=',
                        'content_table',
                        'field_2',
                    ],
                ],
                'content_table', ':empty:, field_2', '!=',
            ],
            [
                ['or', ['foo' => null], ['foo' => '']], 'foo', ':empty:',
            ],
            [
                ['or', ['foo' => null], ['foo' => '']], 'foo', ':EMPTY:',
            ],
            [
                ['not', ['or', ['foo' => null], ['foo' => '']]], 'foo', ':notempty:',
            ],
            [
                ['not', ['or', ['foo' => null], ['foo' => '']]], 'foo', ':NOTEMPTY:',
            ],
            [
                ['not', ['or', ['foo' => null], ['foo' => '']]], 'foo', 'not :empty:',
            ],
            [
                ['not', ['or', ['foo' => null], ['foo' => '']]], 'foo', 'NOT :EMPTY:',
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

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        if (!Craft::$app->getDb()->getIsMysql()) {
            $this->markTestSkipped();
        }
    }
}
