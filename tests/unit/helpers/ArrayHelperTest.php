<?php

namespace app\helpers;

use craft\helpers\ArrayHelper;

class ArrayHelperTest extends \Codeception\TestCase\Test
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
        $this->assertSame([0 => 1, 1 => 2, 4 => null, 5 => 5], ArrayHelper::filterEmptyStringsFromArray([0 => 1, 1 => 2, 3 => '', 4 => null, 5 => 5]));
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
}
