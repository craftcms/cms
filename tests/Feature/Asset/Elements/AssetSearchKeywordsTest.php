<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;

it('makes images findable by their colors’ names, in English and in the site’s language', function (string $language, string $expected) {
    Site::query()->whereKey(Sites::getPrimarySite()->id)->update(['language' => $language]);
    Sites::refreshSites();

    $asset = Asset::factory()->createElement(['kind' => 'image']);
    // Red, then blue
    $asset->colors = new ImageColors(palette: ['#d0302a', '#2563eb']);

    expect($asset->getSearchKeywords('colors'))->toBe($expected);
})->with([
    'German' => ['de', 'red rot blue blau'],
    'English' => ['en-US', 'red blue'],
]);
