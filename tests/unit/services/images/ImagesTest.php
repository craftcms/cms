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
use craft\helpers\StringHelper;
use craft\services\Images;
use Imagick;
use UnitTester;
use yii\base\Exception;

/**
 * Unit tests for images service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ImagesTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Images
     */
    protected $images;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $sandboxPath;

    /**
     * @dataProvider checkMemoryForImageDataProvider
     *
     * @param bool $expected
     * @param string $filePath
     */
    public function testCheckMemoryForImage(bool $expected, string $filePath)
    {
        self::assertSame($expected, $this->images->checkMemoryForImage($this->path . $filePath));
    }

    /**
     * @throws Exception
     */
    public function testCleanImageSvg()
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
    public function testDontCleanWithConfigSetting()
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
    public function testRotateImageByExifData()
    {
        $this->_skipIfNoImagick();

        $this->images->cleanImage($this->sandboxPath . 'image-rotated-180.jpg');
        $image = new Imagick($this->sandboxPath . 'image-rotated-180.jpg');
        self::assertSame(0, $image->getImageOrientation());
    }

    /**
     * @throws Exception
     */
    public function testCleanImageRotatesOrientation()
    {
        $this->_skipIfNoImagick();

        $this->images->cleanImage($this->sandboxPath . 'image-rotated-180.jpg');
        $currentExif = $this->images->getExifData($this->sandboxPath . 'image-rotated-180.jpg');
        self::assertArrayNotHasKey('ifd0.Orientation', $currentExif);
    }

    /**
     * Tests respect for the transformGifs config setting.
     *
     * @throws Exception
     */
    public function testCleanImageDoesntDoGifWhenSettingDisabled()
    {
        $this->_skipIfNoImagick();

        Craft::$app->getConfig()->getGeneral()->transformGifs = false;

        $oldContents = file_get_contents($this->sandboxPath . 'example-gif.gif');
        self::assertNull($this->images->cleanImage($this->sandboxPath . 'example-gif.gif'));
        self::assertSame($oldContents, file_get_contents($this->sandboxPath . 'example-gif.gif'));

        Craft::$app->getConfig()->getGeneral()->transformGifs = true;
        $this->images->cleanImage($this->sandboxPath . 'example-gif.gif');
        self::assertNotSame($oldContents, file_get_contents($this->sandboxPath . 'example-gif.gif'));
    }

    /**
     * @todo With data provider for different image types?
     */
    public function testGetExifData()
    {
        $this->_skipIfNoImagick();
        $exifData = $this->images->getExifData($this->sandboxPath . 'image-rotated-180.jpg');

        $requiredValues = [
            'ifd0.Orientation' => 4,
            'ifd0.XResolution' => '72/1',
            'ifd0.YResolution' => '72/1',
            'ifd0.ResolutionUnit' => 2,
            'ifd0.YCbCrPositioning' => 1
        ];

        foreach ($requiredValues as $key => $value) {
            self::assertSame($value, $exifData[$key] ?? null);
        }
    }

    /**
     * Test that false is returned (and not for example an exception being thrown) when calling exif based functions.
     */
    public function testNoExifFalses()
    {
        self::assertNull($this->images->getExifData($this->sandboxPath . 'craft-logo.svg'));
        self::assertFalse($this->images->rotateImageByExifData($this->sandboxPath . 'craft-logo.svg'));
        self::assertFalse($this->images->stripOrientationFromExifData($this->sandboxPath . 'craft-logo.svg'));
    }

    /**
     * @return array
     * @todo Can we get this to fail?
     */
    public function checkMemoryForImageDataProvider(): array
    {
        return [
            [true, 'craft-logo.svg'],
            [true, 'empty-file.text'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before()
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
        if (!($this->images->getIsImagick() && method_exists(Imagick::class, 'getImageOrientation'))) {
            $this->markTestSkipped('Need Imagick to test this function.');
        }
    }
}
