<?php /** @noinspection PhpParamsInspection */

/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Stub;
use Codeception\Test\Unit;
use Craft;
use craft\helpers\Image;
use Exception;
use TypeError;
use UnitTester;
use yii\log\Logger;

/**
 * Class ImageHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ImageHelperTest extends Unit
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
     *
     */
    public function testConstants()
    {
        $this->assertSame(3, Image::EXIF_IFD0_ROTATE_180);
        $this->assertSame(6, Image::EXIF_IFD0_ROTATE_90);
        $this->assertSame(8, Image::EXIF_IFD0_ROTATE_270);
    }

    /**
     * @dataProvider calculateMissingDimensionDataProvider
     *
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

    /**
     * @dataProvider canManipulateAsImageDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testCanManipulateAsImage($result, $input)
    {
        $canManipulate = Image::canManipulateAsImage($input);
        $this->assertSame($result, $canManipulate);
    }

    /**
     *
     */
    public function testWebSafeFormats()
    {
        $this->assertSame(['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'], Image::webSafeFormats());
    }

    /**
     * @dataProvider pngImageInfoDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testPngImageInfo($result, $input)
    {
        $imageInfo = Image::pngImageInfo($input);
        $this->assertSame($result, $imageInfo);
    }

    /**
     * @dataProvider canHaveExitDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testCanHaveExifData($result, $input)
    {
        $canHavExit = Image::canHaveExifData($input);
        $this->assertSame($result, $canHavExit);
    }

    /**
     * @dataProvider imageSizeDataProvider
     *
     * @param array $result
     * @param string $input
     * @param bool $skipIfGd
     */
    public function testImageSize($result, $input, $skipIfGd)
    {
        if ($skipIfGd && Craft::$app->getImages()->getIsGd()) {
            $this->markTestSkipped('Need Imagick to test this function.');
        }

        $imageSize = Image::imageSize($input);
        $this->assertSame($result, $imageSize);
    }

    /**
     * @dataProvider parseSvgDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testParseSvgImageSize($result, $input)
    {
        $parsed = Image::parseSvgSize($input);
        $this->assertSame($result, $parsed);
    }

    /**
     * @dataProvider imageByStreamDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testImageByStream($result, $input)
    {
        $stream = Image::imageSizeByStream($input);
        $this->assertSame($result, $stream);
    }

    /**
     *
     */
    public function testNoResourceImageByStreamExceptions()
    {
        $this->tester->expectThrowable(TypeError::class, function() {
            $db_link = @mysqli_connect('localhost', 'not_a_user', 'not_a_pass');
            /** @noinspection PhpParamsInspection */
            Image::imageSizeByStream($db_link);
        });
    }

    /**
     * @dataProvider exceptionTriggeringImageByStreamDataProvider
     *
     * @param $errorLogMessage
     * @param $input
     * @throws Exception
     */
    public function testImageByStreamException($errorLogMessage, $input)
    {
        Craft::setLogger(
            Stub::make(Logger::class, [
                'log' => function($message) use ($errorLogMessage) {
                    $this->assertSame($errorLogMessage, $message);
                }
            ])
        );

        $result = Image::imageSizeByStream($input);
        $this->assertSame([], $result);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function imageByStreamDataProvider(): array
    {
        $dirnameFile3 = dirname(__FILE__, 3);

        return [
            [[400, 300], fopen($dirnameFile3 . '/_data/assets/files/example-gif.gif', 'rb')],
            [[960, 640], fopen($dirnameFile3 . '/_data/assets/files/background.jpg', 'rb')],
            [[200, 200], fopen($dirnameFile3 . '/_data/assets/files/google.png', 'rb')],
            [false, fopen($dirnameFile3 . '/_data/assets/files/craft-logo.svg', 'rb')],
        ];
    }

    /**
     * @return array
     */
    public function exceptionTriggeringImageByStreamDataProvider(): array
    {
        $dirnameFile3 = dirname(__FILE__, 3);

        return [
            ['Unrecognized JPG file structure.', fopen($dirnameFile3 . '/_data/assets/files/broken-jpg-structure.jpg', 'rb')],
            ['Unrecognized image signature.', fopen($dirnameFile3 . '/_data/assets/files/broken-gif-signature.gif', 'rb')],
            ['Unrecognized image signature.', fopen($dirnameFile3 . '/_data/assets/files/broken-png-signature.png', 'rb')],
            ['Unrecognized PNG file structure.', fopen($dirnameFile3 . '/_data/assets/files/invalid-ihdr.png', 'rb')],
        ];
    }

    /**
     * @return array
     */
    public function parseSvgDataProvider(): array
    {
        return [
            [[140.0, 41.0], file_get_contents(dirname(__FILE__, 3) . '/_data/assets/files/craft-logo.svg')],
            [[100.0, 100.0], file_get_contents(dirname(__FILE__, 3) . '/_data/assets/files/gng.svg')],

            // This svg is same as craft-logo but we removed viewbox="" and height=""/width="" so it returns 100.0 100.0 instead of 140.0 41.0
            [[100, 100], file_get_contents(dirname(__FILE__, 3) . '/_data/assets/files/no-dimension-svg.svg')],
            [[100, 100], file_get_contents(dirname(__FILE__, 3) . '/_data/assets/files/google.png')],
        ];
    }

    /**
     * @return array
     */
    public function imageSizeDataProvider(): array
    {
        return [
            [[960, 640], dirname(__FILE__, 3) . '/_data/assets/files/background.jpg', false],
            [[200, 200], dirname(__FILE__, 3) . '/_data/assets/files/google.png', false],
            [[1728, 2376], dirname(__FILE__, 3) . '/_data/assets/files/random.tiff', true],
            [[100.0, 100.0], dirname(__FILE__, 3) . '/_data/assets/files/gng.svg', false],
        ];
    }

    /**
     * @return array
     */
    public function canHaveExitDataProvider(): array
    {
        return [
            [true, dirname(__FILE__, 3) . '/_data/assets/files/background.jpg'],
            [true, dirname(__FILE__, 3) . '/_data/assets/files/background.jpeg'],
            [true, dirname(__FILE__, 3) . '/_data/assets/files/random.tiff'],

            [false, dirname(__FILE__, 3) . '/_data/assets/files/random.tif'],
            [false, dirname(__FILE__, 3) . '/_data/assets/files/empty-file.text'],
            [false, dirname(__FILE__, 3) . '/_data/assets/files/google.png'],
        ];
    }

    /**
     * @return array
     * @todo Test empty unpack() function and invalid IHDR chunks and INVALID color value. See coverage for more.
     */
    public function pngImageInfoDataProvider(): array
    {
        return [
            [
                [
                    'width' => 200,
                    'height' => 200,
                    'bit-depth' => 8,
                    'color' => 2,
                    'compression' => 0,
                    'filter' => 0,
                    'interface' => 0,
                    'color-type' => 'Truecolour',
                    'channels' => 3
                ], dirname(__FILE__, 3) . '/_data/assets/files/google.png'
            ],
            [false, dirname(__FILE__, 3) . '/_data/assets/files/no-ihdr.png'],
            [false, dirname(__FILE__, 3) . '/_data/assets/files/invalid-ihdr.png'],
            [false, ''],
            [false, dirname(__FILE__, 3) . '/_data/assets/files/ign.jpg'],
        ];
    }

    /**
     * @return array
     */
    public function calculateMissingDimensionDataProvider(): array
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
     * @return array
     */
    public function canManipulateAsImageDataProvider(): array
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
}
