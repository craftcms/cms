<?php

declare(strict_types=1);

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\MoneyAsset;

function legacyAssetsHtml(string $asset): string
{
    app(InternalAssetRegistry::class)->register($asset);
    $stack = app(HtmlStack::class);

    return $stack->headHtml().$stack->bodyHtml();
}

it('loads the CP asset without jQuery or Garnish', function () {
    $html = legacyAssetsHtml(CpAsset::class);

    expect($html)->toContain('legacy/theme/dist/')
        ->not->toContain('legacy/jquery/dist/jquery.js')
        ->not->toContain('legacy/garnish/dist/garnish.js');
});

it('loads Garnish ahead of the legacy money input', function () {
    $html = legacyAssetsHtml(MoneyAsset::class);

    expect($html)->toContain('legacy/garnish/dist/garnish.js')
        ->and(strpos($html, 'legacy/garnish/dist/garnish.js'))
        ->toBeLessThan(strpos($html, 'legacy/money/dist/Money.js'));
});
