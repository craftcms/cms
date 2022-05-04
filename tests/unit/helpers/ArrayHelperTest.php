<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\ArrayHelper;
use craft\test\TestCase;
use stdClass;

/**
 * Unit tests for the Array Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ArrayHelperTest extends TestCase
{
    /**
     * @dataProvider toArrayDataProvider
     * @param array $expected
     * @param mixed $object
     */
    public function testToArray(array $expected, mixed $object): void
    {
        self::assertSame($expected, ArrayHelper::toArray($object));
    }

    /**
     * @dataProvider prependDataProvider
     * @param array $expected
     * @param array $array
     * @param array $values
     */
    public function testPrepend(array $expected, array $array, array $values): void
    {
        ArrayHelper::prepend($array, ...$values);
        self::assertSame($expected, $array);
    }

    /**
     * @dataProvider appendDataProvider
     * @param array $expected
     * @param array $array
     * @param array $values
     */
    public function testAppend(array $expected, array $array, array $values): void
    {
        ArrayHelper::append($array, ...$values);
        self::assertSame($expected, $array);
    }

    /**
     * @dataProvider prependOrAppendDataProvider
     * @param array $expected
     * @param array $array
     * @param mixed $appendable
     * @param bool $prepend
     */
    public function testPrependOrAppend(array $expected, array $array, mixed $appendable, bool $prepend): void
    {
        ArrayHelper::prependOrAppend($array, $appendable, $prepend);
        self::assertSame($expected, $array);
    }

    /**
     *
     */
    public function testWhere(): void
    {
        $array = [
            [
                'name' => 'array 1',
                'description' => 'the first array',
            ],
            [
                'name' => 'array 2',
                'description' => 'the second array',
            ],
        ];

        $filtered = ArrayHelper::where($array, 'name', 'array 1');
        self::assertCount(1, $filtered);
        self::assertSame('the first array', $filtered[0]['description']);

        // Set the name to empty and see if we can filter by keys with an empty value
        $array[0]['name'] = '';
        $filtered = ArrayHelper::where($array, 'name', '');
        self::assertCount(1, $filtered);
        self::assertSame('the first array', $filtered[0]['description']);

        // Add a new key to the array that it empty and with an empty value. Make sure that when filtering empty by empty  it returns everything.
        $array[0][''] = '';
        $filtered = ArrayHelper::where($array, '', '');
        self::assertCount(count($array), $filtered);
        self::assertSame($array, $filtered);

        // Filter by emojis?
        $array[0]['😀'] = '😘';
        $filtered = ArrayHelper::where($array, '😀', '😘');
        self::assertCount(1, $filtered);
        self::assertSame('the first array', $filtered[0]['description']);

        // See if we can filter by an array as a value.
        self::assertSame([['name' => ['testname' => true]]],
            ArrayHelper::where(
                [
                    ['name' => ['testname' => true]],
                    ['name' => '22'],
                ],
                'name',
                ['testname' => true]
            ));

        // Strict will only return 1. Non strict will typecast integer to string and thus find 2.
        self::assertCount(2,
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
        self::assertCount(1,
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

        self::assertSame(
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

        self::assertSame(
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

        // keepKeys = false
        self::assertSame(
            [['name' => 'john']],
            ArrayHelper::where(
                [
                    'john' => ['name' => 'john'],
                    'michael' => ['name' => 'michael'],
                ],
                function($array) {
                    return $array['name'];
                },
                'john',
                true,
                false
            ));

        // Make sure that filter by value hasn't made any changes to the array content, etc.
        $mockedUp = [
            [
                'name' => '',
                'description' => 'the first array',
                '' => '',
                '😀' => '😘',

            ],
            [
                'name' => 'array 2',
                'description' => 'the second array',
            ],
        ];

        self::assertSame($array, $mockedUp);
    }

    /**
     * Test `whereIn()`
     */
    public function testWhereIn(): void
    {
        $array = [
            'foo' => [
                'type' => 'apple',
                'num' => '1',
            ],
            'bar' => [
                'type' => 'banana',
                'num' => '2',
            ],
            'baz' => [
                'type' => 'orange',
                'num' => '3',
            ],
        ];

        $filtered = ArrayHelper::whereIn($array, 'type', ['apple', 'banana', 'pickle']);
        self::assertCount(2, $filtered);
        self::assertSame(['foo', 'bar'], array_keys($filtered));

        $filtered = ArrayHelper::whereIn($array, 'num', [1, 2, 3], true);
        self::assertEmpty($filtered);

        $filtered = ArrayHelper::whereIn($array, 'num', [1, 2]);
        self::assertCount(2, $filtered);
        self::assertSame(['foo', 'bar'], array_keys($filtered));

        $filtered = ArrayHelper::whereIn($array, 'num', [1, 2], false, false);
        self::assertCount(2, $filtered);
        self::assertSame([0, 1], array_keys($filtered));
    }

    /**
     * Test `whereMultiple` func
     */
    public function testWhereMultiple(): void
    {
        $array = [
            [
                'name' => 'array 1',
                'description' => 'the first array',
                'handle' => 'foo',
            ],
            [
                'name' => 'array 2',
                'description' => 'the second array',
                'handle' => '88',
            ],
            [
                'name' => 'array 3',
                'description' => 'the third array',
                'handle' => 'bar',
                'arrayTest' => ['test' => 'me'],
            ],
            [
                'name' => 'array 4',
                'description' => '',
                'handle' => 'baz',
                '😀' => '😘',
            ],
        ];

        // Simple search
        $filtered = ArrayHelper::whereMultiple($array, ['name' => 'array 1']);
        self::assertCount(1, $filtered);
        self::assertSame('the first array', $filtered[0]['description']);

        // Search by empty property
        $filtered = ArrayHelper::whereMultiple($array, ['description' => ['']]);
        self::assertCount(1, $filtered);
        self::assertSame('baz', $filtered[3]['handle']);

        // Search with no condition
        $filtered = ArrayHelper::whereMultiple($array, ['name' => []]);
        self::assertCount(count($array), $filtered);
        self::assertSame($array, $filtered);

        // Filter by emojis?
        $filtered = ArrayHelper::whereMultiple($array, ['😀' => '😘']);
        self::assertCount(1, $filtered);
        self::assertSame('array 4', $filtered[3]['name']);

        // Find a non-strict match.
        $filtered = ArrayHelper::whereMultiple($array, ['handle' => 88]);
        self::assertCount(1, $filtered);
        self::assertSame('array 2', $filtered[1]['name']);

        // Fail to find a strict match
        $filtered = ArrayHelper::whereMultiple($array, ['handle' => 88], true);
        self::assertCount(0, $filtered);

        // Find multiple
        $filtered = ArrayHelper::whereMultiple($array, ['handle' => ['foo', 'bar', 'baz']]);
        self::assertCount(3, $filtered);
        self::assertSame('array 1', $filtered[0]['name']);
        self::assertSame('array 3', $filtered[2]['name']);

        // Find multiple and narrow down
        $filtered = ArrayHelper::whereMultiple($array, ['handle' => ['foo', 'bar', 'baz'], 'name' => 'array 4']);
        self::assertCount(1, $filtered);
        self::assertSame('array 4', $filtered[3]['name']);

        // Ensure that array element must match all conditions
        $filtered = ArrayHelper::whereMultiple($array, ['handle' => ['foo', 'bar', 'baz'], 'name' => ['array 4', 'array 2']]);
        self::assertCount(1, $filtered);
        self::assertSame('array 4', $filtered[3]['name']);

        // Find multiple and narrow down to multiple
        $filtered = ArrayHelper::whereMultiple($array, ['handle' => ['foo', 'bar', 'baz'], 'name' => ['array 4', 'array 3']]);
        self::assertCount(2, $filtered);
        self::assertSame('array 3', $filtered[2]['name']);

        // Wrong array syntax
        $filtered = ArrayHelper::whereMultiple($array, ['arrayTest' => ['test' => 'me']]);
        self::assertCount(0, $filtered);

        // Right array syntax
        $filtered = ArrayHelper::whereMultiple($array, ['arrayTest' => [['test' => 'me']]]);
        self::assertCount(1, $filtered);
        self::assertSame('array 3', $filtered[2]['name']);
    }

    /**
     * @dataProvider containsDataProvider
     * @param bool $expected
     * @param array $array
     * @param callable|string $key
     * @param mixed $value
     * @param bool $strict
     */
    public function testContains(bool $expected, array $array, callable|string $key, mixed $value = true, bool $strict = false): void
    {
        self::assertSame($expected, ArrayHelper::contains($array, $key, $value, $strict));
    }

    /**
     * @dataProvider onlyContainsDataProvider
     *
     * @param bool $expected
     * @param array $array
     * @param callable|string $key
     * @param mixed $value
     * @param bool $strict
     */
    public function testOnlyContains(bool $expected, array $array, callable|string $key, mixed $value = true, bool $strict = false): void
    {
        self::assertSame($expected, ArrayHelper::onlyContains($array, $key, $value, $strict));
    }

    /**
     *
     */
    public function testFilterEmptyStringsFromArray(): void
    {
        self::assertSame([0 => 1, 1 => 2, 4 => null, 5 => 5], ArrayHelper::filterEmptyStringsFromArray([0 => 1, 1 => 2, 3 => '', 4 => null, 5 => 5]));
    }

    /**
     *
     */
    public function testFirstKey(): void
    {
        self::assertNull(ArrayHelper::firstKey([]));
        self::assertEquals(0, ArrayHelper::firstKey([1]));
        self::assertEquals(5, ArrayHelper::firstKey([5 => 'value']));
        self::assertEquals('firstKey', ArrayHelper::firstKey(['firstKey' => 'firstValue', 'secondKey' => 'secondValue']));
    }

    /**
     * @dataProvider firstValueDataProvider
     * @param mixed $expected
     * @param array $array
     */
    public function testFirstValue(mixed $expected, array $array): void
    {
        self::assertSame($expected, ArrayHelper::firstValue($array));
    }

    /**
     * @dataProvider renameDataProvider
     * @param array $expected
     * @param array $array
     * @param string $oldKey
     * @param string $newKey
     * @param mixed $default
     */
    public function testRename(array $expected, array $array, string $oldKey, string $newKey, mixed $default = null): void
    {
        ArrayHelper::rename($array, $oldKey, $newKey, $default);
        self::assertSame($expected, $array);
    }

    /**
     * @dataProvider withoutDataProvider
     * @param array $expected
     * @param array $array
     * @param string $key
     */
    public function testWithout(array $expected, array $array, string $key): void
    {
        self::assertSame($expected, ArrayHelper::without($array, $key));
    }

    /**
     * @dataProvider withoutValueDataProvider
     * @param array $expected
     * @param array $array
     * @param mixed $value
     */
    public function testWithoutValue(array $expected, array $array, mixed $value): void
    {
        self::assertSame($expected, ArrayHelper::withoutValue($array, $value));
    }

    /**
     * @dataProvider ensureNonAssociativeDataProvider
     * @param array $expected
     * @param array $array
     */
    public function testEnsureNonAssociative(array $expected, array $array): void
    {
        ArrayHelper::ensureNonAssociative($array);
        self::assertSame($expected, $array);
    }

    /**
     * @dataProvider isOrderedDataProvider
     * @param bool $expected
     * @param array $array
     */
    public function testIsOrdered(bool $expected, array $array): void
    {
        self::assertSame($expected, ArrayHelper::isOrdered($array));
    }

    /**
     * @dataProvider isNumericDataProvider
     * @param bool $expected
     * @param array $array
     */
    public function testIsNumeric(bool $expected, array $array): void
    {
        self::assertSame($expected, ArrayHelper::isNumeric($array));
    }

    /**
     * @dataProvider getValueDataProvider
     * @param string $expected
     * @param array $array
     * @param string $key
     */
    public function testGetValue(string $expected, array $array, string $key): void
    {
        $this->assertSame($expected, ArrayHelper::getValue($array, $key));
    }

    /**
     * @return array
     */
    public function toArrayDataProvider(): array
    {
        $stdClass2 = new stdClass();
        $stdClass2->subProp = 'value';

        $stdClass = new stdClass();
        $stdClass->prop1 = '11';
        $stdClass->prop2 = '22';
        $stdClass->prop3 = $stdClass2;

        return [
            [[], null],
            [[], null], [[1, 2, 3], [1, 2, 3]],
            [['prop1' => '11', 'prop2' => '22', 'prop3' => ['subProp' => 'value']], $stdClass],
            [['foo', 'bar, baz'], 'foo, bar\, baz', ''],
        ];
    }

    /**
     * @return array
     */
    public function prependDataProvider(): array
    {
        return [
            [[1, 2, 3, 4], [3, 4], [1, 2]],
            [[1, 2, 3, 4], [1, 2, 3, 4], []],
        ];
    }

    /**
     * @return array
     */
    public function appendDataProvider(): array
    {
        return [
            [[1, 2, 3, 4], [1, 2], [3, 4]],
            [[1, 2, 3, 4], [1, 2, 3, 4], []],
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
    public function containsDataProvider(): array
    {
        return [
            [true, [['foo' => 1, 'bar' => 2]], 'foo'],
            [false, [['foo' => 1, 'bar' => 2]], 'foo', true, true],
        ];
    }

    /**
     * @return array
     */
    public function onlyContainsDataProvider(): array
    {
        return [
            [true, [['foo' => 1], ['foo' => 2]], 'foo'],
            [false, [['foo' => 1], ['bar' => 2]], 'foo'],
            [false, [['foo' => 1], ['foo' => 2]], 'foo', true, true],
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
            [$std, ['key' => $std]],
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
    public function withoutDataProvider(): array
    {
        return [
            [[], ['key' => 'value'], 'key'],
            [['key' => 'value'], ['key' => 'value', 'key2' => 'value2'], 'key2'],
            [['key' => 'value'], ['key' => 'value'], 'notakey'],
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

    /**
     * @return array
     */
    public function ensureNonAssociativeDataProvider(): array
    {
        return [
            [[1, 2, 3], ['a' => 1, 'b' => 2, 'c' => 3]],
        ];
    }

    /**
     * @return array
     */
    public function isOrderedDataProvider(): array
    {
        return [
            [true, ['a', 'b', 'c']],
            [true, [5 => 'a', 10 => 'b', 15 => 'c']],
            [false, ['a' => 1, 'b' => 2, 'c' => 3]],
            [false, ['a', 'b', 'c' => 3]],
            [false, [3 => 'a', 2 => 'b', 1 => 'c']],
        ];
    }

    /**
     * @return array
     */
    public function isNumericDataProvider(): array
    {
        return [
            [true, [0, 1, 2, '3']],
            [false, [0, 1, 2, '3a']],
        ];
    }

    public function getValueDataProvider(): array
    {
        return [
            ['foo', ['foo' => 'foo'], 'foo'],
            ['foo.bar', ['foo' => ['bar' => 'foo.bar']], 'foo[bar]'],
            ['foo.bar.baz', ['foo' => ['bar' => ['baz' => 'foo.bar.baz']]], 'foo[bar][baz]'],
            ['foo[bar', ['foo[bar' => 'foo[bar'], 'foo[bar'],
            ['foo[bar][]', ['foo[bar][]' => 'foo[bar][]'], 'foo[bar][]'],
            ['foo.bar:baz.qux', ['foo' => ['bar:baz' => ['qux' => 'foo.bar:baz.qux']]], 'foo[bar:baz][qux]'],
            ['foo-bar.baz.qux', ['foo-bar' => ['baz' => ['qux' => 'foo-bar.baz.qux']]], 'foo-bar[baz][qux]'],
        ];
    }
}
