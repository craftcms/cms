<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Arr;
use craft\test\TestCase;
use stdClass;

/**
 * Unit tests for the Array Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 5.x
 */
class ArrTest extends TestCase
{
    public function testMerge(): void
    {
        $a = [
            'name' => 'Yii',
            'version' => '1.0',
            'options' => [
                'namespace' => false,
                'unittest' => false,
            ],
            'features' => [
                'mvc',
            ],
        ];
        $b = [
            'version' => '1.1',
            'options' => [
                'unittest' => true,
            ],
            'features' => [
                'gii',
            ],
        ];
        $c = [
            'version' => '2.0',
            'options' => [
                'namespace' => true,
            ],
            'features' => [
                'debug',
            ],
            'foo',
        ];

        $result = Arr::merge($a, $b, $c);
        $expected = [
            'name' => 'Yii',
            'version' => '2.0',
            'options' => [
                'namespace' => true,
                'unittest' => true,
            ],
            'features' => [
                'mvc',
                'gii',
                'debug',
            ],
            'foo',
        ];

        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testWhereNotEmpty(): void
    {
        self::assertSame([0 => 1, 1 => 2, 4 => null, 5 => 5], Arr::whereNotEmpty([0 => 1, 1 => 2, 3 => '', 4 => null, 5 => 5]));
    }

    /**
     * @dataProvider firstDataProvider
     * @param mixed $expected
     * @param array $array
     */
    public function testFirst(mixed $expected, array $array): void
    {
        self::assertSame($expected, Arr::first($array));
    }

    /**
     * @dataProvider exceptDataProvider
     * @param array $expected
     * @param array $array
     * @param string $key
     */
    public function testExcept(array $expected, array $array, string $key): void
    {
        self::assertSame($expected, Arr::except($array, $key));
    }

    /**
     * @dataProvider getDataProvider
     * @param string $expected
     * @param array $array
     * @param string $key
     */
    public function testGet(string $expected, array $array, string $key): void
    {
        self::assertSame($expected, Arr::get($array, $key));
    }

    /**
     * @dataProvider isOrderedDataProvider
     * @param bool $expected
     * @param array $array
     */
    public function testIsOrdered(bool $expected, array $array): void
    {
        self::assertSame($expected, Arr::isOrdered($array));
    }

    /**
     * @dataProvider isNumericDataProvider
     * @param bool $expected
     * @param array $array
     */
    public function testIsNumeric(bool $expected, array $array): void
    {
        self::assertSame($expected, Arr::isNumeric($array));
    }

    /**
     * @dataProvider isIndexedDataProvider
     * @param bool $expected
     * @param array $array
     */
    public function testIsIndexed(bool $expected, array $array): void
    {
        self::assertSame($expected, Arr::isIndexed($array));
    }

    /**
     * @return array
     */
    public static function firstDataProvider(): array
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
    public static function renameDataProvider(): array
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
    public static function exceptDataProvider(): array
    {
        return [
            [[], ['key' => 'value'], 'key'],
            [['key' => 'value'], ['key' => 'value', 'key2' => 'value2'], 'key2'],
            [['key' => 'value'], ['key' => 'value'], 'notakey'],
        ];
    }

    public static function getDataProvider(): array
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

    /**
     * @return array
     */
    public static function isOrderedDataProvider(): array
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
    public static function isNumericDataProvider(): array
    {
        return [
            [true, [0, 1, 2, '3']],
            [false, [0, 1, 2, '3a']],
        ];
    }

    /**
     * @return array
     */
    public static function isIndexedDataProvider(): array
    {
        return [
            [true, [0, 1, 2]],
            [false, [0, 1, 2, '3']],
            [false, [0, 1, 2, '3a']],
        ];
    }
}
