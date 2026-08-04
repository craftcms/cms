<?php

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\User\Elements\User;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;

use function Pest\Laravel\actingAs;

test('editable/savable returns 0 when having no access', function (string $method) {
    actingAs(User::find()->one());

    Edition::set(Edition::Pro);

    AssetModel::factory()->create();

    expect(assetQuery()->$method()->count())->toBe(1);

    actingAs(CraftCms\Cms\User\Models\User::factory()->createElement());

    // Access to nothing
    expect(assetQuery()->$method()->count())->toBe(0);
})->with([
    'editable',
    'savable',
]);

test('it adds the volume as a cache tag', function () {
    ElementCaches::startCollectingCacheInfo();

    $asset = AssetModel::factory()->create();

    assetQuery()->volumeId($asset->volumeId)->all();

    /** @var TagDependency $dependency */
    $dependency = ElementCaches::stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::'.Asset::class.'::volume:'.$asset->volumeId);
});
