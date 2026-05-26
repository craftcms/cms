<?php

use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
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
