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
use craft\elements\db\EagerLoadPlan;
use craft\elements\User;
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

        self::assertSame('w=200&h=200', $url);

        Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = $previousValue;
    }

    /**
     * `uploader` is a magic property that resolves to `getUploader()`. Since eager-loading the
     * `uploader` handle bypasses the generic `Element::$_eagerLoadedElements` array (see
     * `Asset::setEagerLoadedElements()`), property access should always match `getUploader()`,
     * whether or not the uploader has been eager-loaded.
     */
    public function testUploaderPropertyMatchesGetter(): void
    {
        // No uploader set at all
        $asset = new Asset(['id' => 1]);
        self::assertNull($asset->getUploader());
        self::assertSame($asset->getUploader(), $asset->uploader);

        // Uploader set directly, not via eager-loading
        $uploader = new User(['id' => 100]);
        $asset->setUploader($uploader);
        self::assertSame($uploader, $asset->getUploader());
        self::assertSame($asset->getUploader(), $asset->uploader);

        // Uploader eager-loaded
        $asset2 = new Asset(['id' => 2]);
        $plan = new EagerLoadPlan(['handle' => 'uploader']);
        $asset2->setEagerLoadedElements('uploader', [$uploader], $plan);
        self::assertSame($uploader, $asset2->getUploader());
        self::assertSame($asset2->getUploader(), $asset2->uploader);

        // No uploader eager-loaded (empty result)
        $asset3 = new Asset(['id' => 3]);
        $asset3->setEagerLoadedElements('uploader', [], $plan);
        self::assertNull($asset3->getUploader());
        self::assertSame($asset3->getUploader(), $asset3->uploader);
    }
}
