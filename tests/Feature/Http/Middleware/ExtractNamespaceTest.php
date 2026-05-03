<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->middleware = app(ExtractNamespace::class);
});

it('does nothing if there is no namespace header', function () {
    $request = Request::create('foo', 'POST', [
        'foo' => [
            'title' => 'Namespaced Title',
        ],
    ]);

    $this->middleware->handle($request, fn () => 'bar');

    expect($request->input('title'))->toBeNull();
});

it('merges input from a simple namespace', function () {
    $request = Request::create('foo', 'POST', [
        'foo' => [
            'title' => 'Namespaced Title',
        ],
    ], server: [
        'HTTP_X_CRAFT_NAMESPACE' => 'foo',
    ]);

    $this->middleware->handle($request, fn () => 'bar');

    expect($request->input('title'))->toBe('Namespaced Title');
});

it('merges input from a bracketed namespace', function () {
    $request = Request::create('foo', 'POST', [
        'fields' => [
            'body' => [
                'blocks' => [
                    'new1' => [
                        'title' => 'Block Title',
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ], server: [
        'HTTP_X_CRAFT_NAMESPACE' => 'fields[body][blocks][new1]',
    ]);

    $this->middleware->handle($request, fn () => 'bar');

    expect($request->input('title'))->toBe('Block Title')
        ->and($request->boolean('enabled'))->toBeTrue();
});

it('ignores namespaces that do not resolve to an array', function () {
    $request = Request::create('foo', 'POST', [
        'foo' => 'bar',
    ], server: [
        'HTTP_X_CRAFT_NAMESPACE' => 'foo',
    ]);

    $this->middleware->handle($request, fn () => 'bar');

    expect($request->input())->toBe([
        'foo' => 'bar',
    ]);
});
