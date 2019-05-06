<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\Image;
use UnitTester;

/**
 * Class ImageHelperTest.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class ImageHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testConstants()
    {
        $this->assertSame(3, Image::EXIF_IFD0_ROTATE_180);
        $this->assertSame(6, Image::EXIF_IFD0_ROTATE_90);
        $this->assertSame(8, Image::EXIF_IFD0_ROTATE_270);
    }

    /**
     * @dataProvider calculateMissingImensionData
     * @param $result
     * @param $targetWidth
     * @param $targetHeight
     * @param $sourceWidth
     * @param $sourceHeight
     */
    public function testCalculateMissingDimension($result, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight)
    {
        $calculate = Image::calculateMissingDimension($targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        $this->assertSame($result, $calculate);
    }
    public function calculateMissingImensionData(): array
    {
        return [
            [[1, 1], 1, 1, 1, 1],
            [[10, 2], 10, 2, 4, 2],
            [[4, 2], 0, 2, 4, 2],
            [[2, 1], 2, 0, 4, 2],
            [[0, 0], 0, 0, 4.2891, 2.12321],
            [[28971, 14342], 28971.251, 0, 4.2891, 2.12321],
            [[2491031, 1233121], 0, 1233121.123213, 4.2891, 2.12321],
            [[12, 1233121], 12.12, 1233121.123213, 0, 4324],
        ];
    }

    /**
     * @dataProvider canManipulateAsImageData
     * @param $result
     * @param $input
     */
    public function testCanManipulateAsImage($result, $input)
    {
        $canManipulate = Image::canManipulateAsImage($input);
        $this->assertSame($result, $canManipulate);
    }
    public function canManipulateAsImageData(): array
    {
        return [
            [true, 'jpg'],
            [true, 'jpeg'],
            [true, 'gif'],
            [true, 'png'],
            [true, 'svg'],
            [true, 'SVG'],
            [false, '.SVG'],
            [false, 'stuffsvg'],
            [false, 'pdf'],
            [false, 'json'],
            [false, 'html'],
            [false, 'htm']
        ];
    }

    public function testWebSafeFormats()
    {
        $this->assertSame(['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'], Image::webSafeFormats());
    }

    /**
     * @dataProvider pngImageInfoData
     * @param $result
     * @param $input
     */
    public function testPngImageInfo($result, $input)
    {
        $imageInfo = Image::pngImageInfo($input);
        $this->assertSame($result, $imageInfo);
    }
    public function pngImageInfoData(): array
    {
        return [
            [[
                'width' => 200,
                'height' => 200,
                'bit-depth' => 8,
                'color' => 2,
                'compression' => 0,
                'filter' => 0,
                'interface' => 0,
                'color-type' => 'Truecolour',
                'channels' => 3
            ], dirname(__FILE__, 3).'\_data\assets\files\google.png'],
            [false, dirname(__FILE__, 3).'\_data\assets\files\no-ihdr.png'],
            [false, dirname(__FILE__, 3).'\_data\assets\files\invalid-ihdr.png'],
            [false, ''],
            [false, dirname(__FILE__, 3).'\_data\assets\files\ign.jpg'],
            // TODO: Test empty unpack() function  and invalid IHDR chunks and INVALID color value. See coverage for more.
        ];
    }

    /**
     * @dataProvider canHaveExitData
     * @param $result
     * @param $input
     */
    public function testCanHaveExifData($result, $input)
    {
        $canHavExit = Image::canHaveExifData($input);
        $this->assertSame($result, $canHavExit);
    }
    public function canHaveExitData(): array
    {
        return [
            [true, dirname(__FILE__, 3).'\_data\assets\files\background.jpg'],
            [true, dirname(__FILE__, 3).'\_data\assets\files\background.jpeg'],
            [true, dirname(__FILE__, 3).'\_data\assets\files\random.tiff'],

            [false, dirname(__FILE__, 3).'\_data\assets\files\random.tif'],
            [false, dirname(__FILE__, 3).'\_data\assets\files\empty-file.text'],
            [false, dirname(__FILE__, 3).'\_data\assets\files\google.png'],
        ];
    }

    /**
     * @dataProvider imageSizeData
     * @param $result
     * @param $input
     */
    public function testImageSize($result, $input)
    {
        $imageSize = Image::imageSize($input);
        $this->assertSame($result, $imageSize);
    }
    public function imageSizeData(): array
    {
        return [
            [[960, 640], dirname(__FILE__, 3).'\_data\assets\files\background.jpg'],
            [[200, 200], dirname(__FILE__, 3).'\_data\assets\files\google.png'],
            [[0, 0], dirname(__FILE__, 3).'\_data\assets\files\random.tiff'],
            [[100.0, 100.0], dirname(__FILE__, 3).'\_data\assets\files\gng.svg'],
        ];
    }

    /**
     * @dataProvider parseSvgData
     * @param $result
     * @param $input
     */
    public function testParseSvgImageSize($result, $input)
    {
        $parsed = Image::parseSvgSize($input);
        $this->assertSame($result, $parsed);
    }
    public function parseSvgData(): array
    {
        return [
            [[140.0, 41.0], file_get_contents(dirname(__FILE__, 3).'\_data\assets\files\craft-logo.svg')],
            [[100.0, 100.0], file_get_contents(dirname(__FILE__, 3).'\_data\assets\files\gng.svg')],

            // This svg is same as craft-logo but we removed viewbox="" and height=""/width="" so it returns 100.0 100.0 instead of 140.0 41.0
            [[100, 100], file_get_contents(dirname(__FILE__, 3).'\_data\assets\files\no-dimension-svg.svg')],
            [[100, 100], file_get_contents(dirname(__FILE__, 3).'\_data\assets\files\google.png')],
        ];
    }

    /**
     * @dataProvider imageByStreamData
     * @param $result
     * @param $input
     */
    public function testImageByStream($result, $input)
    {
        $stream = Image::imageSizeByStream($input);
        $this->assertSame($result, $stream);
    }
    public function imageByStreamData(): array
    {
        return [
            [[400, 300], fopen(dirname(__FILE__, 3).'\_data\assets\files\example-gif.gif', 'rb')],
            [[960, 640], fopen(dirname(__FILE__, 3).'\_data\assets\files\background.jpg', 'rb')],
            [[200, 200], fopen(dirname(__FILE__, 3).'\_data\assets\files\google.png', 'rb')],
            [false, fopen(dirname(__FILE__, 3).'\_data\assets\files\craft-logo.svg', 'rb')],

            // TODO: Generate a bunch of invalid image formats that generate exceptions.

        ];
    }
}