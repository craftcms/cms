<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\ArrayHelper;
use stdClass;
use UnitTester;

/**
 * Unit tests for the Array Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ArrayHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider toArrayDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testToArray($result, $input)
    {
        $toArray = ArrayHelper::toArray($input);
        $this->assertSame($result, $toArray);
    }

    /**
     * @dataProvider prependOrAppendDataProvider
     *
     * @param $result
     * @param $inputArray
     * @param $appendable
     * @param $preOrAppend
     */
    public function testPrependOrAppend($result, $inputArray, $appendable, $preOrAppend)
    {
        ArrayHelper::prependOrAppend($inputArray, $appendable, $preOrAppend);
        $this->assertSame($result, $inputArray);
    }

    /**
     *
     */
    public function testFilterEmptyStringsFromArray()
    {
        $this->assertSame([0 => 1, 1 => 2, 4 => null, 5 => 5], ArrayHelper::filterEmptyStringsFromArray([0 => 1, 1 => 2, 3 => '', 4 => null, 5 => 5]));
    }

    /**
     *
     */
    public function testFirstKey()
    {
        $this->assertNull(ArrayHelper::firstKey([]));
        $this->assertEquals(0, ArrayHelper::firstKey([1]));
        $this->assertEquals(5, ArrayHelper::firstKey([5 => 'value']));
        $this->assertEquals('firstKey', ArrayHelper::firstKey(['firstKey' => 'firstValue', 'secondKey' => 'secondValue']));
    }

    /**
     * @dataProvider renameDataProvider
     *
     * @param      $result
     * @param      $inputArray
     * @param      $oldKey
     * @param      $newKey
     * @param null $default
     */
    public function testArrayRename($result, $inputArray, $oldKey, $newKey, $default = null)
    {
        ArrayHelper::rename($inputArray, $oldKey, $newKey, $default);
        $this->assertSame($result, $inputArray);
    }

    /**
     * @dataProvider firstValueDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testFirstValue($result, $input)
    {
        $firstVal = ArrayHelper::firstValue($input);
        $this->assertSame($result, $firstVal);
    }

    /**
     * @dataProvider withoutDataProvider
     *
     * @param $result
     * @param $array
     * @param $key
     */
    public function testWithout($result, $array, $key)
    {
        $without = ArrayHelper::without($array, $key);
        $this->assertSame($result, $without);
    }

    /**
     * @dataProvider withoutValueDataProvider
     *
     * @param $result
     * @param $array
     * @param $value
     */
    public function testWithoutValue($result, $array, $value)
    {
        $without = ArrayHelper::withoutValue($array, $value);
        $this->assertSame($result, $without);
    }

    /**
     *
     */
    public function testWhere()
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

        $filtered = ArrayHelper::where($array, 'name', 'array 1');
        $this->assertCount(1, $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // Set the name to empty and see if we can filter by keys with an empty value
        $array[0]['name'] = '';
        $filtered = ArrayHelper::where($array, 'name', '');
        $this->assertCount(1, $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // Add a new key to the array that it empty and with an empty value. Make sure that when filtering empty by empty  it returns everything.
        $array[0][''] = '';
        $filtered = ArrayHelper::where($array, '', '');
        $this->assertCount(count($array), $filtered);
        $this->assertSame($array, $filtered);

        // Filter by emojis?
        $array[0]['😀'] = '😘';
        $filtered = ArrayHelper::where($array, '😀', '😘');
        $this->assertCount(1, $filtered);
        $this->assertSame('the first array', $filtered[0]['description']);

        // See if we can filter by an array as a value.
        $this->assertSame([['name' => ['testname' => true]]],
            ArrayHelper::where(
                [
                    ['name' => ['testname' => true]],
                    ['name' => '22'],
                ],
                'name',
                ['testname' => true]
            ));

        // Strict will only return 1. Non strict will typecast integer to string and thus find 2.
        $this->assertCount(2,
            ArrayHelper::where(
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
            ArrayHelper::where(
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
            ArrayHelper::where(
                [
                    ['name' => 'john'],
                    ['name' => 'michael'],
                ],
                'name',
                'john',
                true
            ));

        $this->assertSame(
            [['name' => 'john']],
            ArrayHelper::where(
                [
                    ['name' => 'john'],
                    ['name' => 'michael'],
                ],
                function($array) {
                    return $array['name'];
                },
                'john',
                true
            ));
        // Make sure that filter by value hasn't made any changes to the array content, etc.
        $mockedUp = [
            [
                'name' => '',
                'description' => 'the first array',
                '' => '',
                '😀' => '😘'

            ],
            [
                'name' => 'array 2',
                'description' => 'the second array'
            ]
        ];

        $this->assertSame($array, $mockedUp);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function toArrayDataProvider(): array
    {
        $stdClass2 = new StdClass();
        $stdClass2->subProp = 'value';

        $stdClass = new StdClass();
        $stdClass->prop1 = '11';
        $stdClass->prop2 = '22';
        $stdClass->prop3 = $stdClass2;

        return [
            [[], null],
            [[], null], [[1, 2, 3], [1, 2, 3]],
            [['prop1' => '11', 'prop2' => '22', 'prop3' => ['subProp' => 'value']], $stdClass],
        ];
    }

    /**
     * @return array
     */
    public function prependOrAppendDataProvider(): array
    {
        return [
            [[1, 2, 3, 4], [1, 2, 3], 4, false],
            [[4, 1, 2, 3], [1, 2, 3], 4, true],
            [[1, 2, 3, ['22']], [1, 2, 3], ['22'], false],
            [[1, 2, 3, null], [1, 2, 3], null, false],
        ];
    }

    /**
     * @return array
     */
    public function renameDataProvider(): array
    {
        return [
            [['fizz' => 'plop', 'foo2' => 'bar'], ['foo' => 'bar', 'fizz' => 'plop'], 'foo', 'foo2'],
            [['foo' => 'bar', 'fizz' => 'plop', 'fooY' => null], ['foo' => 'bar', 'fizz' => 'plop'], 'fooX', 'fooY'],
            [['foo' => 'bar', 'fizz' => 'plop'], ['foo' => 'bar', 'fizz' => 'plop'], 'fooX', 'foo'],
            [['foo' => 'bar', 'fizz' => 'plop', 'fooY' => 'test'], ['foo' => 'bar', 'fizz' => 'plop'], 'fooX', 'fooY', 'test'],
        ];
    }

    /**
     * @return array
     */
    public function firstValueDataProvider(): array
    {
        $std = new stdClass();
        $std->a = '22';
        return [
            ['test', ['test']],
            [['test'], [['test']]],
            [$std, ['key' => $std]]
        ];
    }

    /**
     * @return array
     */
    public function withoutDataProvider(): array
    {
        return [
            [[], ['key' => 'value'], 'key'],
            [['key' => 'value'], ['key' => 'value', 'key2' => 'value2'], 'key2'],
            [['key' => 'value'], ['key' => 'value'], 'notakey'],
            [[], ['value'], 0],
        ];
    }

    /**
     * @return array
     */
    public function withoutValueDataProvider(): array
    {
        return [
            [[], ['key' => 'value'], 'value'],
            [['key' => 'value'], ['key' => 'value'], 'notavalue'],
            [[], ['value'], 'value'],
            [[], ['key' => 'value', 'key2' => 'value'], 'value'],
        ];
    }
}
