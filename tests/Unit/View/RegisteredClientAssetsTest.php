<?php

declare(strict_types=1);

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;
use CraftCms\Cms\View\RegisteredClientAssets;

class TestClientTrackedAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile('/client-tracked-bundle.js');
    }
}

function clientAssetHash(string $key): string
{
    return sprintf('%x', crc32($key));
}

it('skips asset bundles the client reported as loaded', function () {
    request()->headers->set('X-Registered-Asset-Bundles', clientAssetHash(TestClientTrackedAsset::class));

    $registry = app(InternalAssetRegistry::class);
    $registry->register(TestClientTrackedAsset::class);
    $registry->flush();

    expect(app(HtmlStack::class)->bodyHtml())->not->toContain('client-tracked-bundle.js');
});

it('registers asset bundles the client does not have', function () {
    $registry = app(InternalAssetRegistry::class);
    $registry->register(TestClientTrackedAsset::class);
    $registry->flush();

    expect(app(HtmlStack::class)->bodyHtml())->toContain('client-tracked-bundle.js');
});

it('skips JS files the client reported as loaded', function () {
    request()->headers->set('X-Registered-Js-Files', clientAssetHash('/already-loaded.js'));

    $htmlStack = app(HtmlStack::class);
    $htmlStack->jsFile('/already-loaded.js');
    $htmlStack->jsFile('/fresh.js');

    $html = $htmlStack->bodyHtml();

    expect($html)->not->toContain('already-loaded.js')
        ->and($html)->toContain('fresh.js');
});

it('respects custom jsFile keys when matching client-loaded files', function () {
    request()->headers->set('X-Registered-Js-Files', clientAssetHash('my-key'));

    $htmlStack = app(HtmlStack::class);
    $htmlStack->jsFile('/keyed.js', [], 'my-key');

    expect($htmlStack->bodyHtml())->not->toContain('keyed.js');
});

it('emits a sync script recording registered assets on full page renders', function () {
    $htmlStack = app(HtmlStack::class);

    $registry = app(InternalAssetRegistry::class);
    $registry->register(TestClientTrackedAsset::class);
    $registry->flush();

    app(RegisteredClientAssets::class)->registerSyncJs($htmlStack);

    $html = $htmlStack->bodyHtml();

    expect($html)->toContain('Craft.registeredAssetBundles')
        ->and($html)->toContain(clientAssetHash(TestClientTrackedAsset::class))
        ->and($html)->toContain('Craft.registeredJsFiles')
        ->and($html)->toContain(clientAssetHash('/client-tracked-bundle.js'));
});

it('does not emit the sync script for ajax requests', function () {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');

    $htmlStack = app(HtmlStack::class);
    $htmlStack->jsFile('/some-file.js');

    app(RegisteredClientAssets::class)->registerSyncJs($htmlStack);

    expect($htmlStack->bodyHtml())->not->toContain('Craft.registeredJsFiles');
});

it('emits nothing when no assets were registered', function () {
    $htmlStack = app(HtmlStack::class);

    app(RegisteredClientAssets::class)->registerSyncJs($htmlStack);

    expect($htmlStack->bodyHtml())->not->toContain('Craft.registeredAssetBundles');
});

it('emits pending bundle hashes even when called before any drain', function () {
    $htmlStack = app(HtmlStack::class);

    $registry = app(InternalAssetRegistry::class);
    $registry->register(TestClientTrackedAsset::class);
    // No flush/drain before the sync registration — mirrors the page
    // composer, which runs before headHtml() is rendered.
    app(RegisteredClientAssets::class)->registerSyncJs($htmlStack);

    expect($htmlStack->bodyHtml())->toContain(clientAssetHash(TestClientTrackedAsset::class));
});
