<?php
namespace craftunit\helpers;

use \craft\helpers\ArrayHelper;

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
