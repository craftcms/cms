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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

it('routes site requests under a base path with encoded characters', function () {
    Site::factory()->create(['handle' => 'japanese', 'baseUrl' => 'https://localhost/日本']);
    Sites::refreshSites();

    $this->get('https://localhost/%E6%97%A5%E6%9C%AC/probe')->assertOk()->assertJson([
        'site' => 'japanese',
        'path' => 'probe',
        'requestPath' => 'probe',
        'url' => 'https://localhost/%E6%97%A5%E6%9C%AC/probe',
    ]);
});

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
});

it('matches project config routes under the site base path', function () {
    TemplateMode::set(TemplateMode::Site);
    Aliases::set('@templates', dirname(__DIR__, 3).'/Support/templates');
    app(TemplateRoots::class)->register(TemplateMode::Site, '', dirname(__DIR__, 3).'/Support/templates');
    view()->addLocation(dirname(__DIR__, 3).'/Support/templates');
    app(Routes::class)->saveRoute(new ProjectConfigRoute(uriParts: ['news'], template: 'field-layout-template'));
    app(ProjectConfigRoutes::class)->register();

    $this->get('https://localhost/fr/news')->assertOk()->assertSee('Display only');
});
