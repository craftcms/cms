<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use craft\fs\Local;
use craft\models\ImageTransform;
use craft\test\TestCase;
use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\ImageTransforms;
use CraftCms\Cms\Support\Str;
use UnitTester;

/**
 * Unit tests for the User Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 *
 * @since 3.5
 */
class AssetElementTest extends TestCase
{
    protected UnitTester $tester;

    public function test_transform_with_override_parameters(): void
    {

        // Set up asset to have an URL and a mock transform
        $asset = $this->make(Asset::class, [
            'getVolume' => $this->make(Volume::class, [
                'getFs' => $this->make(Local::class, [
                    'hasUrls' => true,
                ]),
            ]),
            'folderId' => 2,
            'filename' => 'foo.jpg',
        ]);

        app(AssetTransformDrivers::class)->extend('test', fn() => new class() implements AssetTransformDriver {
            public function definition(): AssetTransformDriverDefinition
            {
                return new AssetTransformDriverDefinition('Test');
            }

            public function transform(AssetTransformRequest $request): AssetTransformResult
            {
                return new AssetTransformResult(
                    "w={$request->parameters['width']}&h={$request->parameters['height']}",
                    'image/jpeg',
                );
            }
        });
        app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
            'uid' => Str::uuid()->toString(),
            'name' => 'Test',
            'handle' => 'test',
            'driver' => 'test',
        ]), false);
        Cms::config()->defaultAssetTransformer('test');

        ImageTransforms::shouldReceive('getTransformByHandle')
            ->andReturn($this->make(ImageTransform::class, [
                'width' => 400,
                'height' => 200,
            ]));

        $url = $asset->getUrl(['transform' => 'mockedTransform', 'width' => 200]);

        self::assertSame('w=200&h=200', $url);
    }
}
