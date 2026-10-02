<?php

use CraftCms\Cms\Http\Middleware\HandleTemplateRequest;
use CraftCms\Yii2Adapter\Http\HandleYiiSiteRouteFallback;
use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use CraftCms\Yii2Adapter\Http\NormalizeLegacyPath;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use yii\base\Application;

it('can restore empty strings', function() {
    $_POST = [
        'foo' => '',
    ];

    $request = Request::create(
        uri: '/',
        method: 'POST',
        parameters: [
            'foo' => '',
        ]
    );

    expect($request->get('foo'))->toBe('');

    (new ConvertEmptyStringsToNull())
        ->handle($request, function() {
        });

    expect($request->get('foo'))->toBeNull();

    try {
        app(LegacyMiddleware::class)->handle($request, function() {
        });
    } catch (Throwable) {
        // We don't care about exceptions
    }

    expect($request->get('foo'))->toBe('');
});

it('can restore nested empty strings', function() {
    $_POST = [
        'foo' => [
            'bar' => [
                'baz' => '',
            ],
        ],
    ];

    $request = Request::create(
        uri: '/',
        method: 'POST',
        parameters: [
            'foo' => [
                'bar' => [
                    'baz' => '',
                ],
            ],
        ],
    );

    expect($request->get('foo')['bar']['baz'])->toBe('');

    (new ConvertEmptyStringsToNull())->handle($request, function() {
    });

    expect($request->get('foo')['bar']['baz'])->toBeNull();

    try {
        app(LegacyMiddleware::class)->handle($request, function() {
        });
    } catch (Throwable) {
        // We don't care about exceptions
    }

    expect($request->get('foo')['bar']['baz'])->toBe('');
});

it('normalizes legacy path parameters without re-entering the application', function() {
    $request = Request::create('/index.php?p=legacy/path&site=en', 'POST', [
        'foo' => 'bar',
    ]);

    $normalized = app(NormalizeLegacyPath::class)->handle($request, fn(Request $request) => $request);

    expect($normalized->path())->toBe('legacy/path')
        ->and($normalized->query('site'))->toBe('en')
        ->and($normalized->query('p'))->toBeNull()
        ->and($normalized->input('foo'))->toBe('bar')
        ->and(request())->toBe($normalized);
});

it('runs Yii site routes before the public template fallback', function() {
    $middleware = app(Router::class)->getMiddlewareGroups()['craft.web'];
    $templateFallback = array_search(HandleTemplateRequest::class, $middleware, true);
    $yiiFallback = array_search(HandleYiiSiteRouteFallback::class, $middleware, true);

    expect($templateFallback)->toBeInt()
        ->and($yiiFallback)->toBeInt()
        ->and($yiiFallback)->toBeGreaterThan($templateFallback);
});

it('triggers after request callbacks when Laravel terminates a request', function() {
    $called = false;

    Craft::$app->on(Application::EVENT_AFTER_REQUEST, function() use (&$called) {
        $called = true;
    });

    app()->terminate();

    expect($called)->toBeTrue();
});

it('does not trigger after request callbacks twice after a legacy request', function() {
    $calls = 0;

    Craft::$app->on(Application::EVENT_AFTER_REQUEST, function() use (&$calls) {
        $calls++;
    });

    Craft::$app->state = Application::STATE_AFTER_REQUEST;
    Craft::$app->trigger(Application::EVENT_AFTER_REQUEST);
    app()->terminate();

    expect($calls)->toBe(1);
});
