<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\Number;
use craft\test\mockclasses\ToString;
use craft\test\TestCase;

/**
 * Class NumberHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class NumberHelperTest extends TestCase
{
    /**
     * @dataProvider wordDataProvider
     * @param string $expected
     * @param int $num
     */
    public function testWord(string $expected, int $num): void
    {
        self::assertSame($expected, Number::word($num));
    }

    /**
     * @dataProvider upperAlphaDataProvider
     * @param string $expected
     * @param int $num
     */
    public function testUpperAlpha(string $expected, int $num): void
    {
        self::assertSame($expected, Number::upperAlpha($num));
    }

    /**
     * @dataProvider lowerAlphaDataProvider
     * @param string $expected
     * @param int $num
     */
    public function testLowerAlpha(string $expected, int $num): void
    {
        self::assertSame($expected, Number::lowerAlpha($num));
    }

    /**
     * @dataProvider upperRomanDataProvider
     * @param string $expected
     * @param int $num
     */
    public function testUpperRoman(string $expected, int $num): void
    {
        self::assertSame($expected, Number::upperRoman($num));
    }

    /**
     * @dataProvider lowerRomanDataProvider
     * @param string $expected
     * @param int $num
     */
    public function testLowerRoman(string $expected, int $num): void
    {
        self::assertSame($expected, Number::lowerRoman($num));
    }

    /**
     * @dataProvider makeNumericDataProvider
     * @param mixed $expected
     * @param mixed $var
     */
    public function testMakeNumeric(mixed $expected, mixed $var): void
    {
        self::assertSame($expected, Number::makeNumeric($var));
    }

    /**
     * @dataProvider isIntOrFloatDataProvider
     * @param bool $expected
     * @param mixed $value
     */
    public function testIsIntOrFloat(bool $expected, mixed $value): void
    {
        self::assertSame($expected, Number::isIntOrFloat($value));
    }

    /**
     * @return array
     */
    public static function makeNumericDataProvider(): array
    {
        $toStringClass = new ToString('50');

        return [
            [0, false],
            [1, true],
            ['1000', '1000'],
            ['50', $toStringClass],
            [1, 'five'],
            [1, [false]],
            [0, []],
        ];
    }

    /**
     * @return array
     */
    public static function lowerRomanDataProvider(): array
    {
        return [
            ['ii', 2],
            ['', 0],
            ['l', 50],
            ['mi', 1001],
        ];
    }

    /**
     * @return array
     */
    public static function upperRomanDataProvider(): array
    {
        return [
            ['II', 2],
            ['', 0],
            ['L', 50],
            ['MI', 1001],
        ];
    }

    /**
     * @return array
     */
    public static function wordDataProvider(): array
    {
        return [
            ['22', 22],
            ['two', 2],
            ['0', 0],
            ['10', 10],
            ['nine', 9],
        ];
    }

    /**
     * @return array
     */
    public static function upperAlphaDataProvider(): array
    {
        return [
            ['W', 23],
            ['A', 1],
            ['Z', 26],
            ['', 0],
            ['BC', 55],
            ['FHIM', 111111],
        ];
    }

    /**
     * @return array
     */
    public static function lowerAlphaDataProvider(): array
    {
        return [
            ['w', 23],
            ['a', 1],
            ['z', 26],
            ['', 0],
            ['bc', 55],
            ['fhim', 111111],
        ];
    }

    /**
     * @return array
     */
    public static function isIntOrFloatDataProvider(): array
    {
        return [
            [true, 0],
            [true, 0.5],
            [true, 10],
            [true, 10.5],
            [true, '0'],
            [true, '0.5'],
            [true, '0.50'],
            [true, '10'],
            [true, '10.5'],
            [false, '00'],
            [false, ' 0'],
            [false, '00.5'],
            [false, ' 0.5'],
            [false, ' '],
            [false, 'y'],
            [false, true],
            [false, []],
        ];
    }
}
