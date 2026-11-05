<?php

declare(strict_types=1);

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\InputmaskAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\MoneyAsset;

it('loads the CP asset without jQuery or Garnish', function () {
    app(InternalAssetRegistry::class)->register(CpAsset::class);
    $html = app(HtmlStack::class)->headHtml().app(HtmlStack::class)->bodyHtml();

    expect($html)->toContain('legacy/theme/dist/')
        ->not->toContain('legacy/jquery/dist/jquery.js')
        ->not->toContain('legacy/garnish/dist/garnish.js');
});

it('loads Garnish ahead of the legacy money input', function () {
    app(InternalAssetRegistry::class)->register(MoneyAsset::class);
    $html = app(HtmlStack::class)->headHtml().app(HtmlStack::class)->bodyHtml();

    expect($html)->toContain('legacy/garnish/dist/garnish.js')
        ->and(strpos($html, 'legacy/garnish/dist/garnish.js'))
        ->toBeLessThan(strpos($html, 'legacy/money/dist/Money.js'));
});

it('loads jQuery ahead of the Inputmask plugin', function () {
    app(InternalAssetRegistry::class)->register(InputmaskAsset::class);
    $html = app(HtmlStack::class)->headHtml().app(HtmlStack::class)->bodyHtml();

    expect($html)->toContain('legacy/jquery/dist/jquery.js')
        ->and(strpos($html, 'legacy/jquery/dist/jquery.js'))
        ->toBeLessThan(strpos($html, 'legacy/inputmask/dist/jquery.inputmask.bundle.js'));
});
