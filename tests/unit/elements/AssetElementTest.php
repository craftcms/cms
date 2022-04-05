<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\base\Fs;
use craft\elements\Asset;
use craft\imagetransforms\ImageTransformer;
use craft\models\ImageTransform;
use craft\models\Volume;
use craft\test\TestCase;
use UnitTester;

/**
 * Unit tests for the User Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.5
 */
class AssetElementTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     *
     */
    public function testTransformWithOverrideParameters(): void
    {

        // Set up asset to have an URL and a mock transform
        $asset = $this->make(Asset::class, [
            'getVolume' => $this->make(Volume::class, [
                'getFs' => $this->make(Fs::class, [
                    'hasUrls' => true,
                ]),
                'getTransformFs' => $this->make(Fs::class, [
                    'hasUrls' => true,
                ]),
            ]),
            'folderId' => 2,
            'filename' => 'foo.jpg',
        ]);
        $this->tester->mockCraftMethods('imageTransforms', [
            'getTransformByHandle' => $this->make(ImageTransform::class, [
                'width' => 400,
                'height' => 200,
                'getImageTransformer' => $this->make(ImageTransformer::class, [
                    'getTransformUrl' => fn(Asset $asset, ImageTransform $transform) => 'w=' . $transform->width . '&h=' . $transform->height,
                ]),
            ]),
        ]);

        $previousValue = Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
        Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = true;
        $url = $asset->getUrl(['transform' => 'mockedTransform', 'width' => 200]);

        $this->assertSame('w=200&h=200', $url);

        Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = $previousValue;
    }
}
