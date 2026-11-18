<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\events\AssetPreviewEvent;
use craft\events\DefineAssetThumbUrlEvent;
use craft\events\ReplaceAssetEvent;
use craft\services\Assets as LegacyAssets;
use CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetReplaced;
use CraftCms\Cms\Asset\Events\AssetReplacing;
use CraftCms\Cms\Asset\Events\PreviewHandlerResolving;
use CraftCms\Cms\Asset\Events\ThumbUrlResolving;

afterEach(function() {
    YiiEvent::off(LegacyAssets::class, LegacyAssets::EVENT_BEFORE_REPLACE_ASSET);
    YiiEvent::off(LegacyAssets::class, LegacyAssets::EVENT_AFTER_REPLACE_ASSET);
    YiiEvent::off(LegacyAssets::class, LegacyAssets::EVENT_DEFINE_THUMB_URL);
    YiiEvent::off(LegacyAssets::class, LegacyAssets::EVENT_REGISTER_PREVIEW_HANDLER);
});

test('legacy asset replace events bridge the replacing and replaced events', function() {
    $asset = new Asset();
    $afterFilename = null;

    YiiEvent::on(LegacyAssets::class, LegacyAssets::EVENT_BEFORE_REPLACE_ASSET, function(ReplaceAssetEvent $event) use ($asset) {
        expect($event->asset)->toBe($asset)
            ->and($event->replaceWith)->toBe('/tmp/new.jpg')
            ->and($event->filename)->toBe('old.jpg');

        $event->filename = 'renamed.jpg';
    });
    YiiEvent::on(LegacyAssets::class, LegacyAssets::EVENT_AFTER_REPLACE_ASSET, function(ReplaceAssetEvent $event) use (&$afterFilename) {
        $afterFilename = $event->filename;
    });

    $replacing = new AssetReplacing($asset, '/tmp/new.jpg', 'old.jpg');
    event($replacing);
    event(new AssetReplaced($asset, $replacing->filename));

    expect($replacing->filename)->toBe('renamed.jpg')
        ->and($afterFilename)->toBe('renamed.jpg');
});

test('legacy thumb url and preview handler events resolve the core events', function() {
    $asset = new Asset();
    $previewHandler = Mockery::mock(AssetPreviewHandlerInterface::class);

    YiiEvent::on(LegacyAssets::class, LegacyAssets::EVENT_DEFINE_THUMB_URL, function(DefineAssetThumbUrlEvent $event) {
        $event->url = "https://example.com/thumb-{$event->width}x{$event->height}.jpg";
    });
    YiiEvent::on(LegacyAssets::class, LegacyAssets::EVENT_REGISTER_PREVIEW_HANDLER, function(AssetPreviewEvent $event) use ($previewHandler) {
        $event->previewHandler = $previewHandler;
    });

    $thumbUrl = new ThumbUrlResolving($asset, 120, 80);
    event($thumbUrl);
    $preview = new PreviewHandlerResolving($asset);
    event($preview);

    expect($thumbUrl->url)->toBe('https://example.com/thumb-120x80.jpg')
        ->and($preview->previewHandler)->toBe($previewHandler);
});
