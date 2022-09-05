<?php

namespace helpers;

use craft\errors\ImageTransformException;
use craft\helpers\ImageTransforms;

class ImageTransformsHelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
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
}
