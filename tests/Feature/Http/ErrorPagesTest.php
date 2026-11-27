<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

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

it('drops cache headers queued before an exception', function (bool $debug) {
    config(['app.debug' => $debug]);

    Route::middleware(['web', 'craft', 'craft.web'])->get('throws', function () {
        ResponseHeaders::setCache(3600);
        ResponseHeaders::add('X-Test', 'value');

        throw new RuntimeException('Boom');
    });

    $response = $this->get('/throws')
        ->assertInternalServerError()
        ->assertHeaderMissing('X-Test');

    expect($response->headers->hasCacheControlDirective('public'))->toBeFalse();
})->with([
    'debug mode off' => false,
    'debug mode on' => true,
]);

it('drops cache headers queued before Laravel renders an exception', function (Throwable $e) {
    Route::middleware(['web', 'craft', 'craft.web'])->get('throws', function () use ($e) {
        ResponseHeaders::setCache(3600);

        throw $e;
    });

    $response = $this->get('/throws');

    expect($response->headers->get('Cache-Control'))->toBe('no-cache, private');
})->with([
    'authentication' => fn () => new AuthenticationException(redirectTo: '/login'),
    'validation' => fn () => ValidationException::withMessages(['email' => 'Invalid']),
]);

it('drops cache headers queued before an exception that renders its own response', function () {
    Route::middleware(['web', 'craft', 'craft.web'])->get('throws', function () {
        ResponseHeaders::setCache(3600);

        throw new class('Nope') extends RuntimeException
        {
            public function render(): Response
            {
                return response('Nope', 400);
            }
        };
    });

    $response = $this->get('/throws')->assertBadRequest();

    expect($response->headers->get('Cache-Control'))->toBe('no-cache, private');
});

it('applies cache headers queued by the error template', function () {
    config(['app.debug' => false]);
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/errors/404.twig", '{% expires in 1 hour %}Not found');

    Route::middleware(['web', 'craft', 'craft.web'])->get('throws', function () {
        ResponseHeaders::setCache(60);
        abort(404);
    });

    $response = $this->get('/throws')->assertNotFound();

    expect($response->headers->get('Cache-Control'))->toBe('max-age=3600, public');
});

it('drops cache headers queued by an error template that fails', function () {
    config(['app.debug' => false]);
    File::ensureDirectoryExists("{$this->tempDir}/errors");
    File::put("{$this->tempDir}/errors/500.twig", '{% expires in 1 hour %}{% exit 500 %}');

    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('throws', fn () => throw new RuntimeException('Boom'));

    $response = $this->get('/throws')->assertInternalServerError();

    expect($response->headers->get('Cache-Control'))->toBe('no-cache, private');
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

it('falls back to the control panel’s error page on the site, without linking to the control panel', function () {
    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('gone', fn () => abort(404, 'Gone fishing'));

    $this->get('/gone')
        ->assertNotFound()
        ->assertSee('Page Not Found')
        ->assertSee('Gone fishing')
        ->assertDontSee(Cms::config()->routing->cpTrigger);
});

it('hides an unexpected exception’s message on the control panel’s error page', function () {
    config(['app.debug' => false]);

    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('throws', fn () => throw new RuntimeException('secret details'));

    $this->get('/throws')
        ->assertInternalServerError()
        ->assertSee('Internal Server Error')
        ->assertSee('An error occurred while processing your request.')
        ->assertDontSee('secret details');
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

    $this->get('/'.Cms::config()->routing->cpTrigger.'/does-not-exist')
        ->assertNotFound()
        ->assertSee('Page Not Found')
        ->assertDontSee('site-404');
});
