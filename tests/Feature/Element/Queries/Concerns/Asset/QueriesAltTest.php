<?php

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Site\Models\Site;

test('alt', function () {
    // Without alt
    Asset::factory()->create();

    // With alt
    Asset::factory()->create()->sites()->attach(Site::all(), [
        'alt' => 'Alt text',
    ]);

    $assetWithAltInSite = Asset::factory()->create();
    $assetWithAltInSite->sites()->attach(Site::first(), ['alt' => 'Alt text in site']);

    expect(assetQuery()->count())->toBe(3);
    expect(assetQuery()->hasAlt()->count())->toBe(2);
    expect(assetQuery()->hasAlt(false)->count())->toBe(1);
});
