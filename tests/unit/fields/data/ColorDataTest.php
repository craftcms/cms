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
        self::assertSame("rgba($r,$g,$b,1)", $color->getRgba());
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
        self::assertSame("hsla($h,$s%,$l%,1)", $color->getHsla());
    }

    /**
     * @dataProvider alphaDataProvider
     * @param float $alpha
     * @param string $hex
     * @param string $opaqueHex
     */
    public function testAlpha(float $alpha, string $hex, string $opaqueHex): void
    {
        $color = new ColorData($hex);
        self::assertSame($alpha, $color->getAlpha());
        self::assertSame($hex, $color->getHex());
        self::assertSame($opaqueHex, $color->getHex(false));
        self::assertSame("rgb(255,128,0)", $color->getRgb());
        self::assertSame("rgba(255,128,0,$alpha)", $color->getRgba());
        self::assertSame("hsla(30,100%,50%,$alpha)", $color->getHsla());
    }

    public static function alphaDataProvider(): array
    {
        return [
            [1.0, '#ff8000', '#ff8000'],
            [0.0, '#ff800000', '#ff8000'],
            [0.5, '#ff800080', '#ff8000'],
            [0.25, '#ff800040', '#ff8000'],
            [0.2, '#ff800033', '#ff8000'],
            // 0x81 / 255 = 0.50588…, and 0.51 would round back to 0x82
            [0.506, '#ff800081', '#ff8000'],
        ];
    }

    public static function rgbDataProvider(): array
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

    public static function hslDataProvider(): array
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
