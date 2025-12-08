<?php

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\User\Elements\User;

it('can query users by users that have uploaded an asset', function () {
    expect(userQuery()->assetUploaders()->count())->toBe(0);
    expect(userQuery()->assetUploaders(false)->count())->toBe(1);

    Asset::factory()->create([
        'uploaderId' => User::find()->firstOrFail()->id,
    ]);

    expect(userQuery()->assetUploaders()->count())->toBe(1);
    expect(userQuery()->assetUploaders(false)->count())->toBe(0);
});
