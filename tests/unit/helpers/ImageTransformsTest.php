<?php

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\ImageTransforms;
use craft\models\ImageTransform;
use craft\models\Volume;
use craft\test\mockclasses\fs\MockNonLocalFs;
use ReflectionProperty;

class ImageTransformsTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected \UnitTester $tester;

    public array $fullTransform = [
        'id' => 123,
        'name' => 'Test Transform',
        'transformer' => ImageTransform::DEFAULT_TRANSFORMER,
        'handle' => 'testTransform',
        'width' => 100,
        'height' => 200,
        'format' => 'jpg',
        'mode' => 'fit',
        'position' => 'center-center',
        'fill' => '#ff0000',
        'quality' => 95,
        'interlace' => 'line',
        'upscale' => true,
    ];

    public function testCreateTransformFromStringInvalid()
    {
        $this->tester->expectThrowable(ImageTransformException::class, function() {
            ImageTransforms::createTransformFromString('some_invalid_string');
        });
    }

    /**
     * @dataProvider createTransformsFromStringProvider
     * @param array $expected
     * @param string $string
     * @return void
     * @throws ImageTransformException
     */
    public function testCreateTransformFromString(array $expected, string $string): void
    {
        $transform = ImageTransforms::createTransformFromString($string);

        foreach ($expected as $property => $value) {
            self::assertSame($transform->{$property}, $value);
        }
    }

    public function createTransformsFromStringProvider(): array
    {
        return [
            'happy path' => [
                [
                    'width' => 1280,
                    'height' => 600,
                    'mode' => 'crop',
                    'position' => 'center-center',
                ],
                '_1280x600_crop_center-center',
            ],
            'with quality' => [
                [
                    'quality' => 95,
                ],
                '_1280x600_crop_center-center_95',
            ],
            'with interlace' => [
                [
                    'interlace' => 'line',
                ],
                '_1280x600_crop_center-center_95_line',
            ],
            'with fill' => [
                [
                    'fill' => '#ff0000',
                ],
                '_1280x600_crop_center-center_95_line_ff0000',
            ],
            'invalid fill' => [
                [
                    'fill' => null,
                ],
                '_1280x600_crop_center-center_95_line_invalidFill',
            ],
            'transparent fill' => [
                [
                    'fill' => 'transparent',
                ],
                '_1280x600_crop_center-center_95_line_transparent',
            ],
            'upscale' => [
                [
                    'upscale' => false,
                ],
                '_1280x600_crop_center-center_95_line_ns',
            ],
            'upscale with fill' => [
                [
                    'fill' => '#ff0000',
                    'upscale' => false,
                ],
                '_1280x600_crop_center-center_95_line_ff0000_ns',
            ],
        ];
    }

    /**
     * @dataProvider normalizeTransformProvider
     */
    public function testNormalizeTransform($expected, $input): void
    {
        $transform = ImageTransforms::normalizeTransform($input);

        if ($expected === null) {
            self::assertSame($expected, $transform);
        } else {
            self::assertInstanceOf(ImageTransform::class, $transform);

            foreach ($expected as $property => $value) {
                self::assertSame($transform->$property, $value);
            }
        }
    }

    public function normalizeTransformProvider(): array
    {
        return [
            'false' => [null, false],
            'empty string' => [null, ''],
            'true' => [null, true],
            'object' => [
                $this->fullTransform,
                (object)$this->fullTransform,
            ],
            'array' => [
                $this->fullTransform,
                $this->fullTransform,
            ],
            'non-numeric width' => [
                ArrayHelper::merge($this->fullTransform, ['width' => null]),
                ArrayHelper::merge($this->fullTransform, ['width' => 'not a number']),
            ],
            'non-numeric height' => [
                ArrayHelper::merge($this->fullTransform, ['height' => null]),
                ArrayHelper::merge($this->fullTransform, ['height' => 'not a number']),
            ],
            'invalid fill' => [
                [
                    'fill' => null,
                ],
                ArrayHelper::merge($this->fullTransform, ['fill' => 'invalidFill']),
            ],
            'transparent fill' => [
                [
                    'fill' => 'transparent',
                ],
                ArrayHelper::merge($this->fullTransform, ['fill' => 'transparent']),
            ],
            'extended transform' => [
                [
                    'id' => null,
                    'name' => null,
                    'width' => $this->fullTransform['width'],
                    'height' => $this->fullTransform['height'],
                ],
                ArrayHelper::merge($this->fullTransform, [
                    'transform' => [
                        'id' => '200',
                        'name' => 'Base Transform',
                        'width' => '300',
                        'height' => '400',
                    ],
                ]),
            ],
            'valid string' => [
                [
                    'width' => 1280,
                    'height' => 600,
                    'mode' => 'crop',
                    'position' => 'center-center',
                ],
                '_1280x600_crop_center-center',
            ],
        ];
    }

    /**
     * @dataProvider getTransformStringProvider
     * @param $expected
     * @param $input
     * @return void
     */
    public function testGetTransformString($expected, $input): void
    {
        $transform = new ImageTransform($input);
        self::assertSame($expected, ImageTransforms::getTransformString($transform));
    }

    public function getTransformStringProvider(): array
    {
        return [
            'basic transform' => [
                '_1200x900_crop_center-center_none_ns',
                [
                    'width' => 1200,
                    'height' => 900,
                    'upscale' => false,
                ],
            ],
            'no width' => [
                '_AUTOx900_crop_center-center_none',
                [
                    'width' => null,
                    'height' => 900,
                    'upscale' => true,
                ],
            ],
            'no height' => [
                '_1200xAUTO_crop_center-center_none',
                [
                    'width' => 1200,
                    'height' => null,
                ],
            ],
            'upscale' => [
                '_1200xAUTO_crop_center-center_none',
                [
                    'width' => 1200,
                    'height' => null,
                    'upscale' => true,
                ],
            ],
            'no upscale' => [
                '_1200xAUTO_crop_center-center_none_ns',
                [
                    'width' => 1200,
                    'height' => null,
                    'upscale' => false,
                ],
            ],
            'with handle' => [
                '_' . $this->fullTransform['handle'],
                $this->fullTransform,
            ],
            'full transform' => [
                '_100x200_fit_center-center_95_line_ff0000_ns',
                ArrayHelper::merge($this->fullTransform, ['handle' => null, 'upscale' => false]),
            ],
            'transparent fill' => [
                '_100x200_fit_center-center_95_line_transparent_ns',
                ArrayHelper::merge($this->fullTransform, ['fill' => 'transparent', 'handle' => null, 'upscale' => false]),
            ],
        ];
    }

    /**
     * @dataProvider parseTransformStringDataProvider
     */
    public function testParseTransformString(array $config): void
    {
        $transform = new ImageTransform($config);
        $str = ImageTransforms::getTransformString($transform);
        self::assertSame($config, ImageTransforms::parseTransformString($str));
    }

    public static function parseTransformStringDataProvider(): array
    {
        return [
            [
                [
                    'width' => 100,
                    'height' => 200,
                    'mode' => 'fit',
                    'position' => 'top-left',
                    'quality' => 70,
                    'interlace' => 'partition',
                    'fill' => null,
                    'upscale' => true,
                ],
            ],
            [
                [
                    'width' => 100,
                    'height' => null,
                    'mode' => 'crop',
                    'position' => 'bottom-right',
                    'quality' => null,
                    'interlace' => 'none',
                    'fill' => null,
                    'upscale' => false,
                ],
            ],
            [
                [
                    'width' => 100,
                    'height' => 200,
                    'mode' => 'fit',
                    'position' => 'top-left',
                    'quality' => 70,
                    'interlace' => 'partition',
                    'fill' => 'transparent',
                    'upscale' => true,
                ],
            ],
            [
                [
                    'width' => 100,
                    'height' => 200,
                    'mode' => 'fit',
                    'position' => 'top-left',
                    'quality' => 70,
                    'interlace' => 'partition',
                    'fill' => '#f00',
                    'upscale' => false,
                ],
            ],
            [
                [
                    'width' => 100,
                    'height' => 200,
                    'mode' => 'fit',
                    'position' => 'top-left',
                    'quality' => 70,
                    'interlace' => 'partition',
                    'fill' => '#ff0000',
                    'upscale' => true,
                ],
            ],
        ];
    }

    /**
     * Covers #19328: getLocalImageSource() only re-downloaded a non-local fs’s cached source file
     * when it was missing or 0 bytes, never when the remote object itself had changed. That left
     * transforms rendering from stale bytes after an asset was replaced on a filesystem such as S3
     * or GCS, if the local cache had already been seeded before the replacement was detected.
     */
    public function testGetLocalImageSourceReDownloadsWhenRemoteFileChanges(): void
    {
        $remoteRoot = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'mock-non-local-fs-' . uniqid('', true);
        FileHelper::createDirectory($remoteRoot);
        $remoteFilename = 'test-image.jpg';
        $remotePath = $remoteRoot . DIRECTORY_SEPARATOR . $remoteFilename;
        file_put_contents($remotePath, 'original bytes');
        touch($remotePath, time() - 100);

        $fs = new MockNonLocalFs([
            'handle' => 'mockNonLocalFs',
            'name' => 'Mock Non-Local Fs',
            'path' => $remoteRoot,
        ]);
        $volume = new Volume();
        $volume->setFs($fs);

        $asset = new Asset();
        $asset->id = PHP_INT_MAX - 1;
        $asset->folderPath = '';
        $asset->setFilename($remoteFilename);
        (new ReflectionProperty(Asset::class, '_volume'))->setValue($asset, $volume);

        $cachePath = Craft::$app->getPath()->getAssetSourcesPath() . DIRECTORY_SEPARATOR . $asset->id . '.' . $asset->getExtension();

        try {
            // First read: nothing cached yet, so it should download the original bytes.
            $sourcePath = ImageTransforms::getLocalImageSource($asset);
            self::assertSame($cachePath, $sourcePath);
            self::assertSame('original bytes', file_get_contents($sourcePath));

            // Change the remote file's content (and mtime) without going through Craft, simulating
            // a "replace file" on a non-local fs while a local cached copy already exists.
            file_put_contents($remotePath, 'updated bytes');
            touch($remotePath, time() + 100);

            // Second read: the cached copy is still present and non-empty, but it's now stale
            // relative to the remote object, so it must be re-downloaded.
            $sourcePath = ImageTransforms::getLocalImageSource($asset);
            self::assertSame('updated bytes', file_get_contents($sourcePath));
        } finally {
            FileHelper::removeDirectory($remoteRoot);
            FileHelper::unlink($cachePath);
        }
    }
}
