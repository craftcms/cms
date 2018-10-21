<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\Image;

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
    public function calculateMissingImensionData()
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
    public function canManipulateAsImageData()
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

    }
    public function pngImageInfoData()
    {
        return [

        ];
    }
}