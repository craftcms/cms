<?php

namespace craftunit\helpers;


use craft\helpers\Db;
use craftunit\support\mockclasses\components\Serializable;
use Codeception\Test\Unit;

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

    public function testGetNumericCollumnType()
    {
        $this->assertSame(Db::getNumericalColumnType(0, 5), 'smallint(1)');
        $this->assertSame(Db::getNumericalColumnType(0, 0), 'smallint(0)');
        $this->assertSame(Db::getNumericalColumnType(0, 10), 'smallint(2)');
        $this->assertSame(Db::getNumericalColumnType(0, 100), 'smallint(3)');

        $this->assertSame(Db::getNumericalColumnType(100, 0), 'smallint(3)');
        $this->assertSame(Db::getNumericalColumnType(0, 1231224), 'integer(7)');
        $this->assertSame(Db::getNumericalColumnType(0, 230221224), 'integer(9)');
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

    /**
     * TODO: Fix this test.
     */
    public function testIsTextualCollumnType()
    {
        $this->markTestSkipped('Test bugs out. Need to determine what is textual ctype is used for. ');

        $this->assertTrue(Db::isTextualColumnType('Longtext'));
        $this->assertTrue(Db::isTextualColumnType('Tinytext'));
        $this->assertTrue(Db::isTextualColumnType('text'));

        $this->assertFalse(Db::isTextualColumnType('decimal'));
        $this->assertFalse(Db::isTextualColumnType('integer'));
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
        $this->assertSame('Serialized data', $serializable->serialize());

    }

    public function testPrepareDateForDb()
    {
        $dateTime = new \DateTime('2018-08-08 20:00:00');
    }

}