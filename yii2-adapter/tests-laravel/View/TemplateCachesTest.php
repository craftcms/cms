<?php

declare(strict_types=1);

use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\Cache;
use yii\web\AssetBundle;

class TestAssetBundle extends AssetBundle
{
}

beforeEach(function() {
    Cache::flush();
    HtmlStack::clear();
    Sites::setCurrentSite(new Site([
        'id' => 1,
        'language' => 'en-US',
        'baseUrl' => 'https://example.test/',
        'primary' => true,
        'hasUrls' => true,
    ]));
    Craft::$app->getView()->assetBundles = [];
});

it('replays legacy asset bundles from cached template fragments', function() {
    $cacheService = Craft::$app->getTemplateCaches();
    $view = Craft::$app->getView();

    $cacheService->startTemplateCache(true, true);
    $view->registerAssetBundle(TestAssetBundle::class);
    $cacheService->endTemplateCache('asset-bundle-cache', true, null, null, 'cached body', true);

    expect($view->assetBundles)->toHaveKey(TestAssetBundle::class);

    HtmlStack::clear();
    $view->assetBundles = [];

    $body = $cacheService->getTemplateCache('asset-bundle-cache', true, true);

    expect($body)->toBe('cached body')
        ->and($view->assetBundles)->toHaveKey(TestAssetBundle::class);
});
