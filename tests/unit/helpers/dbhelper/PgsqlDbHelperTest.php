<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers\dbhelper;

use Codeception\Test\Unit;
use Craft;
use craft\db\pgsql\Schema;
use craft\helpers\Db;
use craft\test\TestCase;
use yii\base\Exception;
use yii\base\NotSupportedException;

/**
 * Unit tests for the DB Helper class where its output may need to be pgsql specific. Will be skipped if db isn't pgsql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PgsqlDbHelperTest extends TestCase
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
     * @dataProvider parseParamDataProvider
     * @param string|array $expected
     * @param string $column
     * @param mixed $value
     * @param string $defaultOperator
     * @param bool $caseInsensitive
     */
    public function testParseParam(string|array $expected, string $column, mixed $value, string $defaultOperator = '=', bool $caseInsensitive = false): void
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
    public function parseParamDataProvider(): array
    {
        return [
            'multi-:empty:-param' => [
                [
                    'or',
                    ['not', ['content_table' => null], ],
                    ['!=', 'content_table', 'field_2'],
                ],
                'content_table', ':empty:, field_2', '!=',
            ],
            [
                ['foo' => null], 'foo', ':empty:',
            ],
            [
                ['foo' => null], 'foo', ':EMPTY:',
            ],
            [
                ['not', ['foo' => null]], 'foo', ':notempty:',
            ],
            [
                ['not', ['foo' => null]], 'foo', ':NOTEMPTY:',
            ],
            [
                ['not', ['foo' => null]], 'foo', 'not :empty:',
            ],
            [
                ['not', ['foo' => null]], 'foo', 'NOT :EMPTY:',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getTextualColumnTypeByContentLengthDataProvider(): array
    {
        return [
            ['text', 254],
            ['text', 65534],
            ['text', 16777214],
            ['text', 4294967294],
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
    public function getTextualColumnStorageCapacityDataProvider(): array
    {
        return [
            [null, Schema::TYPE_TEXT],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        if (!Craft::$app->getDb()->getIsPgsql()) {
            $this->markTestSkipped();
        }
    }
}
