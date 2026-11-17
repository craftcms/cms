<?php

declare(strict_types=1);

use CraftCms\Cms\Support\CmsAssets;

it('resolves legacy icon paths through Craft', function(string $alias, string $path) {
    expect(Craft::getAlias($alias))->toBe(CmsAssets::resourcesPath("icons/$path"));
})->with([
    'icons root' => ['@icons', ''],
    'explicit family' => ['@icons/custom-icons/craft-cms.svg', 'custom-icons/craft-cms.svg'],
    'app icons root' => ['@appicons', 'solid'],
    'family-neutral icon' => ['@appicons/craft-cms.svg', 'custom-icons/craft-cms.svg'],
    'old app icon form' => ['@app/icons/craft-cms.svg', 'custom-icons/craft-cms.svg'],
    'renamed icon' => ['@appicons/buoey.svg', 'solid/life-ring.svg'],
    'extensionless renamed icon' => ['@appicons/info-circle', 'solid/circle-info.svg'],
    'world icon' => ['@appicons/world.svg', 'solid/earth-americas.svg'],
    'unknown icon' => ['@appicons/not-an-icon.svg', 'solid/not-an-icon.svg'],
]);
