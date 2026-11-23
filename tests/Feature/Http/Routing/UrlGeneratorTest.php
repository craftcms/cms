<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Site::factory()->create(['handle' => 'french', 'baseUrl' => 'https://localhost/fr']);
    Site::factory()->create(['handle' => 'other', 'baseUrl' => 'https://other.test/de']);
    Sites::refreshSites();

    Route::get('app-target', fn () => 'app')->name('test.app');

    Route::middleware(['craft', 'craft.web'])->group(function () {
        Route::get('/', fn () => 'home:'.Sites::getCurrentSite()->handle)->name('test.home');
        Route::get('target', fn () => 'target:'.Sites::getCurrentSite()->handle)->name('test.target');
        Route::get('sites/{site}', fn (string $site) => $site)->name('test.site-param');
        Route::domain('localhost')->get('domain-target', fn () => 'domain-target:'.Sites::getCurrentSite()->handle)->name('test.domain');
        Route::get(Cms::config()->cpTrigger.'/cp-path', fn () => 'cp-path')->name('test.cp-path');
        Route::rootOnly()->get('root-only', fn () => 'root-only')->name('test.root-only');
        Route::get('signed', fn (Request $request) => $request->hasValidSignature() ? 'valid' : 'invalid')->name('test.signed');
        Route::get('links', fn () => [
            'route' => route('test.target', ['page' => 2]),
            'home' => route('test.home'),
            'relative' => route('test.target', absolute: false),
            'url' => url('target'),
            'cp' => route('craft.cp.dashboard'),
            'app' => route('test.app'),
            'rootOnly' => route('test.root-only'),
            'cpPath' => route('test.cp-path'),
            'primary' => route('test.target', ['site' => Sites::getPrimarySite()]),
            'other' => route('test.target', ['site' => 'other']),
            'otherById' => route('test.target', ['site' => Sites::getSiteByHandle('other')->id]),
            'unknownSite' => route('test.target', ['site' => 'nope']),
            'siteParam' => route('test.site-param', ['site' => 'other']),
            'domain' => route('test.domain'),
            'signed' => URL::signedRoute('test.signed'),
        ]);
    });

    $routes = new RouteCollection;

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $routes->add($route);
    }

    Route::setRoutes($routes);
});

it('generates site route URLs under the current site’s base URL', function () {
    $cp = 'https://localhost/'.Cms::config()->cpTrigger.'/dashboard';

    expect($this->get('https://localhost/fr/links')->json())->toMatchArray([
        'route' => 'https://localhost/fr/target?page=2',
        'home' => 'https://localhost/fr',
        'relative' => '/target',
        'url' => 'https://localhost/target',
        'cp' => $cp,
        'app' => 'https://localhost/app-target',
        'rootOnly' => 'https://localhost/root-only',
        'cpPath' => 'https://localhost/'.Cms::config()->cpTrigger.'/cp-path',
        'primary' => 'https://localhost/target',
        'other' => 'https://other.test/de/target',
        'otherById' => 'https://other.test/de/target',
        'unknownSite' => 'https://localhost/fr/target?site=nope',
        'siteParam' => 'https://localhost/fr/sites/other',
        'domain' => 'https://localhost/fr/domain-target',
    ]);

    expect($this->get('https://localhost/links')->json())->toMatchArray([
        'route' => 'https://localhost/target?page=2',
        'home' => 'https://localhost',
        'cp' => $cp,
        'other' => 'https://other.test/de/target',
    ]);
});

it('generates other sites’ URLs with their own scheme', function () {
    expect($this->get('http://localhost/fr/links')->json())->toMatchArray([
        'other' => 'https://other.test/de/target',
        'siteParam' => 'http://localhost/fr/sites/other',
    ]);
});

it('generates the next request’s URLs from its own origin', function () {
    $this->get('https://localhost/fr/links');

    expect($this->get('https://other.test/links')->json('url'))->toBe('https://other.test/target');
});

it('resolves the URLs it generates for the site', function () {
    $links = $this->get('https://localhost/fr/links')->json();

    $this->get($links['route'])->assertContent('target:french');
    $this->get($links['home'])->assertContent('home:french');
    $this->get($links['domain'])->assertContent('domain-target:french');
    $this->get($links['signed'])->assertContent('valid');
    $this->get(str_replace('/fr/', '/', $links['signed']))->assertContent('invalid');
    $this->get($links['rootOnly'])->assertContent('root-only');
    $this->get('https://localhost/fr/root-only')->assertNotFound();
});
