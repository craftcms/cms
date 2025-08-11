<?php

use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;

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
