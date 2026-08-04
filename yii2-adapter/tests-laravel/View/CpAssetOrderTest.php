<?php

declare(strict_types=1);

use craft\web\assets\cp\CpAsset;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack as HtmlStackService;
use yii\web\AssetBundle;
use yii\web\JqueryAsset as YiiJqueryAsset;

class CpAssetDependentAssetBundle extends AssetBundle
{
    public $baseUrl = 'https://example.test/assets';

    public $depends = [
        CpAsset::class,
    ];

    public $js = [
        'dependent.js',
    ];
}

class YiiJqueryDependentAssetBundle extends AssetBundle
{
    public $baseUrl = 'https://example.test/assets';

    public $depends = [
        YiiJqueryAsset::class,
    ];

    public $js = [
        'depends-on-yii-jquery.js',
    ];
}

beforeEach(function() {
    HtmlStack::clear();

    $view = Craft::$app->getView();
    $view->assetBundles = [];
    $view->registeredAssetBundles = [];
    $view->registeredJsFiles = [];
});

it('renders the internal CP asset files before Yii asset bundle dependents', function() {
    $view = Craft::$app->getView();

    $view->registerAssetBundle(CpAssetDependentAssetBundle::class);

    $html = $view->placeholderHtml()['bodyEndHtml'];

    expect($html)->toContain('legacy/jquery/dist/jquery.js')
        ->and($html)->toContain('https://example.test/assets/dependent.js')
        ->and(strpos($html, 'legacy/jquery/dist/jquery.js'))->toBeLessThan(strpos($html, 'https://example.test/assets/dependent.js'));
});

it('resolves Yii jQuery asset bundles to the internal jQuery asset', function() {
    $view = Craft::$app->getView();

    $view->registerAssetBundle(YiiJqueryDependentAssetBundle::class);

    $html = $view->placeholderHtml()['bodyEndHtml'];

    expect($html)
        ->toContain('legacy/jquery/dist/jquery.js')
        ->and($html)->toContain('https://example.test/assets/depends-on-yii-jquery.js')
        ->and(strpos($html, 'legacy/jquery/dist/jquery.js'))->toBeLessThan(strpos($html, 'https://example.test/assets/depends-on-yii-jquery.js'))
        ->and($html)->not->toContain('cpresources');
});

it('uses the current scoped HtmlStack after scoped instances are flushed', function() {
    $view = Craft::$app->getView();

    app(HtmlStackService::class)->js('window.firstScopedStack = true', Position::Head);
    expect($view->placeholderHtml()['headHtml'])->toContain('window.firstScopedStack = true;');

    app()->forgetScopedInstances();

    app(HtmlStackService::class)->js('window.secondScopedStack = true', Position::Head);
    $html = $view->placeholderHtml()['headHtml'];

    expect($html)
        ->toContain('window.secondScopedStack = true;')
        ->not->toContain('window.firstScopedStack = true;');
});
