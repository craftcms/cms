<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->middleware = app(ResolveSite::class);
    $this->sites = app(CraftCms\Cms\Site\Sites::class);
});

it('sets the current site from a path-based site url', function () {
    $site = Site::factory()->create([
        'language' => 'fr',
        'baseUrl' => 'https://localhost/fr',
    ]);

    $this->sites->refreshSites();

    $request = Request::create('https://localhost/fr/news');

    $this->middleware->handle($request, fn () => 'passed');

    expect(Sites::getCurrentSite()->id)->toBe($site->id);
});

it('prefers a site token over the request url', function () {
    $site = Site::factory()->create([
        'language' => 'fr',
        'baseUrl' => 'https://localhost/fr',
    ]);

    $this->sites->refreshSites();

    $request = Request::create('https://localhost/news', 'GET', [
        config('craft.general.siteToken') => Crypt::encrypt((string) $site->id),
    ]);

    $this->middleware->handle($request, fn () => 'passed');

    expect(Sites::getCurrentSite()->id)->toBe($site->id);
});

it('sets no-cache headers for site token requests on routes outside Craft', function () {
    Route::middleware('web')->get('app-route', fn () => response('page')->setPublic()->setMaxAge(3600));

    $response = $this->get('/app-route', [
        'X-Craft-Site-Token' => Crypt::encrypt((string) Sites::getPrimarySite()->id),
    ])->assertOk();

    expect($response->headers->get('Cache-Control'))->toBe('must-revalidate, no-cache, no-store, private');
});
