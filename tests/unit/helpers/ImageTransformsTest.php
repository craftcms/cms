<?php

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\errors\ImageTransformException;
use craft\helpers\ImageTransforms;
use craft\models\ImageTransform;

class ImageTransformsTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testCreateTransformFromString()
    {
        $this->tester->expectThrowable(ImageTransformException::class, function() {
            ImageTransforms::createTransformFromString('some_invalid_string');
        });

        $happyPath = ImageTransforms::createTransformFromString('_1280x600_crop_center-center');
        $this->assertSame($happyPath->width, 1280);
        $this->assertSame($happyPath->height, 600);
        $this->assertSame($happyPath->mode, 'crop');
        $this->assertSame($happyPath->position, 'center-center');

        $withQuality = ImageTransforms::createTransformFromString('_1280x600_crop_center-center_95');
        $this->assertSame($withQuality->quality, 95);

        $withInterlace = ImageTransforms::createTransformFromString('_1280x600_crop_center-center_95_line');
        $this->assertSame($withInterlace->interlace, 'line');
    }

    /**
     * @dataProvider parseTransformStringDataProvider
     */
    public function testParseTransformString(array $config): void
    {
        $transform = new ImageTransform($config);
        $str = ImageTransforms::getTransformString($transform);
        $this->assertSame($config, ImageTransforms::parseTransformString($str));
    }

    public function parseTransformStringDataProvider(): array
    {
        return [
            [[
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'top-left',
                'quality' => 70,
                'interlace' => 'partition',
            ]],
            [[
                'width' => 100,
                'height' => null,
                'mode' => 'crop',
                'position' => 'bottom-right',
                'quality' => null,
                'interlace' => 'none',
            ]],
        ];
    }
}
