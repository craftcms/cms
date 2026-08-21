<?php

declare(strict_types=1);

use CraftCms\Yii2Adapter\Http\PrepareLegacyCraftApp;
use Illuminate\Http\Request;
use yii\web\AssetBundle;

it('preserves asset bundles registered before request middleware runs', function() {
    Craft::$app->getView()->registerAssetBundle(InitRegisteredAsset::class);

    $html = app(PrepareLegacyCraftApp::class)->handle(
        Request::create('/'),
        fn() => Craft::$app->getView()->placeholderHtml(),
    );

    expect($html['headHtml'])->toContain('/cms-2324.css')
        ->and($html['bodyEndHtml'])->toContain('/cms-2324.js');
});

class InitRegisteredAsset extends AssetBundle
{
    public $baseUrl = '/';

    public $css = ['cms-2324.css'];

    public $js = ['cms-2324.js'];
}
