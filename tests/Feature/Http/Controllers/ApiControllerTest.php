<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\ApiController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('headers returns JSON response with API headers', function () {
    get(action([ApiController::class, 'headers']))
        ->assertOk()
        ->assertHeader('content-type', 'application/json');
});

test('headers returns array structure', function () {
    $json = get(action([ApiController::class, 'headers']))
        ->assertOk()
        ->json();

    expect($json)->toBeArray();
});

test('processResponseHeaders validates required headers field', function () {
    postJson(action([ApiController::class, 'processResponseHeaders']), [])
        ->assertJsonValidationErrors(['headers']);
});

test('processResponseHeaders validates headers is array', function () {
    postJson(action([ApiController::class, 'processResponseHeaders']), [
        'headers' => 'not-an-array',
    ])->assertJsonValidationErrors(['headers']);
});

test('processResponseHeaders processes headers and returns response', function () {
    $json = postJson(action([ApiController::class, 'processResponseHeaders']), [
        'headers' => [
            'X-Custom-Header' => 'value1',
            'X-Another-Header' => 'value2',
        ],
    ])
        ->assertOk()
        ->assertHeader('content-type', 'application/json')
        ->json();

    expect($json)->toBeArray();
});

test('processResponseHeaders rejects empty headers array', function () {
    postJson(action([ApiController::class, 'processResponseHeaders']), [
        'headers' => [],
    ])
        ->assertJsonValidationErrors(['headers']);
});

test('processResponseHeaders handles nested header structures', function () {
    $json = postJson(action([ApiController::class, 'processResponseHeaders']), [
        'headers' => [
            'X-Rate-Limit' => '100',
            'X-Rate-Remaining' => '95',
            'X-Custom-Data' => 'complex-value',
        ],
    ])
        ->assertOk()
        ->json();

    expect($json)->toBeArray();
});
