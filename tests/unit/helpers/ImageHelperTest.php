<?php

/** @noinspection PhpParamsInspection */

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Stub;
use Craft;
use craft\helpers\Image;
use craft\test\TestCase;
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
class ImageHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     *
     */
    public function testConstants(): void
    {
        self::assertSame(3, Image::EXIF_IFD0_ROTATE_180);
        self::assertSame(6, Image::EXIF_IFD0_ROTATE_90);
        self::assertSame(8, Image::EXIF_IFD0_ROTATE_270);
    }

    /**
     * @dataProvider calculateMissingDimensionDataProvider
     * @param int[] $expected
     * @param int|float|null $targetWidth
     * @param int|float|null $targetHeight
     * @param int|float $sourceWidth
     * @param int|float $sourceHeight
     */
    public function testCalculateMissingDimension(array $expected, float|int|null $targetWidth, float|int|null $targetHeight, float|int $sourceWidth, float|int $sourceHeight): void
    {
        self::assertSame($expected, Image::calculateMissingDimension($targetWidth, $targetHeight, $sourceWidth, $sourceHeight));
    }

    /**
     * @dataProvider canManipulateAsImageDataProvider
     * @param bool $expected
     * @param string $extension
     */
    public function testCanManipulateAsImage(bool $expected, string $extension): void
    {
        self::assertSame($expected, Image::canManipulateAsImage($extension));
    }

    /**
     *
     */
    public function testWebSafeFormats(): void
    {
        self::assertSame(['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp', 'avif'], Image::webSafeFormats());
    }

    /**
     * @dataProvider pngImageInfoDataProvider
     * @param array|false $expected
     * @param string $file
     */
    public function testPngImageInfo(array|false $expected, string $file): void
    {
        self::assertSame($expected, Image::pngImageInfo($file));
    }

    /**
     * @dataProvider canHaveExitDataProvider
     * @param bool $expected
     * @param string $filePath
     */
    public function testCanHaveExifData(bool $expected, string $filePath): void
    {
        self::assertSame($expected, Image::canHaveExifData($filePath));
    }

    /**
     * @dataProvider imageSizeDataProvider
     * @param array $expected
     * @param string $filePath
     * @param bool $skipIfGd
     */
    public function testImageSize(array $expected, string $filePath, bool $skipIfGd): void
    {
        if ($skipIfGd && Craft::$app->getImages()->getIsGd()) {
            $this->markTestSkipped('Need Imagick to test this function.');
        }

        self::assertSame($expected, Image::imageSize($filePath));
    }

    /**
     * @dataProvider parseSvgSizeProvider
     * @param array $expected
     * @param string $svg
     */
    public function testParseSvgSize(array $expected, string $svg): void
    {
        self::assertSame($expected, Image::parseSvgSize($svg));
    }

    /**
     * @dataProvider imageSizeByStreamDataProvider
     * @param array|false $expected
     * @param resource $stream
     */
    public function testImageSizeByStream(array|false $expected, $stream): void
    {
        self::assertSame($expected, Image::imageSizeByStream($stream));
    }

    /**
     *
     */
    public function testNoResourceImageByStreamExceptions(): void
    {
        $this->tester->expectThrowable(TypeError::class, function() {
            /** @phpstan-ignore-next-line */
            Image::imageSizeByStream(1);
        });
    }

    /**
     * @dataProvider exceptionTriggeringImageByStreamDataProvider
     * @param string $errorLogMessage
     * @param resource $input
     * @throws Exception
     */
    public function testImageByStreamException(string $errorLogMessage, $input): void
    {
        Craft::setLogger(
            Stub::make(Logger::class, [
                'log' => function($message) use ($errorLogMessage) {
                    self::assertSame($errorLogMessage, $message);
                },
            ])
        );

        $result = Image::imageSizeByStream($input);
        self::assertSame([], $result);
    }

    /**
     * @return array
     */
    public function imageSizeByStreamDataProvider(): array
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
    public function parseSvgSizeProvider(): array
    {
        return [
            [[140, 41], file_get_contents(dirname(__FILE__, 3) . '/_data/assets/files/craft-logo.svg')],
            [[100, 100], file_get_contents(dirname(__FILE__, 3) . '/_data/assets/files/gng.svg')],

            // This svg is same as craft-logo but we removed viewbox="" and height=""/width="" so it returns 100 100 instead of 140 41
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
            [[100, 100], dirname(__FILE__, 3) . '/_data/assets/files/gng.svg', false],
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
                    'channels' => 3,
                ], dirname(__FILE__, 3) . '/_data/assets/files/google.png',
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
            [[4, 2], 0, 0, 4.2891, 2.12321],
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
            [false, 'htm'],
        ];
    }
}
