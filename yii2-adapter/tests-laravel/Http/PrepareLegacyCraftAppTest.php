<?php

declare(strict_types=1);

use CraftCms\Yii2Adapter\Http\PrepareLegacyCraftApp;
use Illuminate\Http\Request;
use yii\web\AssetBundle;
use yii\web\Response;

it('preserves asset bundles registered before request middleware runs', function() {
    Craft::$app->getView()->registerAssetBundle(InitRegisteredAsset::class);

    $html = app(PrepareLegacyCraftApp::class)->handle(
        Request::create('/'),
        fn() => Craft::$app->getView()->placeholderHtml(),
    );

    expect($html['headHtml'])->toContain('/cms-2324.css')
        ->and($html['bodyEndHtml'])->toContain('/cms-2324.js');
});

it('clears the previous legacy response before the next request', function() {
    $previous = Craft::$app->getResponse();
    $format = $previous->format;
    $previous->setIlluminateResponse(response('previous'));
    $previous->headers->set('X-Previous-Response', 'yes');
    $previous->format = Response::FORMAT_RAW;

    $response = app(PrepareLegacyCraftApp::class)->handle(
        Request::create('/'),
        fn() => Craft::$app->getResponse(),
    );

    expect($response)->not->toBe($previous)
        ->and($response->getIlluminateResponse())->toBeNull()
        ->and($response->headers->get('X-Previous-Response'))->toBeNull()
        ->and($response->format)->toBe($format);
});

class InitRegisteredAsset extends AssetBundle
{
    public $baseUrl = '/';

    public $css = ['cms-2324.css'];

    public $js = ['cms-2324.js'];
}
