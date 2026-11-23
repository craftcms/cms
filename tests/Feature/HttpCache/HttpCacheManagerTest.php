<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\HttpCache;
use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\ProxyClient\Noop;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['craft.http-cache.clients' => [
        'noop' => ['driver' => 'noop'],
        'varnish' => [
            'driver' => 'varnish',
            'servers' => ['10.0.0.1:6081', '10.0.0.2:6081'],
            'base_uri' => 'https://example.com/site',
        ],
        'cloudflare' => [
            'driver' => 'cloudflare',
            'authentication_token' => 'secret',
            'zone_identifier' => 'zone',
        ],
    ]]);
});

it('uses the default client', function () {
    expect(HttpCache::client())->toBeInstanceOf(Noop::class);
});

it('sends each purge to every server, keeping the site host', function () {
    Http::fake();

    HttpCache::invalidator('varnish')->invalidatePath('/blog')->invalidatePath('/blog')->flush();

    Http::assertSentCount(2);
    foreach (['10.0.0.1', '10.0.0.2'] as $server) {
        Http::assertSent(fn (Request $request) => $request->method() === 'PURGE'
            && $request->url() === "http://$server:6081/site/blog"
            && $request->header('Host') === ['example.com']);
    }
});

it('sends tag purges with their bodies, without merging them', function () {
    Http::fake();

    HttpCache::invalidator('cloudflare')->invalidateTags(['a'])->flush();
    HttpCache::invalidator('cloudflare')->invalidateTags(['b'])->invalidateTags(['c'])->flush();

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.cloudflare.com/client/v4/zones/zone/purge_cache'
        && $request->header('Authorization') === ['Bearer secret']
        && $request->data() === ['tags' => ['c']]);
});

it('reports failed purges', function () {
    Http::fake(['*' => Http::response(status: 503)]);

    HttpCache::invalidator('varnish')->invalidatePath('/blog')->flush();
})->throws(ExceptionCollection::class);
