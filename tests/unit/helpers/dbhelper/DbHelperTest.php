<?php

namespace craftunit\helpers;


use craft\helpers\Db;
use craftunit\support\mockclasses\components\Serializable;
use Codeception\Test\Unit;
use yii\base\Exception;

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

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    const BASIC_PARSEPARAM = [
        'or',
        [
            'in',
            'foo',
            [
                'bar'
            ]
        ]
    ];

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

    const MULTI_PARSEPARAM_EMPTY = [
        'or',
        [
            'not',
            [
                'or',
                [
                    'content_table' => null,
                ],
                [
                    'content_table' => '',
                ]
            ]
        ],
        [
            '!=',
            'content_table',
            'field_2'
        ]
    ];

    public function testParseParam()
    {
        // TODO: Postgres >.<
        $this->assertSame(self::BASIC_PARSEPARAM, Db::parseParam('foo', 'bar'));
        $this->assertSame(self::MULTI_PARSEPARAM, Db::parseParam('content_table', ['field_1', 'field_2']));
        $this->assertSame(self::MULTI_PARSEPARAM, Db::parseParam('content_table', 'field_1, field_2'));
        $this->assertSame(self::MULTI_PARSEPARAM_NOT, Db::parseParam('content_table', 'field_1, field_2', 'not'));
        $this->assertSame(self::MULTI_PARSEPARAM_NOT, Db::parseParam('content_table', 'field_1, field_2', '!='));

        $this->assertSame(self::MULTI_PARSEPARAM_EMPTY, Db::parseParam('content_table', ':empty:, field_2', '!='));
        $this->assertSame('', Db::parseParam('content_table', 'not'));
        $this->assertSame('', Db::parseParam('content', []));

        // No param passed? Empty string
        $this->assertSame('', Db::parseParam('', ''));
        $this->assertSame('', Db::parseParam('content', null));

        // No value. Empty string.
        $this->assertSame('', Db::parseParam('contentCol', ''));

        // No collumn does return an array.
        $this->assertSame(self::EMPTY_COLLUMN_PARSEPARAM, Db::parseParam('', 'field_1'));
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

    public function testGetSimplifiedCollumnType()
    {
        $this->assertSame('textual', Db::getSimplifiedColumnType('Textual'));
        $this->assertSame( 'numeric', Db::getSimplifiedColumnType('Integer'));
        $this->assertSame('raaaaaa', Db::getSimplifiedColumnType('RAAAAAA'));
        $this->assertSame('textual', Db::getSimplifiedColumnType('Tinytext'));
        $this->assertSame('numeric', Db::getSimplifiedColumnType('Decimal'));
        $this->assertSame('textual', Db::getSimplifiedColumnType('Longtext'));
    }

    public function testValuePrepareForDb()
    {
        $jsonableArray = ['JsonArray' => 'SomeArray'];
        $jsonableClass = new \stdClass();
        $jsonableClass->name = 'name';
        $serializable = new Serializable();

        $excpectedDateTime = new \DateTime('2018-06-06 18:00:00');
        $excpectedDateTime->setTimezone(new \DateTimeZone('UTC'));

        $dateTime = new \DateTime('2018-06-06 18:00:00');
        $this->assertSame($excpectedDateTime->format('Y-m-d H:i:s'), Db::prepareValueForDb($dateTime));
        $this->assertSame( json_encode($jsonableArray), Db::prepareValueForDb($jsonableArray));
        $this->assertSame('{"name":"name"}', Db::prepareValueForDb($jsonableClass));
        $this->assertSame('Serialized data', Db::prepareValueForDb($serializable));
    }

    public function testPrepareDateForDb()
    {
        $date = new \DateTime('2018-08-08 20:00:00', new \DateTimeZone('UTC'));
        $this->assertSame($date->format('Y-m-d H:i:s'), Db::prepareDateForDb($date));

        $date = new \DateTime('2018-08-08 20:00:00', new \DateTimeZone('Asia/Tokyo'));
        $dbPrepared = Db::prepareDateForDb($date);

        // Ensure db makes no changes.
        $this->assertSame('2018-08-08 20:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tokyo', $date->getTimezone()->getName());

        // Set the time to utc from tokyo and ensure its the same as that from prepare.
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->assertSame($date->format('Y-m-d H:i:s'), $dbPrepared);

        // One test to ensure that when a date time is passed in via, for example, string format.
        // It is created, set to system, set to utc and then formatted as MySql format.
        $date = new \DateTime('2018-08-09 20:00:00', new \DateTimeZone('+09:00'));
        $preparedWithTz = Db::prepareDateForDb('2018-08-09T20:00:00+09:00');

        $date->setTimezone(new \DateTimeZone(\Craft::$app->getTimeZone()));
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->assertSame($date->format('Y-m-d H:i:s'), $preparedWithTz);
    }
}