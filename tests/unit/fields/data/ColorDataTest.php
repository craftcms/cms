<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields\data;

use Codeception\Test\Unit;
use craft\fields\data\ColorData;
use craft\test\TestCase;

/**
 * Unit tests for the ColorData class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.26
 */
class ColorDataTest extends TestCase
{
    /**
     * @dataProvider rgbDataProvider
     * @param int $r
     * @param int $g
     * @param int $b
     * @param string $hex
     */
    public function testRgb(int $r, int $g, int $b, string $hex): void
    {
        $color = new ColorData($hex);
        self::assertSame($r, $color->getRed());
        self::assertSame($g, $color->getGreen());
        self::assertSame($b, $color->getBlue());
        self::assertSame($r, $color->getR());
        self::assertSame($g, $color->getG());
        self::assertSame($b, $color->getB());
        self::assertSame("rgb($r,$g,$b)", $color->getRgb());
    }

    /**
     * @dataProvider hslDataProvider
     * @param int $h
     * @param int $s
     * @param int $l
     * @param string $hex
     */
    public function testHsl(int $h, int $s, int $l, string $hex): void
    {
        $color = new ColorData($hex);
        self::assertSame($h, $color->getHue());
        self::assertSame($s, $color->getSaturation());
        self::assertSame($l, $color->getLightness());
        self::assertSame($h, $color->getH());
        self::assertSame($s, $color->getS());
        self::assertSame($l, $color->getL());
        self::assertSame("hsl($h,$s%,$l%)", $color->getHsl());
    }

    public function rgbDataProvider(): array
    {
        return [
            [0, 0, 0, '#000000'],
            [255, 255, 255, '#ffffff'],
            [255, 0, 0, '#ff0000'],
            [0, 255, 0, '#00ff00'],
            [0, 0, 255, '#0000ff'],
            [229, 66, 43, '#E5422B'],
        ];
    }

    public function hslDataProvider(): array
    {
        return [
            [0, 0, 0, '#000000'],
            [0, 0, 100, '#ffffff'],
            [0, 100, 50, '#ff0000'],
            [120, 100, 50, '#00ff00'],
            [240, 100, 50, '#0000ff'],
            [7, 78, 53, '#E5422B'],
            [34, 94, 75, '#fbc884'],
        ];
    }
}
