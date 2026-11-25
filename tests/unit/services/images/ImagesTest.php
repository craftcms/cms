<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services\images;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Image as ImageHelper;
use craft\helpers\StringHelper;
use craft\image\Svg;
use craft\services\Images;
use craft\test\TestCase;
use Imagick;
use yii\base\Exception;

/**
 * Unit tests for images service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ImagesTest extends TestCase
{
    /**
     * @var Images
     */
    protected Images $images;

    /**
     * @var string
     */
    protected string $path;

    /**
     * @var string
     */
    protected string $sandboxPath;

    /**
     * @dataProvider checkMemoryForImageDataProvider
     * @param bool $expected
     * @param string $filePath
     */
    public function testCheckMemoryForImage(bool $expected, string $filePath): void
    {
        self::assertSame($expected, $this->images->checkMemoryForImage($this->path . $filePath));
    }

    /**
     *
     */
    public function testLoadImageSvg(): void
    {
        $path = $this->path . 'svg-pcts.svg';
        /** @var Svg $image */
        $image = $this->images->loadImage($path);
        $this->assertSame(4167, $image->getWidth());
        $this->assertSame(4167, $image->getHeight());
        $this->assertSame(
            str_replace('width="100%" height="100%"', 'width="4167px" height="4167px"', file_get_contents($path)),
            $image->getSvgString(),
        );
    }

    /**
     * @throws Exception
     */
    public function testCleanImageSvg(): void
    {
        $this->images->cleanImage(
            $this->sandboxPath . 'dirty-svg.svg'
        );

        $contents = file_get_contents($this->sandboxPath . 'dirty-svg.svg');

        self::assertFalse(
            StringHelper::contains($contents, '<script>')
        );
        self::assertFalse(
            StringHelper::contains($contents, '<this>')
        );
    }

    /**
     * @throws Exception
     */
    public function testDontCleanWithConfigSetting(): void
    {
        Craft::$app->getConfig()->getGeneral()->sanitizeSvgUploads = false;

        $this->images->cleanImage(
            $this->sandboxPath . 'dirty-svg.svg'
        );

        $contents = file_get_contents($this->sandboxPath . 'dirty-svg.svg');

        self::assertTrue(
            StringHelper::contains($contents, '<script>')
        );
        self::assertTrue(
            StringHelper::contains($contents, '<this>')
        );
    }

    /**
     *
     */
    public function testRotateImageByExifData(): void
    {
        $this->_skipIfNoImagick();

        $this->images->cleanImage($this->sandboxPath . 'image-rotated-180.jpg');
        $image = new Imagick($this->sandboxPath . 'image-rotated-180.jpg');
        self::assertSame(0, $image->getImageOrientation());
    }

    /**
     * @throws Exception
     */
    public function testCleanImageRotatesOrientation(): void
    {
        $this->_skipIfNoImagick();

        $this->images->cleanImage($this->sandboxPath . 'image-rotated-180.jpg');
        $currentExif = $this->images->getExifData($this->sandboxPath . 'image-rotated-180.jpg');
        self::assertArrayNotHasKey('ifd0.Orientation', $currentExif);
    }

    /**
     * @throws Exception
     */
    public function testCleanImageKeepsIndexedPngPixels(): void
    {
        $this->_skipIfNoImagick();

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('Need GD to create the indexed PNG.');
        }

        $path = $this->sandboxPath . 'indexed.png';
        $image = imagecreate(200, 50);
        for ($x = 0; $x < 200; $x++) {
            imageline($image, $x, 0, $x, 49, imagecolorallocate($image, $x, 255 - $x, intdiv($x, 2)));
        }
        imagepng($image, $path);
        $source = new Imagick($path);

        $this->images->cleanImage($path);
        $this->images->cleanImage($path);

        $cleaned = new Imagick($path);

        self::assertSame(3, ord(file_get_contents($path, false, null, 25, 1)));
        self::assertSame(200, $source->getImageColors());
        self::assertSame(200, $cleaned->getImageColors());
        self::assertSame($source->getImageSignature(), $cleaned->getImageSignature());
    }

    /**
     * @throws Exception
     */
    public function testCleanImageKeepsGreyscalePngPixels(): void
    {
        $this->_skipIfNoImagick();

        $path = $this->sandboxPath . 'greyscale.png';
        $height = 1000;
        $pixels = '';
        for ($y = 0; $y < $height; $y++) {
            $pixels .= "\0" . pack('n', $y * 65);
        }
        $chunk = fn(string $type, string $data) => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        file_put_contents($path, "\x89PNG\r\n\x1a\n" .
            $chunk('IHDR', pack('NNCCCCC', 1, $height, 16, 0, 0, 0, 0)) .
            $chunk('IDAT', gzcompress($pixels)) .
            $chunk('IEND', ''));
        $source = new Imagick($path);

        $this->images->cleanImage($path);
        $this->images->cleanImage($path);

        $cleaned = new Imagick($path);

        $pngInfo = ImageHelper::pngImageInfo($path);
        $levels = fn(Imagick $image) => $image->exportImagePixels(0, 0, 1, $height, 'I', Imagick::PIXEL_SHORT);

        self::assertSame(16, $pngInfo['bit-depth']);
        self::assertContains($pngInfo['color'], [0, 4]);
        self::assertSame($height, $source->getImageColors());
        self::assertSame($levels($source), $levels($cleaned));
    }

    /**
     * Tests respect for the transformGifs config setting.
     *
     * @throws Exception
     */
    public function testCleanImageDoesntDoGifWhenSettingDisabled(): void
    {
        $this->_skipIfNoImagick();

        Craft::$app->getConfig()->getGeneral()->transformGifs = false;

        $oldContents = file_get_contents($this->sandboxPath . 'example-gif.gif');

        $this->images->cleanImage($this->sandboxPath . 'example-gif.gif');
        self::assertSame($oldContents, file_get_contents($this->sandboxPath . 'example-gif.gif'));

        Craft::$app->getConfig()->getGeneral()->transformGifs = true;
        $this->images->cleanImage($this->sandboxPath . 'example-gif.gif');
        self::assertNotSame($oldContents, file_get_contents($this->sandboxPath . 'example-gif.gif'));
    }

    /**
     * @todo With data provider for different image types?
     */
    public function testGetExifData(): void
    {
        $this->_skipIfNoExif();
        $this->_skipIfNoImagick();

        $exifData = $this->images->getExifData($this->sandboxPath . 'image-rotated-180.jpg');

        $requiredValues = [
            'ifd0.Orientation' => 4,
            'ifd0.XResolution' => '72/1',
            'ifd0.YResolution' => '72/1',
            'ifd0.ResolutionUnit' => 2,
            'ifd0.YCbCrPositioning' => 1,
        ];

        foreach ($requiredValues as $key => $value) {
            self::assertSame($value, $exifData[$key] ?? null);
        }
    }

    /**
     * Test that false is returned (and not for example an exception being thrown) when calling exif based functions.
     */
    public function testNoExifFalses(): void
    {
        self::assertNull($this->images->getExifData($this->sandboxPath . 'craft-logo.svg'));
        self::assertFalse($this->images->rotateImageByExifData($this->sandboxPath . 'craft-logo.svg'));
        self::assertFalse($this->images->stripOrientationFromExifData($this->sandboxPath . 'craft-logo.svg'));
    }

    /**
     * @return array
     * @todo Can we get this to fail?
     */
    public static function checkMemoryForImageDataProvider(): array
    {
        return [
            [true, 'craft-logo.svg'],
            [true, 'empty-file.text'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->path = dirname(__DIR__, 3) . '/_data/assets/files/';
        $this->sandboxPath = dirname(__DIR__) . '/images/sandbox/';

        $this->images = Craft::$app->getImages();

        if (!is_dir($this->sandboxPath)) {
            FileHelper::createDirectory($this->sandboxPath);
        }

        FileHelper::clearDirectory($this->sandboxPath);

        copy($this->path . 'dirty-svg.svg', $this->sandboxPath . 'dirty-svg.svg');
        copy($this->path . 'image-rotated-180.jpg', $this->sandboxPath . 'image-rotated-180.jpg');
        copy($this->path . 'craft-logo.svg', $this->sandboxPath . 'craft-logo.svg');
        copy($this->path . 'example-gif.gif', $this->sandboxPath . 'example-gif.gif');
    }

    /**
     *
     */
    private function _skipIfNoImagick()
    {
        if (!($this->images->getIsImagick())) {
            $this->markTestSkipped('Need Imagick to test this function.');
        }
    }

    /**
     *
     */
    private function _skipIfNoExif()
    {
        if (!extension_loaded('exif')) {
            $this->markTestSkipped('Need ext-exif to test this function.');
        }
    }
}
