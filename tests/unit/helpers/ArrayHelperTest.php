<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;

use \craft\helpers\ArrayHelper;

/**
 * Unit tests for the Array Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ArrayHelperTest extends \Codeception\Test\Unit
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

    public function testToArray()
    {
        $this->assertEquals([], ArrayHelper::toArray(null));
        $this->assertEquals([1, 2, 3], ArrayHelper::toArray([1, 2, 3]));
    }

    public function testPrependOrAppend()
    {
        $array = [1, 2, 3];
        ArrayHelper::prependOrAppend($array, 4, false);
        $this->assertSame([1, 2, 3, 4], $array);

        $array = [1, 2, 3];
        ArrayHelper::prependOrAppend($array, 4, true);
        $this->assertSame([4, 1, 2, 3], $array);

        $array = [1, 2, 3];
        ArrayHelper::prependOrAppend($array, ['22'], false);
        $this->assertSame([1, 2, 3, ['22']], $array);

        $array = [1, 2, 3];
        ArrayHelper::prependOrAppend($array, null, false);
        $this->assertSame([1, 2, 3, null], $array);
    }

    public function testFilterEmptyStringsFromArray()
    {
        $this->assertSame([0 => 1, 1 => 2, 4 => null, 5 => 5], ArrayHelper::filterEmptyStringsFromArray([0 => 1, 1 => 2, 3 => '', 4 => null, 5=> 5]));
    }

    public function testFirstKey()
    {
        $this->assertNull(ArrayHelper::firstKey([]));
        $this->assertEquals(0, ArrayHelper::firstKey([1]));
        $this->assertEquals(5, ArrayHelper::firstKey([5 => 'value']));
        $this->assertEquals('firstKey', ArrayHelper::firstKey(['firstKey' => 'firstValue', 'secondKey' => 'secondValue']));
    }

    public function testRename()
    {
        $array = ['foo' => 'bar', 'fizz' => 'plop'];
        ArrayHelper::rename($array, 'foo', 'foo2');
        $this->assertSame(['fizz' => 'plop', 'foo2' => 'bar'], $array);

        $array = ['foo' => 'bar', 'fizz' => 'plop'];
        ArrayHelper::rename($array, 'fooX', 'fooY');
        $this->assertSame(['foo' => 'bar', 'fizz' => 'plop', 'fooY' => null], $array);

        $array = ['foo' => 'bar', 'fizz' => 'plop'];
        ArrayHelper::rename($array, 'fooX', 'foo');
        $this->assertSame(['foo' => 'bar', 'fizz' => 'plop'], $array);

        $array = ['foo' => 'bar', 'fizz' => 'plop'];
        ArrayHelper::rename($array, 'fooX', 'fooY', 'test');
        $this->assertSame(['foo' => 'bar', 'fizz' => 'plop', 'fooY' => 'test'], $array);
    }

    public function testFilterbyValue()
    {
        // TODO: Inconsistent coding style
        $array = [
            [
                'name' => 'array 1',
                'description' => 'the first array',
            ],
            [
                'name' => 'array 2',
                'description' => 'the second array'
            ]
        ];

        $filtered = ArrayHelper::filterByValue($array, 'name', 'array 1');
        $this->assertCount(1, $filtered);
        $this->assertSame( 'the first array', $filtered[0]['description']);

        // Set the name to empty and see if we can filter by keys with an empty value
        $array[0]['name'] = '';
        $filtered = ArrayHelper::filterByValue($array, 'name', '');
        $this->assertCount(1, $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // Add a new key to the array. that it empty and with an empty value. Make sure that when filtering empty it return everything.
        $array[0][''] = '';
        $filtered = ArrayHelper::filterByValue($array, '', '');
        $this->assertCount(count($array), $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // Filter by emojis?
        $array[0]['😀'] = '😘';
        $filtered = ArrayHelper::filterByValue($array, '😀', '😘');
        $this->assertCount(1, $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // See if we can filter by an array as a value.
        $this->assertSame([['name' => ['testname' => true]]],
            ArrayHelper::filterByValue(
            [
                ['name' => ['testname' => true]],
                ['name' => '22'],
            ],
            'name',
            ['testname' => true]
        ));

        // Strict will only return 1. Non strict will typecast integer to string and thus find 2.
        $this->assertCount(2,
            ArrayHelper::filterByValue(
                [
                    ['name' => 22],
                    ['name' => '22'],
                ],
                'name',
                22,
                false
            )
        );
        $this->assertCount(1,
            ArrayHelper::filterByValue(
                [
                    ['name' => 22],
                    ['name' => '22'],
                ],
                'name',
                22,
                true
            )
        );

        $this->assertSame(
            [['name' => 'john']],
            ArrayHelper::filterByValue(
            [
                ['name' => 'john'],
                ['name' => 'michael'],
            ],
            ['name'],
            'john',
            true
        ));

        $this->assertSame(
            [['name' => 'john']],
            ArrayHelper::filterByValue(
                [
                    ['name' => 'john'],
                    ['name' => 'michael'],
                ],
                function ($array, $default){
                    return $array['name'];
                },
                'john',
                true
            ));
        // Make sure that filter by value hasnt made any changes to the array content e.t.c.
        $mockedUp = [
            [
                'name' => '',
                'description' => 'the first array',
                '' => '',
                '😀' =>'😘'

            ],
            [
                'name' => 'array 2',
                'description' => 'the second array'
            ]
        ];

        $this->assertSame($array, $mockedUp);
    }
}
