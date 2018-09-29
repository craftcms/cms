<?php
/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 28/09/2018
 * Time: 11:44
 */

namespace app\helpers;


use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use yii\rest\Serializer;

class DbHelperTest extends \Codeception\Test\Unit
{
    const BASIC_PARSEPARAM_QUERY = [
        'or',
        [
            'in',
            'foo',
            [
                'bar'
            ]
        ]
    ];

    public function testParseParam()
    {
        $expectedReturn = [
            'or',
            [
                'in',
                'foo',
                [
                    'bar'
                ]
            ]
        ];

        $this->assertSame(Db::parseParam('foo', 'bar'), $expectedReturn);

        // Lets add another param into this and simulate passig value as an array.
        $expectedReturn[1]['foo'][] = 'baz';
        $this->assertSame(Db::parseParam('foo', ['bar', 'baz']), $expectedReturn);
        $this->assertSame(Db::parseParam('foo', 'bar, baz'), $expectedReturn);



        // EMPTY VALUE TESTING---------------------------------------------------
        // No param passed? Empty string
        $this->assertSame(Db::parseParam('', ''), '');
        $this->assertSame(Db::parseParam('content', []), '');
        $this->assertSame(Db::parseParam('content', null), '');

        // No value. Empty string.
        $this->assertSame(Db::parseParam('contentCol', ''), '');

        // No collumn. you guessed it: https://i.pinimg.com/originals/62/0e/39/620e398761442eee86d00eab52a812d2.jpg
        $this->assertSame(Db::parseParam('', '22'), '');

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
        //
        $this->assertSame(Db::getSimplifiedColumnType('Textual'), 'textual');
        $this->assertSame(Db::getSimplifiedColumnType('Integer'), 'numeric');
        $this->assertSame(Db::getSimplifiedColumnType('RAAAAAA'), 'raaaaaa');
        $this->assertSame(Db::getSimplifiedColumnType('Tinytext'), 'textual');
        $this->assertSame(Db::getSimplifiedColumnType('Decimal'), 'numeric');
        $this->assertSame(Db::getSimplifiedColumnType('Longtext'), 'textual');
    }


    public function testIsTextualCollumnType()
    {
        $this->assertTrue(Db::isTextualColumnType('tinytext'));
        $this->assertTrue(Db::isTextualColumnType('enum'));
        $this->assertTrue(Db::isTextualColumnType('longtext'));
        $this->assertTrue(Db::isTextualColumnType('mediumtext'));


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