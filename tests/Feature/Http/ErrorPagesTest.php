<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-error-pages-test-'.uniqid();

    File::ensureDirectoryExists($this->tempDir);

    Aliases::set('@templates', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('renders site error templates for unknown URLs', function (string $file, string $contents) {
    File::ensureDirectoryExists(dirname("{$this->tempDir}/{$file}"));
    File::put("{$this->tempDir}/{$file}", $contents);

    $this->get('/does-not-exist')
        ->assertNotFound()
        ->assertSee('custom-404 code=404', false);
})->with([
    'twig' => ['errors/404.twig', 'custom-404 code={{ exception.statusCode }}'],
    'private twig' => ['errors/_404.twig', 'custom-404 code={{ exception.statusCode }}'],
    'blade' => ['errors/404.blade.php', 'custom-404 code={{ $exception->getStatusCode() }}'],
]);

it('does not render private templates requested by URL', function () {
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/_secret.twig", 'secret-template');
    File::put("{$this->tempDir}/errors/404.twig", 'custom-404');

    $this->get('/_secret')
        ->assertNotFound()
        ->assertSee('custom-404')
        ->assertDontSee('secret-template');
});

it('prefers the current site’s error templates', function () {
    $site = Site::factory()->create(['baseUrl' => 'https://localhost/fr']);
    Sites::refreshSites();

    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::ensureDirectoryExists("{$this->tempDir}/{$site->handle}/errors");
    File::put("{$this->tempDir}/errors/404.twig", 'global-404');
    File::put("{$this->tempDir}/{$site->handle}/errors/_404.twig", 'site-404');

    $this->get('https://localhost/fr/does-not-exist')
        ->assertNotFound()
        ->assertSee('site-404')
        ->assertDontSee('global-404');

    $this->get('/does-not-exist')
        ->assertNotFound()
        ->assertSee('global-404')
        ->assertDontSee('site-404');
});

it('renders site error templates for exceptions outside debug mode', function () {
    config(['app.debug' => false]);
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/errors/500.twig", 'custom-500 {{ statusCode }} {{ message }}');

    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('throws', fn () => throw new RuntimeException('Boom'));

    $this->get('/throws')
        ->assertInternalServerError()
        ->assertSee('custom-500 500 Server Error')
        ->assertDontSee('Boom');

    $this->getJson('/throws')
        ->assertInternalServerError()
        ->assertJson(['message' => 'Server Error']);
});

it('shows exception details to admins who chose to see them outside debug mode', function () {
    config(['app.debug' => false]);
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/errors/500.twig", 'custom-500');

    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('throws', fn () => throw new RuntimeException('Boom'));

    $admin = User::find()->admin()->one();

    actingAs($admin);
    $this->get('/throws')->assertSee('custom-500')->assertDontSee('Boom');

    app(Users::class)->saveUserPreferences($admin, ['showExceptionView' => true]);
    $this->get('/throws')->assertInternalServerError()->assertSee('Boom')->assertDontSee('custom-500');

    expect(config('app.debug'))->toBeFalse();
});

it('falls back to offline and catch-all error templates', function () {
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/errors/offline.twig", 'offline {{ statusCode }}');
    File::put("{$this->tempDir}/errors/_error.twig", 'catch-all {{ statusCode }} {{ message }}');

    Route::middleware(['web', 'craft', 'craft.web'])->group(function () {
        Route::get('forbidden', fn () => abort(403, 'No entry'));
        Route::get('unavailable', fn () => abort(503));
    });

    $this->get('/forbidden')->assertForbidden()->assertSee('catch-all 403 No entry');
    $this->get('/unavailable')->assertServiceUnavailable()->assertSee('offline 503');
});

it('falls back to control panel error templates on the site', function () {
    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('gone', fn () => abort(404, 'Gone fishing'));

    $this->get('/gone')
        ->assertNotFound()
        ->assertSee('Page Not Found')
        ->assertSee('Gone fishing');
});

it('redirects requests that would 404 using redirect rules', function () {
    config(['craft.redirects' => [
        'old/<slug:{slug}>' => 'new/<slug>',
        ['from' => 'Helpdesk.aspx', 'to' => '/account/tickets', 'caseSensitive' => true, 'statusCode' => 301],
    ]]);

    $this->get('/old/my-post')->assertRedirect(Url::siteUrl('new/my-post'));
    $this->get('/Helpdesk.aspx')->assertStatus(301)->assertRedirect('/account/tickets');
    $this->get('/helpdesk.aspx')->assertNotFound();
});

it('leaves errors on routes outside Craft to Laravel', function () {
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/errors/404.twig", 'custom-404');

    Route::middleware('web')->get('app-route', fn () => abort(404));

    $this->get('/app-route')
        ->assertNotFound()
        ->assertDontSee('custom-404');
});

it('renders control panel error templates instead of site ones', function () {
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/errors/404.twig", 'site-404');

    $this->get('/'.Cms::config()->cpTrigger.'/does-not-exist')
        ->assertNotFound()
        ->assertSee('Page Not Found')
        ->assertDontSee('site-404');
});
