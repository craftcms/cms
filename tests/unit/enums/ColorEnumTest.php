<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\enums;

use Codeception\Test\Unit;
use craft\enums\Color;
use craft\helpers\Diff;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for the Diff Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class ColorEnumTest extends TestCase
{
    /**
     * @dataProvider getCssVarDataProvider
     * @param string|false $expected
     * @param string $color
     * @param int $shade
     */
    public function testCssVar(string|false $expected, string $color, int $shade): void
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
            Color::from($color)->cssVar($shade);
        }

        self::assertSame($expected, Color::from($color)->cssVar($shade));
    }

    /**
     * @return array
     */
    public static function getCssVarDataProvider(): array
    {
        return [
            ['var(--red-050)', 'red', 50],
            ['var(--red-100)', 'red', 100],
            ['var(--red-500)', 'red', 500],
            ['var(--red-900)', 'red', 900],
            ['var(--white)', 'white', 500],
            ['var(--gray)', 'gray', 500],
            ['var(--black)', 'black', 500],
            [false, 'red', 0],
            [false, 'red', 49],
            [false, 'red', 99],
            [false, 'red', 101],
            [false, 'red', 901],
            [false, 'red', 1000],
        ];
    }
}
