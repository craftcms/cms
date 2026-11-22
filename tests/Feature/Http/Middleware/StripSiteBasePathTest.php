<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Route\Data\Route as ProjectConfigRoute;
use CraftCms\Cms\Route\ProjectConfigRoutes;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

beforeEach(function () {
    Site::factory()->create(['handle' => 'french', 'baseUrl' => 'https://localhost/fr']);
    Sites::refreshSites();

    Route::middleware(['craft', 'craft.web'])->get('{path}', fn (Request $request) => [
        'site' => Sites::getCurrentSite()->handle,
        'path' => $request->path(),
        'requestPath' => Sites::getRequestPath($request),
        'url' => $request->url(),
        'fullUrl' => $request->fullUrl(),
        'root' => url('/'),
    ])->where('path', 'probe|fr/probe');
});

it('routes site requests relative to the site base path', function (string $url, string $path) {
    $this->get($url)->assertOk()->assertExactJson([
        'site' => 'french',
        'path' => $path,
        'requestPath' => $path,
        'url' => "https://localhost/fr/$path",
        'fullUrl' => "https://localhost/fr/$path?page=2",
        'root' => 'https://localhost',
    ]);
})->with([
    'route under the base path' => ['https://localhost/fr/probe?page=2', 'probe'],
    'route that repeats the base path' => ['https://localhost/fr/fr/probe?page=2', 'fr/probe'],
]);

it('routes site requests under a base path with encoded characters', function (string $baseUrl) {
    Site::factory()->create(['handle' => 'japanese', 'baseUrl' => $baseUrl]);
    Sites::refreshSites();

    $this->get('https://localhost/%E6%97%A5%E6%9C%AC/probe')->assertOk()->assertJson([
        'site' => 'japanese',
        'path' => 'probe',
        'requestPath' => 'probe',
        'url' => 'https://localhost/%E6%97%A5%E6%9C%AC/probe',
    ]);
})->with([
    'decoded base URL' => 'https://localhost/日本',
    'encoded base URL' => 'https://localhost/%E6%97%A5%E6%9C%AC',
]);

it('routes site requests for an app in a subfolder', function () {
    Site::where('primary', true)->firstOrFail()->update(['baseUrl' => 'https://localhost/craft']);
    Site::factory()->create(['handle' => 'subfolderFrench', 'baseUrl' => 'https://localhost/craft/fr']);
    Sites::refreshSites();

    $this->call('GET', 'https://localhost/craft/fr/probe?page=2', server: [
        'SCRIPT_NAME' => '/craft/index.php',
        'SCRIPT_FILENAME' => '/var/www/public/index.php',
    ])->assertOk()->assertExactJson([
        'site' => 'subfolderFrench',
        'path' => 'probe',
        'requestPath' => 'probe',
        'url' => 'https://localhost/craft/fr/probe',
        'fullUrl' => 'https://localhost/craft/fr/probe?page=2',
        'root' => 'https://localhost/craft',
    ]);
});

it('leaves requests outside the site base path alone', function () {
    $this->get('https://localhost/probe')->assertOk()->assertJson([
        'site' => Sites::getPrimarySite()->handle,
        'path' => 'probe',
        'url' => 'https://localhost/probe',
    ]);

    $cpTrigger = Cms::config()->cpTrigger;

    $this->get("https://localhost/fr/$cpTrigger")->assertNotFound();
    $this->get("https://localhost/fr/$cpTrigger/nope")->assertNotFound();
    expect(request()->isCpRequest())->toBeFalse()
        ->and(request()->path())->toBe("fr/$cpTrigger/nope");
});

it('keeps the full path for routes outside the craft middleware group', function () {
    Route::get('fr/app-route', fn (Request $request) => $request->path());

    $this->get('https://localhost/fr/app-route')->assertContent('fr/app-route');

    Route::get('app-route', fn (Request $request) => $request->path());

    $this->get('https://localhost/fr/app-route')->assertContent('fr/app-route');

    Route::middleware(['craft', 'craft.web'])->get('site-route', fn () => 'site route');
    Route::get('fr/site-route', fn (Request $request) => $request->path());

    $this->get('https://localhost/fr/site-route')->assertContent('fr/site-route');
});

it('leaves invalid site selectors to Craft routes', function () {
    Cms::config()->headlessMode(true);
    Route::get('fr/app-route', fn () => 'app route');

    $this->get('https://localhost/fr/app-route', ['X-Craft-Site' => 'nope'])->assertOk();
    $this->get('https://localhost/fr/probe', ['X-Craft-Site' => 'nope'])->assertBadRequest();
});

it('finds the site from the host a trusted proxy forwarded', function () {
    $this->beforeApplicationDestroyed(TrustProxies::flushState(...));
    TrustProxies::at('*');
    // As in a new PHP process, where no earlier request has trusted a proxy yet
    SymfonyRequest::setTrustedProxies([], SymfonyRequest::getTrustedHeaderSet());

    $this->get('http://internal/fr/probe', [
        'X-Forwarded-Host' => 'localhost',
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Port' => '443',
    ])->assertOk()->assertJson([
        'site' => 'french',
        'path' => 'probe',
        'root' => 'https://localhost',
    ]);
});

it('matches project config routes for the requested site', function () {
    $templates = dirname(__DIR__, 3).'/Support/templates';
    TemplateMode::set(TemplateMode::Site);
    Aliases::set('@templates', $templates);
    app(TemplateRoots::class)->register(TemplateMode::Site, '', $templates);
    view()->addLocation($templates);
    $primaryUid = Sites::getPrimarySite()->uid;
    $frenchUid = Sites::getSiteByHandle('french')->uid;
    $routes = app(Routes::class);
    $routes->saveRoute(new ProjectConfigRoute(uriParts: ['news'], template: 'site-routes/primary', siteUid: $primaryUid));
    $routes->saveRoute(new ProjectConfigRoute(uriParts: ['news'], template: 'site-routes/french', siteUid: $frenchUid));
    $routes->saveRoute(new ProjectConfigRoute(uriParts: ['actualites/', ['slug', '[^\\/]+']], template: 'site-routes/french', siteUid: $frenchUid));
    $routes->saveRoute(new ProjectConfigRoute(uriParts: ['site-routes/primary'], template: 'site-routes/french', siteUid: $frenchUid));
    $routes->saveRoute(new ProjectConfigRoute(uriParts: ['all-sites'], template: 'field-layout-template'));
    app(ProjectConfigRoutes::class)->register();

    $this->get('https://localhost/news')->assertOk()->assertSeeText('Primary news');
    $this->get('https://localhost/fr/news')->assertOk()->assertSeeText('French news');
    $this->get('https://localhost/fr/actualites/bonjour')->assertOk()->assertSeeText('French news bonjour');
    $this->get('https://localhost/actualites/bonjour')->assertNotFound();
    $this->get('https://localhost/fr/site-routes/primary')->assertOk()->assertSeeText('French news');
    $this->get('https://localhost/site-routes/primary')->assertOk()->assertSeeText('Primary news');
    $this->get('https://localhost/fr/all-sites')->assertOk()->assertSee('Display only');
});
