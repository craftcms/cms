<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers\dbhelper;

use Codeception\Test\Unit;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\models\UserGroup;
use craft\test\mockclasses\serializable\Serializable;
use craft\test\TestCase;
use DateTime;
use DateTimeZone;
use stdClass;
use UnitTester;
use yii\db\Exception;
use yii\db\Schema;

/**
 * Unit tests for the DB Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class DbHelperTest extends TestCase
{
    public const MULTI_PARSEPARAM_NOT = [
        'or',
        [
            '!=',
            'foo',
            'field_1',
        ],
        [
            '!=',
            'foo',
            'field_2',
        ],
    ];

    public const MULTI_PARSEPARAM = ['foo' => ['field_1', 'field_2']];

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var DateTimeZone
     */
    protected DateTimeZone $systemTimezone;

    /**
     * @var DateTimeZone
     */
    protected DateTimeZone $utcTimezone;

    /**
     * @var DateTimeZone
     */
    protected DateTimeZone $asiaTokyoTimezone;

    /**
     * @var bool
     */
    protected bool $isMysql;

    /**
     * @dataProvider parseParamDataProvider
     * @param string|array $expected
     * @param string $column
     * @param mixed $value
     * @param string $defaultOperator
     * @param bool $caseInsensitive
     * @param string|null $columnType
     */
    public function testParseParam(string|array $expected, string $column, mixed $value, string $defaultOperator = '=', bool $caseInsensitive = false, ?string $columnType = null): void
    {
        self::assertSame($expected, Db::parseParam($column, $value, $defaultOperator, $caseInsensitive, $columnType));
    }

    /**
     * @dataProvider escapeParamDataProvider
     * @param string $expected
     * @param string $value
     */
    public function testEscapeParam(string $expected, string $value): void
    {
        self::assertSame($expected, Db::escapeParam($value));
    }

    /**
     * @dataProvider escapeCommasDataProvider
     * @param string $expected
     * @param string $value
     */
    public function testEscapeCommas(string $expected, string $value): void
    {
        self::assertSame($expected, Db::escapeCommas($value));
    }

    /**
     * @dataProvider extractGlueDataProvider
     *
     * @param string|null $expectedGlue
     * @param mixed $expectedValue
     * @param mixed $value
     */
    public function testExtractGlue(?string $expectedGlue, $expectedValue, $value): void
    {
        $glue = Db::extractGlue($value);
        self::assertEquals($expectedGlue, $glue);
        self::assertEquals($expectedValue, $value);
    }

    /**
     *
     */
    public function testNormalizeModelParam(): void
    {
        $group1 = new UserGroup(['id' => 1]);
        $group2 = new UserGroup(['id' => 2]);

        $normalizeUserGroup = function($item) {
            return $item instanceof UserGroup ? $item->id : null;
        };

        $value = $group1;
        self::assertEquals(true, Db::normalizeParam($value, $normalizeUserGroup));
        self::assertEquals([1], $value);

        $value = ['and', $group1, $group2];
        self::assertEquals(true, Db::normalizeParam($value, $normalizeUserGroup));
        self::assertEquals(['and', 1, 2], $value);

        $value = null;
        self::assertEquals(true, Db::normalizeParam($value, $normalizeUserGroup));
        self::assertNull($value);

        $value = 'foo';
        self::assertEquals(false, Db::normalizeParam($value, $normalizeUserGroup));
        self::assertEquals('foo', $value);

        $value = ['foo'];
        self::assertEquals(false, Db::normalizeParam($value, $normalizeUserGroup));
        self::assertEquals(['foo'], $value);
    }

    /**
     * @dataProvider parseColumnTypeDataProvider
     * @param string|null $expected
     * @param string $columnType
     */
    public function testParseColumnType(?string $expected, string $columnType): void
    {
        self::assertSame($expected, Db::parseColumnType($columnType));
    }

    /**
     * @dataProvider getNumericalColumnTypeDataProvider
     * @param string $expected
     * @param int|null $min
     * @param int|null $max
     * @param int|null $decimals
     * @throws \yii\base\Exception
     */
    public function testGetNumericalColumnType(string $expected, ?int $min, ?int $max, ?int $decimals = null): void
    {
        self::assertSame($expected, Db::getNumericalColumnType($min, $max, $decimals));
    }

    /**
     * @dataProvider parseColumnLengthDataProvider
     * @param int|null $expected
     * @param string $columnType
     */
    public function testParseColumnLength(?int $expected, string $columnType): void
    {
        self::assertSame($expected, Db::parseColumnLength($columnType));
    }

    /**
     * @dataProvider getSimplifiedColumnTypeDataProvider
     * @param string $expected
     * @param string $columnType
     */
    public function testGetSimplifiedColumnType(string $expected, string $columnType): void
    {
        self::assertSame($expected, Db::getSimplifiedColumnType($columnType));
    }

    /**
     * @dataProvider deleteIfExistsDataProvider
     * @param int $expected
     * @param string $table
     * @param string|array $condition
     * @param array $params
     * @throws Exception
     * @todo Set this up with a fixture or a migration so that we can *actually* delete tables
     */
    public function testDeleteIfExists(int $expected, string $table, string|array $condition = '', array $params = []): void
    {
        self::assertSame($expected, Db::deleteIfExists($table, $condition, $params));
    }

    /*
     * Tests that a Yii\Db\Exception will be thrown if the table *literally* doesnt exist in the schema.
     */
    public function testDeleteIfExistsException(): void
    {
        $this->tester->expectThrowable(Exception::class, function() {
            Db::deleteIfExists('iamnotatable12345678900987654321');
        });
    }

    /**
     * @dataProvider prepareValueForDbDataProvider
     * @param mixed $expected
     * @param mixed $value
     */
    public function testPrepareValueForDb(mixed $expected, mixed $value): void
    {
        self::assertSame($expected, Db::prepareValueForDb($value));
    }

    /**
     *
     */
    public function testPrepareDateForDb(): void
    {
        $date = new DateTime('2018-08-08 20:00:00', $this->utcTimezone);
        self::assertSame($date->format('Y-m-d H:i:s'), Db::prepareDateForDb($date));

        $date = new DateTime('2018-08-08 20:00:00', $this->asiaTokyoTimezone);
        $dbPrepared = Db::prepareDateForDb($date);

        // Ensure db makes no changes.
        self::assertSame('2018-08-08 20:00:00', $date->format('Y-m-d H:i:s'));
        self::assertSame('Asia/Tokyo', $date->getTimezone()->getName());

        // Set the time to utc from tokyo and ensure its the same as that from prepare.
        $date->setTimezone($this->utcTimezone);
        self::assertSame($date->format('Y-m-d H:i:s'), $dbPrepared);

        // One test to ensure that when a date time is passed in via, for example, string format but with a timezone
        // It is created as a \DateTime with its predefined timezone, set to system, set to utc and then formatted as MySql format.
        $date = new DateTime('2018-08-09 20:00:00', new DateTimeZone('+09:00'));
        $preparedWithTz = Db::prepareDateForDb('2018-08-09T20:00:00+09:00');

        $date->setTimezone($this->systemTimezone);
        $date->setTimezone($this->utcTimezone);
        self::assertSame($date->format('Y-m-d H:i:s'), $preparedWithTz);

        // Test that an invalid format will return null.
        self::assertNull(Db::prepareDateForDb(['date' => '']));
    }

    /**
     * @dataProvider areColumnTypesCompatibleDataProvider
     * @param bool $expected
     * @param string $typeA
     * @param string $typeB
     */
    public function testAreColumnTypesCompatible(bool $expected, string $typeA, string $typeB): void
    {
        self::assertSame($expected, Db::areColumnTypesCompatible($typeA, $typeB));
    }

    /**
     * @dataProvider isNumericColumnTypeDataProvider
     * @param bool $expected
     * @param string $columnType
     */
    public function testIsNumericColumnType(bool $expected, string $columnType): void
    {
        self::assertSame($expected, Db::isNumericColumnType($columnType));
    }

    /**
     * @dataProvider isTextualColumnTypeDataProvider
     * @param bool $expected
     * @param string $columnType
     */
    public function testIsTextualColumnType(bool $expected, string $columnType): void
    {
        self::assertSame($expected, Db::isTextualColumnType($columnType));
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
     * @dataProvider getMaxAllowedValueForNumericColumnDataProvider
     * @param int|false $expected
     * @param string $columnType
     */
    public function testGetMaxAllowedValueForNumericColumn(int|false $expected, string $columnType): void
    {
        self::assertSame($expected, Db::getMaxAllowedValueForNumericColumn($columnType));
    }

    /**
     * @dataProvider getMinAllowedValueForNumericColumnDataProvider
     * @param int|false $expected
     * @param string $columnType
     */
    public function testGetMinAllowedValueForNumericColumn(int|false $expected, string $columnType): void
    {
        self::assertSame($expected, Db::getMinAllowedValueForNumericColumn($columnType));
    }

    /**
     * @dataProvider prepareValuesForDbDataProvider
     * @param array $expected
     * @param mixed $values
     */
    public function testPrepareValuesForDb(array $expected, mixed $values): void
    {
        self::assertSame($expected, Db::prepareValuesForDb($values));
    }

    /**
     *
     */
    public function testBatch(): void
    {
        $result = Db::batch((new Query())->from([Table::SITES]), 50);
        self::assertFalse($result->each);
        self::assertSame(50, $result->batchSize);
    }

    /**
     *
     */
    public function testEach(): void
    {
        $result = Db::each((new Query())->from([Table::SITES]), 50);
        self::assertTrue($result->each);
        self::assertSame(50, $result->batchSize);
    }

    /**
     * @return array
     */
    public function parseParamDataProvider(): array
    {
        return [
            'basic' => [
                ['foo' => 'bar'],
                'foo', 'bar',
            ],
            'multi-array-format' => [
                self::MULTI_PARSEPARAM,
                'foo', ['field_1', 'field_2'],
            ],
            'multi-split-by-comma' => [
                self::MULTI_PARSEPARAM,
                'foo', 'field_1, field_2',
            ],
            'multi-not-param' => [
                self::MULTI_PARSEPARAM_NOT,
                'foo', 'field_1, field_2', 'not',
            ],
            'multi-not-symbol' => [
                self::MULTI_PARSEPARAM_NOT,
                'foo', 'field_1, field_2', '!=',
            ],
            'random-symbol' => [
                ['raaa', 'foo', 'field_1'],
                'foo', 'field_1', 'raaa',
            ],
            'random-symbol-multi' => [
                [
                    'or',
                    ['raaa', 'foo', 'field_1'],
                    ['raaa', 'foo', 'field_2'],
                ],
                'foo', 'field_1, field_2', 'raaa',
            ],
            ['', 'foo', 'not'],
            ['', 'foo', []],
            ['', '', ''],
            ['', 'foo', null],
            ['', 'foo', ''],
            [
                ['foo' => ['field_1', 'field_2']],
                'foo', ['or', 'field_1', 'field_2'],
            ],
            [
                ['not', ['foo' => ['field_1', 'field_2']]],
                'foo', ['not', 'field_1', 'field_2'],
            ],
            [
                ['foo' => true],
                'foo', true, '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => true],
                'foo', 1, '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => true],
                'foo', '1', '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => true],
                'foo', 'not 0', '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => true],
                'foo', 'not :empty:', '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => false],
                'foo', false, '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => false],
                'foo', 0, '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => false],
                'foo', '0', '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => false],
                'foo', 'not 1', '=', false, Schema::TYPE_BOOLEAN,
            ],
            [
                ['foo' => false],
                'foo', ':empty:', '=', false, Schema::TYPE_BOOLEAN,
            ],
        ];
    }

    /**
     * @return array
     */
    public function escapeParamDataProvider(): array
    {
        return [
            ['\*', '*'],
            ['\,', ','],
            ['\,\*', ',*'],
            ['\,\*', '\,\*'],
        ];
    }

    /**
     * @return array
     */
    public function escapeCommasDataProvider(): array
    {
        return [
            ['foo\, bar', 'foo, bar'],
            ['foo\, bar*', 'foo, bar*'],
            ['foo\, bar', 'foo\, bar'],
        ];
    }

    /**
     * @return array
     */
    public function extractGlueDataProvider(): array
    {
        return [
            ['and', ['foo', 'bar'], ['and', 'foo', 'bar']],
            ['or', ['foo', 'bar'], ['or', 'foo', 'bar']],
            ['not', ['foo', 'bar'], ['not', 'foo', 'bar']],
            ['and', ['foo', 'bar'], ['AND', 'foo', 'bar']],
            [null, ['foo', 'bar'], ['foo', 'bar']],
            [null, 'foo', 'foo'],
        ];
    }

    /**
     * @return array
     */
    public function parseColumnTypeDataProvider(): array
    {
        return [
            ['string', 'STRING(255)'],
            ['decimal', 'DECIMAL(14,4)'],
            [null, '"invalid"'],
        ];
    }

    /**
     * @return array
     */
    public function getNumericalColumnTypeDataProvider(): array
    {
        return [
            'smallint1-minus' => ['smallint(1)', -0, -5],
            'smallint1' => ['smallint(1)', 0, 5],
            'smallint1-minus-string' => ['smallint(1)', -2, -5],
            'smallint1-string' => ['smallint(1)', 0, 5],
            'smallint0' => ['smallint(0)', 0, 0],
            'smallint2' => ['smallint(2)', 0, 10],
            'smallint3' => ['smallint(3)', 0, 100],
            'smallint3-2' => ['smallint(3)', 100, 0],
            'smallint7' => ['integer(7)', 0, 1231224],
            'smallint9' => ['integer(9)', 0, 230221224],
            'non-numeric' => ['integer(10)', null, null],
            'decimals' => ['decimal(6,2)', 123, 1233, 2],
        ];
    }

    /**
     * @return array
     */
    public function parseColumnLengthDataProvider(): array
    {
        return [
            [2, 'integer(2)'],
            [null, '2'],
            [100, 'craftcms(100)'],
            [null, '(100)'],
            [null, '!@#$%^&*(100)'],
            [null, '!@#$%^&*(100)'],
            [null, '(integer(2))'],
        ];
    }

    /**
     * @return array
     */
    public function getSimplifiedColumnTypeDataProvider(): array
    {
        return [
            ['textual', 'Textual'],
            ['numeric', 'Integer'],
            ['raaa', 'RAAA'],
            ['textual', 'Tinytext'],
            ['numeric', 'Decimal'],
            ['textual', 'Longtext'],
            ['textual', 'string!@#$%^&*()'],
            ['!@#$%', '!@#$%'],
        ];
    }

    /**
     * @return array
     */
    public function deleteIfExistsDataProvider(): array
    {
        return [
            [0, Table::USERS, ['id' => 1234567890]],
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function prepareValueForDbDataProvider(): array
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $dateTime = new DateTime('2018-06-06 18:00:00', new DateTimeZone('UTC'));

        return [
            ['2018-06-06 18:00:00', $dateTime],
            ['{"name":"name"}', $jsonableClass],
            ['{"JsonArray":"SomeArray"}', $jsonableArray],
            ['Serialized data', $serializable],
            [false, false],
            ['😀😘', '😀😘'],
            ['🆔', '🆔'],
        ];
    }

    /**
     * @return array
     */
    public function areColumnTypesCompatibleDataProvider(): array
    {
        return [
            [true, 'Tinytext', 'Longtext'],
            [true, 'Decimal', 'Decimal'],
            [true, '!@#$%', '!@#$%'],
            [true, 'string', 'string!@#$%'],
            [false, 'decimal', 'string'],
            [false, 'abc', '123'],
            [false, 'datetime', 'timestamp'],
        ];
    }

    /**
     * @return array
     */
    public function isNumericColumnTypeDataProvider(): array
    {
        return [
            [true, 'smallint'],
            [true, 'integer'],
            [true, 'integer(1)'],
            [true, 'bigint(5)'],
            [true, 'float'],
            [true, 'double'],
            [true, 'decimal(14,4)'],
            [false, 'string(255)'],
        ];
    }

    /**
     * @return array
     */
    public function isTextualColumnTypeDataProvider(): array
    {
        return [
            [true, 'string(255)'],
            [true, 'string'],
            [true, 'char'],
            [true, 'text'],
            [true, 'tinytext'],
            [true, 'mediumtext'],
            [true, 'longtext'],
            [true, "enum('foo', 'bar', 'baz')"],
            [false, 'smallint'],
            [false, 'integer'],
            [false, 'integer(1)'],
            [false, 'bigint(5)'],
            [false, 'float'],
            [false, 'double'],
            [false, 'decimal(14,4)'],
        ];
    }

    /**
     * @return array
     */
    public function getTextualColumnStorageCapacityDataProvider(): array
    {
        return [
            [1, Schema::TYPE_CHAR],
            [255, Schema::TYPE_STRING],
            [false, Schema::TYPE_MONEY],
            [false, Schema::TYPE_BOOLEAN],
        ];
    }

    /**
     * @return array
     */
    public function getMaxAllowedValueForNumericColumnDataProvider(): array
    {
        return [
            [2147483647, 'integer(9)'],
            [false, 'stuff(9)'],
            [9223372036854775807, 'bigint(9223372036854775807)'],
        ];
    }

    /**
     * @return array
     */
    public function getMinAllowedValueForNumericColumnDataProvider(): array
    {
        return [
            [-2147483648, 'integer(9)'],
            [false, 'stuff(9)'],
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function prepareValuesForDbDataProvider(): array
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $dateTime = new DateTime('2018-06-06 18:00:00', new DateTimeZone('UTC'));

        return [
            [['2018-06-06 18:00:00'], [$dateTime]],
            [['{"name":"name"}'], [$jsonableClass]],
            [['{"JsonArray":"SomeArray"}'], [$jsonableArray]],
            [['Serialized data'], [$serializable]],
            [[false], [false]],
            [['😀😘'], ['😀😘']],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->systemTimezone = new DateTimeZone(Craft::$app->getTimeZone());
        $this->utcTimezone = new DateTimeZone('UTC');
        $this->asiaTokyoTimezone = new DateTimeZone('Asia/Tokyo');
        $this->isMysql = Craft::$app->getDb()->getIsMysql();
    }
}
