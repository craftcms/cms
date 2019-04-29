<?php

namespace craftunit\helpers;


use craft\helpers\Db;
use craft\test\mockclasses\serializable\Serializable;
use Codeception\Test\Unit;
use yii\db\Exception;
use yii\db\Schema;

/**
 * Unit tests for the DB Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class DbHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected $systemTimezone;
    protected $utcTimezone;
    protected $asiaTokyoTimezone;

    protected function _before()
    {
        $this->systemTimezone = new \DateTimeZone(\Craft::$app->getTimeZone());
        $this->utcTimezone = new \DateTimeZone('UTC');
        $this->asiaTokyoTimezone = new \DateTimeZone('Asia/Tokyo');
    }

    protected function _after()
    {
    }
    const MULTI_PARSEPARAM_NOT = [
        'or',
        [
            '!=',
            'content_table',
            'field_1'
        ],
        [
            '!=',
            'content_table',
            'field_2'
        ]
    ];

    const MULTI_PARSEPARAM = [
        'or',
        [
            'in',
            'content_table',
            [
                'field_1',
                'field_2'
            ]
        ]
    ];
    const EMPTY_COLLUMN_PARSEPARAM = [
        'or',
        [
            'in',
            '',
            [
                'field_1',
            ]
        ]
    ];

    /**
     * @dataProvider parseParamDataProvider
     */
    public function testParseParamGeneral($result, $collumn, $value, $defaultOperator = '=', $caseInsensitive = false)
    {
        $this->assertSame($result, Db::parseParam($collumn, $value, $defaultOperator, $caseInsensitive));
    }

    public function parseParamDataProvider()
    {
        return [
            'basic' => [
                ['or', [ 'in', 'foo', [ 'bar']]],
                'foo', 'bar'
            ],
            'multi-array-format' => [
                self::MULTI_PARSEPARAM,
                'content_table', ['field_1', 'field_2'],
            ],
            'multi-split-by-comma' => [
                self::MULTI_PARSEPARAM,
                'content_table', 'field_1, field_2'
            ],
            'multi-not-param' => [
                self::MULTI_PARSEPARAM_NOT,
                'content_table', 'field_1, field_2', 'not',
            ],
            'multi-not-symbol' => [
                self::MULTI_PARSEPARAM_NOT,
                'content_table', 'field_1, field_2', '!='
            ],
            'empty' => [
                ['or',[
                        'in',
                        '',[
                            'field_1',
                        ]]],
                '', 'field_1',
            ],
            'random-symbol' => [
                ['or',
                    ['raaa',  'content_table', 'field_1'],
                ],
                'content_table', 'field_1', 'raaa',
            ],
            'random-symbol-multi' => [
                ['or',
                    ['raaa',  'content_table', 'field_1'],
                    [ 'raaa', 'content_table', 'field_2' ]
                ],
                'content_table', 'field_1, field_2', 'raaa',
            ],
            ['', 'content_table', 'not'],
            ['', 'content', []],
            ['', '', ''],
            ['', 'content', null],
            ['', 'contentCol', ''],

            'firstval-or' =>[
                ['or', ['in', 'content_table', ['field_1', 'field_2']]],
                'content_table', ['or', 'field_1', 'field_2'],
            ],
            'firstval-not' =>[
                ['and', ['not in', 'content_table', ['field_1', 'field_2']]],
                'content_table', ['not', 'field_1', 'field_2'],
            ],
        ];
    }

    /**
     * @dataProvider escapeParamData
     */
    public function testEscapeParam(string $result, string $input)
    {
        $escapeResult = Db::escapeParam($input);
        $this->assertSame($result, $escapeResult);
        $this->assertInternalType('string', $escapeResult);
    }

    public function escapeParamData()
    {
        return [
            ['\*', '*'],
            ['\,', ','],
            ['\,\*', ',*']
        ];
    }

    /**
     * @dataProvider collumnTypeParsingData
     */
    public function testCollumnTypeParsing($result, string $input)
    {
        $this->assertSame($result, Db::parseColumnType($input));
    }

    public function collumnTypeParsingData()
    {
        return [
            ['test', 'test'],
            [null, '!@#$%^&*()craftcms'],
            ['craftcms', 'craftcms!@#$%^&*()'],
            ['craft', 'craft,cms'],
            ['123', '123 craft'],
            ['craft', 'CRAFT'],
            [null, '🎧𢵌 😀😘⛄'],
            [null, 'Δδ'],
            [null, '"craftcms"']
        ];
    }


    /**
     * @dataProvider numericCollumnTypesData
     */
    public function testGetNumericCollumnType($result, $int1, $int2, $decimals = null)
    {
        $this->assertSame($result, Db::getNumericalColumnType($int1, $int2, $decimals));
    }

    public function numericCollumnTypesData()
    {
        return [
            'smallint1-minus' => ['smallint(1)', -0, -5],
            'smallint1' => ['smallint(1)', 0, 5],
            'smallint1-minus-string' => [ 'smallint(1)', '-2', '-5',],
            'smallint1-string' => ['smallint(1)', '0', '5'],
            'smallint0' => ['smallint(0)', 0, 0],
            'smallint2' => ['smallint(2)', 0, 10],
            'smallint3' => ['smallint(3)', 0, 100],
            'smallint3-2' => ['smallint(3)', 100, 0],
            'smallint7' => ['integer(7)', 0, 1231224],
            'smallint9' => [ 'integer(9)', 0, 230221224],
            'non-numeric' => ['integer(10)', null, null],
            'decimals' => ['decimal(6,2)', 123, 1233, 2],
        ];
    }

    /**
     * @dataProvider collumnLengthParseData
     */
    public function testCollumnLengthParsing($result, $input)
    {
        $this->assertSame($result, Db::parseColumnLength($input));
    }

    public function collumnLengthParseData()
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
     * @dataProvider simplifiedCollumnData
     */
    public function testGetSimplifiedCollumnType($result, $input)
    {
        $this->assertSame($result, Db::getSimplifiedColumnType($input));
    }

    public function simplifiedCollumnData()
    {
        return [
            ['textual', 'Textual'],
            ['numeric', 'Integer'],
            ['raaa', 'RAAA'],
            ['textual', 'Tinytext'],
            ['numeric', 'Decimal'],
            ['textual', 'Longtext'],
            ['textual', 'string!@#$%^&*()'],
            ['!@#$%', '!@#$%']
        ];
    }

    /**
     * // TODO: Set this up with a fixture or a migration so that we can *actually* delete tables
     * @dataProvider deleteTablesData
     */
    public function testDeleteIfExists($result, string $table, $condition = '', array $params = [])
    {
        $this->assertSame($result, Db::deleteIfExists($table, $condition, $params));
    }

    public function deleteTablesData()
    {
        return [
            [0, '{{%users}} users', "[[users.id]] = 1234567890 and [[users.uid]] = 'THISISNOTAUID'"]
        ];
    }

    /*
     * Tests that a Yii\Db\Exception will be thrown if the table *Literaly* doesnt exist in the schema.
     */
    public function testDeleteIfExistsException()
    {
        $this->tester->expectThrowable(Exception::class, function () {
            Db::deleteIfExists('iamnotatable12345678900987654321');
        });
    }

    /**
     * @dataProvider dataForDbPrepare
     * @param $result
     * @param $input
     */
    public function testValuePrepareForDb($result, $input)
    {
        $prepped = Db::prepareValueForDb($input);
        $this->assertSame($result, $prepped);
    }

    public function dataForDbPrepare()
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new \stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $dateTime = new \DateTime('2018-06-06 18:00:00');

        return [
            ['2018-06-06 18:00:00', $dateTime],
            ['{"name":"name"}', $jsonableClass],
            ['{"JsonArray":"SomeArray"}', $jsonableArray],
            ['Serialized data', $serializable],
            [false, false],
            // TODO: MB4.
            ['😀😘', '😀😘']
        ];
    }

    /**
     * TODO: Refactor this test to make it slightly clearer.
     */
    public function testPrepareDateForDb()
    {
        $date = new \DateTime('2018-08-08 20:00:00', $this->utcTimezone);
        $this->assertSame($date->format('Y-m-d H:i:s'), Db::prepareDateForDb($date));

        $date = new \DateTime('2018-08-08 20:00:00', $this->asiaTokyoTimezone);
        $dbPrepared = Db::prepareDateForDb($date);

        // Ensure db makes no changes.
        $this->assertSame('2018-08-08 20:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());

        // Set the time to utc from tokyo and ensure its the same as that from prepare.
        $date->setTimezone($this->utcTimezone);
        $this->assertSame($date->format('Y-m-d H:i:s'), $dbPrepared);

        // One test to ensure that when a date time is passed in via, for example, string format but with a timezone
        // It is created as a \DateTime with its predefined timezone, set to system, set to utc and then formatted as MySql format.
        $date = new \DateTime('2018-08-09 20:00:00', new \DateTimeZone('+09:00'));
        $preparedWithTz = Db::prepareDateForDb('2018-08-09T20:00:00+09:00');

        $date->setTimezone($this->systemTimezone);
        $date->setTimezone($this->utcTimezone);
        $this->assertSame($date->format('Y-m-d H:i:s'), $preparedWithTz);

        // Test that an invalid format will return null.
        $this->assertNull(Db::prepareDateForDb(['date' => '']));
    }

    /**
     * @dataProvider columnCompatibilityData
     * @param $result
     * @param $columnA
     * @param $columnB
     */
    public function testColumnCompatibility($result, $columnA, $columnB)
    {
        $areCompatible = Db::areColumnTypesCompatible($columnA, $columnB);
        $this->assertSame($result, $areCompatible);
    }
    public function columnCompatibilityData()
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
     * TODO: Why do all these fail?
     * @dataProvider isNumericData
     * @param $result
     * @param $input
     */
    public function testIsNumericColumnType($result, $input)
    {
        $isNumeric = Db::isNumericColumnType($input);
        $this->assertSame($result, $isNumeric);
    }

    public function isNumericData()
    {
        return [
            [false, 'integer(1)'],
            [false, 'decimal'],
            [false, 'bigint(5)'],
            [false, 'float'],
            [false, '[[float]]'],
            [false, '1234567890!@#$%^&*()'],
            [false, 'textual'],
            [false, 1],
        ];
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
            [1, Schema::TYPE_CHAR],
            [255, Schema::TYPE_STRING],
            [false, Schema::TYPE_MONEY],
            [false, Schema::TYPE_BOOLEAN],
        ];
    }

    /**
     * @dataProvider getMaxAllowedValueForNumericColumnData
     * @param $result
     * @param $input
     */
    public function testGetMaxAllowedValueForNumericColumn($result, $input)
    {
        $allowed = Db::getMaxAllowedValueForNumericColumn($input);
        $this->assertSame($result, $allowed);
    }
    public function getMaxAllowedValueForNumericColumnData()
    {
        return [
            [2147483647, 'integer(9)'],
            [false, 9],
            [false, 'stuff(9)'],
            [9223372036854775807, 'bigint(9223372036854775807)']
        ];
    }

    /**
     * @dataProvider getMinAllowedValueForNumericColumnData
     * @param $result
     * @param $input
     */
    public function testGetMinAllowedValueForNumericCollumn($result, $input)
    {
        $allowed = Db::getMinAllowedValueForNumericColumn($input);
        $this->assertSame($result, $allowed);
    }
    public function getMinAllowedValueForNumericColumnData()
    {
        return [
            [-2147483648, 'integer(9)'],
            [false, 9],
            [false, 'stuff(9)'],
            [-9223372036854775808, 'bigint(9223372036854775807)']
        ];
    }

    /**
     * @dataProvider prepareValuesForDbData
     * @param $result
     * @param $input
     */
    public function testPrepareValueForDb($result, $input)
    {
        $prepared = Db::prepareValuesForDb($input);
        $this->assertSame($result, $prepared);
    }
    public function prepareValuesForDbData()
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new \stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $excpectedDateTime = new \DateTime('2018-06-06 18:00:00');
        $excpectedDateTime->setTimezone(new \DateTimeZone('UTC'));

        $dateTime = new \DateTime('2018-06-06 18:00:00');

        return [
            [['{"date":"2018-06-06 18:00:00.000000","timezone_type":3,"timezone":"UTC"}'], [$dateTime]],
            [['{"name":"name"}'], [$jsonableClass]],
            [['{"JsonArray":"SomeArray"}'], [$jsonableArray]],
            [['[]'], [$serializable]],
            [[false], [false]],
            [['😀😘'], ['😀😘']]
        ];
    }
}
