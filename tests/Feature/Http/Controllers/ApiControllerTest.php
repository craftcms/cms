<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\ApiController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;

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

test('processResponseHeaders validates the headers field', function (array $payload) {
    postJson(action([ApiController::class, 'processResponseHeaders']), $payload)
        ->assertJsonValidationErrors(['headers']);
})->with([
    'missing' => [[]],
    'not an array' => [['headers' => 'not-an-array']],
    'empty array' => [['headers' => []]],
]);

test('processResponseHeaders applies the Craft response headers', function () {
    expect(Cache::has('licensedDomain'))->toBeFalse();

    postJson(action([ApiController::class, 'processResponseHeaders']), [
        'headers' => [
            'X-Craft-License-Domain' => 'foo.cloud',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('Accept', 'application/json');

    expect(Cache::get('licensedDomain'))->toBe('foo.cloud');
});
