<?php

declare(strict_types=1);

use craft\web\assets\cp\CpAsset;
use CraftCms\Cms\Support\Facades\HtmlStack;
use yii\web\AssetBundle;

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
