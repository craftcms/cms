<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->api = app(Api::class);
    $this->license = app(License::class);
});

it('can get headers', function () {
    expect($this->api->headers())->not()->toBeEmpty();

    $headers = $this->api->headers();
    expect($headers['Accept'])->toBe('application/json');
    expect($headers['X-Craft-Env'])->toBe(config('app.env'));
    expect($headers['X-Craft-System'])->toBe(sprintf('craft:%s;%s', Cms::VERSION, Edition::get()->handle()));
    expect($headers['X-Craft-Platform'])->toContain('php:', 'ext-');
});

it('can process response headers', function () {
    expect(Cache::has('editionTestableDomain@localhost'))->toBeFalse();

    $key = Str::random();

    $this->api->processResponseHeaders([
        'X-Craft-Allow-Trials' => true,
        'X-Craft-License' => $key,
        'X-Craft-License-Domain' => 'foo.cloud',
    ]);

    expect(Cache::get('editionTestableDomain@localhost'))->toBe(1);
    expect(Cache::get('licensedDomain'))->toBe('foo.cloud');
});

it('can get license info', function () {
    Http::fake([
        Api::craftApiEndpoint().'/cms-licenses?include=' => Http::response([
            'license' => [
                'id' => 1234,
            ],
        ]),
    ]);

    app()->forgetInstance(Api::class);

    expect(app(Api::class)->getLicenseInfo())->toBe([
        'id' => 1234,
    ]);
});

it('processes response headers from failed API responses', function () {
    Cache::forget('licensedDomain');

    Http::fake([
        Api::craftApiEndpoint().'/updates' => Http::response([], 418, [
            'X-Craft-License-Domain' => 'failed.cloud',
        ]),
    ]);

    expect(fn () => $this->api->request('GET', 'updates'))->toThrow(RequestException::class);
    expect(Cache::get('licensedDomain'))->toBe('failed.cloud');
});
