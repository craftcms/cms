<?php

use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Yii2Adapter\Http\CaptureOriginalActionRequestUri;
use Illuminate\Http\Request as IlluminateRequest;
use yii\base\Module;
use yii\web\Controller;

it('routes plugin default controller action shorthand', function() {
    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'default' => TestPluginDefaultController::class,
        ],
    ]);

    $response = Craft::$app->runAction('test-plugin/ping');

    expect($response->data)->toBe('pong');
});

it('accepts null responses from plugin default controller action shorthand', function() {
    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'default' => TestPluginDefaultController::class,
        ],
    ]);

    $response = Craft::$app->runAction('test-plugin/null');

    expect($response)->toBeNull();
});

it('preserves the original URI for legacy action param requests', function() {
    $request = IlluminateRequest::create('/contact?site=en', 'POST', [
        'action' => 'test-plugin/fail',
        'foo' => 'bar',
    ]);

    app()->instance('request', $request);

    app(CaptureOriginalActionRequestUri::class)->handle($request, function(IlluminateRequest $request) {
        app(HandleActionRequest::class)->handle($request, function(IlluminateRequest $request) {
            expect($request->getRequestUri())->toBe('/actions/test-plugin/fail')
                ->and($request->attributes->get(CaptureOriginalActionRequestUri::ORIGINAL_ACTION_REQUEST_URI))->toBe('/contact?site=en');

            app()->instance('request', $request);

            $yiiRequest = Craft::createObject(\craft\helpers\App::webRequestConfig());

            expect($yiiRequest->getFullPath())->toBe('contact')
                ->and($yiiRequest->getIsActionRequest())->toBeTrue()
                ->and($yiiRequest->getActionSegments())->toBe(['test-plugin', 'fail']);
        });
    });
});

it('does not override the URI for direct legacy action route requests', function() {
    $request = IlluminateRequest::create('/actions/test-plugin/fail', 'POST', [
        'foo' => 'bar',
    ]);

    app(CaptureOriginalActionRequestUri::class)->handle($request, function(IlluminateRequest $request) {
        expect($request->getRequestUri())->toBe('/actions/test-plugin/fail')
            ->and($request->attributes->has(CaptureOriginalActionRequestUri::ORIGINAL_ACTION_REQUEST_URI))->toBeFalse();
    });
});

class TestPluginDefaultController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionPing(): string
    {
        return 'pong';
    }

    public function actionNull(): null
    {
        return null;
    }
}
