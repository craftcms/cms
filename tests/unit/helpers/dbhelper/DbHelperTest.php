<?php

namespace craftunit\helpers;


use craft\helpers\Db;
use craftunit\support\mockclasses\components\Serializable;
use Codeception\Test\Unit;
use yii\db\Exception;

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
    public function testParseParamGeneral($result, array $inputArray)
    {
        $collumn = isset($inputArray[0]) ? $inputArray[0] : null;
        $value =  isset($inputArray[1]) ? $inputArray[1] : null;
        $defaultOperator =  isset($inputArray[2]) ? $inputArray[2] : '=';
        $caseInsensitive =  isset($inputArray[3]) ? $inputArray[3] : null;

        $this->assertSame($result, Db::parseParam($collumn, $value, $defaultOperator, $caseInsensitive));
    }

    public function parseParamDataProvider()
    {
        return [
            'basic' => [
                ['or', [ 'in', 'foo', [ 'bar']]],
                ['foo', 'bar']
            ],
            'multi-array-format' => [
                self::MULTI_PARSEPARAM,
                ['content_table', ['field_1', 'field_2']],
            ],
            'multi-split-by-comma' => [
                self::MULTI_PARSEPARAM,
                ['content_table', 'field_1, field_2']
            ],
            'multi-not-param' => [
                self::MULTI_PARSEPARAM_NOT,
                ['content_table', 'field_1, field_2', 'not'],
            ],
            'multi-not-symbol' => [
                self::MULTI_PARSEPARAM_NOT,
                ['content_table', 'field_1, field_2', '!=']
            ],
            'multi-:empty:-param' => [
                [ 'or', [ 'not', ['or',['content_table' => null, ], ['content_table' => '',]]], ['!=', 'content_table', 'field_2']],
                ['content_table', ':empty:, field_2', '!=']
            ],
            'empty' => [
                ['or',[
                        'in',
                        '',[
                            'field_1',
                        ]]],
                ['', 'field_1'],
            ],
            'random-symbol' => [
                ['or',
                    ['raaa',  'content_table', 'field_1'],
                ],
                ['content_table', 'field_1', 'raaa'],
            ],
            'random-symbol-multi' => [
                ['or',
                    ['raaa',  'content_table', 'field_1'],
                    [ 'raaa', 'content_table', 'field_2' ]
                ],
                ['content_table', 'field_1, field_2', 'raaa'],
            ],
            ['', ['content_table', 'not']],
            ['', ['content', []]],
            ['', ['', '']],
            ['', ['content', null]],
            ['', ['contentCol', '']]
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
    public function testGetNumericCollumnType($int1, $int2, $result)
    {
        $this->assertSame($result, Db::getNumericalColumnType($int1, $int2));
    }

    public function numericCollumnTypesData()
    {
        return [
            'smallint1-minus' => [-0, -5, 'smallint(1)'],
            'smallint1' => [0, 5, 'smallint(1)'],
            'smallint1-minus-string' => ['-2', '-5', 'smallint(1)'],
            'smallint1-string' => ['0', '5', 'smallint(1)'],
            'smallint0' => [0, 0, 'smallint(0)'],
            'smallint2' => [0, 10, 'smallint(2)'],
            'smallint3' => [0, 100, 'smallint(3)'],
            'smallint3-2' => [100, 0, 'smallint(3)'],
            'smallint7' => [0, 1231224, 'integer(7)'],
            'smallint9' => [0, 230221224, 'integer(9)'],
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
        ];
    }
    /**
     * @dataProvider deletablesData
     */
    public function testDeleteIfExists($result, string $table, $condition = '', array $params = [])
    {
        $this->assertSame($result, Db::deleteIfExists($table, $condition, $params));
    }

    public function deletablesData()
    {
        // TODO: Setup a fixture for this.....
        return [
            [0, '{{%users}} users', 'users.id = 1234567890 and users.uid = "THISISNOTAUID"']
        ];
    }

    /*
     * Tests that a Yii\Db\Exception will be thrown if the table *Literaly* doesnt exist in the schema.
     */
    public function testDeleteIfExistsException()
    {
        $this->tester->expectException(Exception::class, function (){
            Db::deleteIfExists('iamnotatable12345678900987654321');
        });
    }

    public function testValuePrepareForDb()
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new \stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $excpectedDateTime = new \DateTime('2018-06-06 18:00:00');
        $excpectedDateTime->setTimezone($this->utcTimezone);

        $dateTime = new \DateTime('2018-06-06 18:00:00');
        $this->assertSame($excpectedDateTime->format('Y-m-d H:i:s'), Db::prepareValueForDb($dateTime));
        $this->assertSame( json_encode($jsonableArray), Db::prepareValueForDb($jsonableArray));
        $this->assertSame('{"name":"name"}', Db::prepareValueForDb($jsonableClass));
        $this->assertSame('Serialized data', Db::prepareValueForDb($serializable));
    }

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

        // One test to ensure that when a date time is passed in via, for example, string format but with a timezone. I.E an array or as demonstrated below.
        // It is created as a \DateTime with its predefined timezone, set to system, set to utc and then formatted as MySql format.
        $date = new \DateTime('2018-08-09 20:00:00', new \DateTimeZone('+09:00'));
        $preparedWithTz = Db::prepareDateForDb('2018-08-09T20:00:00+09:00');

        $date->setTimezone($this->systemTimezone);
        $date->setTimezone($this->utcTimezone);
        $this->assertSame($date->format('Y-m-d H:i:s'), $preparedWithTz);
    }
}