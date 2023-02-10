<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Codeception\Stub\Expected;
use Craft;
use craft\base\Volume;
use craft\elements\Asset;
use craft\models\AssetTransform;
use craft\services\AssetTransforms;
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
    public $tester;

    /**
     *
     */
    public function testTransformWithOverrideParameters()
    {
        $asset = $this->make(Asset::class, [
            'getVolume' => $this->make(Volume::class, [
               'hasUrls' => true,
            ]),
            'folderId' => 2,
        ]);

        $this->tester->mockCraftMethods('assetTransforms', [
            'normalizeTransform' => Expected::atLeastOnce(new AssetTransform()),
            'extendTransform' => Expected::once(new AssetTransform()),
        ]);

        $asset->getUrl([
            'transform' => 'transformHandle',
            'width' => 200,
        ]);
    }

    /**
     * @param $transformData
     * @throws \yii\base\InvalidConfigException
     * @dataProvider normalizingExtendsTransformProvider
     */
    public function testNormalizingExtendsTransform($methodName, $transformData, $expectExtension)
    {
        $asset = $this->make(Asset::class, [
            'getVolume' => $this->make(Volume::class, [
                'hasUrls' => true,
            ]),
            'folderId' => 2,
            'kind' => Asset::KIND_IMAGE,
            'width' => 100,
            'height' => 100,
            'filename' => 'some.jpg',
        ]);

        $this->tester->mockCraftMethods('assets', [
            'getAssetUrl' => 'http://url.com',
        ]);

        $extend = $expectExtension ? Expected::once(new AssetTransform()) : Expected::never(new AssetTransform());

        $assetTransforms = $this->make(AssetTransforms::class, [
            'getTransformByHandle' => new AssetTransform(),
            'extendTransform' => $extend,
        ]);

        Craft::$app->set('assetTransforms', $assetTransforms);

        $result = $asset->{$methodName}($transformData);
    }

    public function normalizingExtendsTransformProvider()
    {
        return [
            ['getUrl', ['width' => 200], false],
            ['getUrl', ['width' => 200, 'transform' => 'someTransform'], true],
            ['getHeight', ['width' => 200], false],
            ['getHeight', ['width' => 200, 'transform' => 'someTransform'], true],
            ['getWidth', ['width' => 200], false],
            ['getWidth', ['width' => 200, 'transform' => 'someTransform'], true],
            ['getImg', ['width' => 200], false],
            ['getImg', ['width' => 200, 'transform' => 'someTransform'], true],
            ['setTransform', ['width' => 200], false],
            ['setTransform', ['width' => 200, 'transform' => 'someTransform'], true],
        ];
    }
}
