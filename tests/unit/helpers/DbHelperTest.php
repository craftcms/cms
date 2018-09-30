<?php
/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 28/09/2018
 * Time: 11:44
 */

namespace craftunit\helpers;


use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use yii\rest\Serializer;

class DbHelperTest extends \Codeception\Test\Unit
{
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

    public function testParseParam()
    {

        $this->assertSame(Db::parseParam('foo', 'bar'), self::BASIC_PARSEPARAM);

        $this->assertSame(Db::parseParam('content_table', ['field_1', 'field_2']), self::MULTI_PARSEPARAM);
        $this->assertSame(Db::parseParam('content_table', 'field_1, field_2'), self::MULTI_PARSEPARAM);

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

        $excpectedDateTime = new \DateTime('2018-06-06 18:00:00');
        $excpectedDateTime->setTimezone(new \DateTimeZone('UTC'));

        $dateTime = new \DateTime('2018-06-06 18:00:00');
        $this->assertSame(Db::prepareValueForDb($dateTime), $excpectedDateTime->format('Y-m-d H:i:s'));
        $this->assertSame(Db::prepareValueForDb($jsonableArray), json_encode($jsonableArray));

        // TODO: Serializable test case
    }

}