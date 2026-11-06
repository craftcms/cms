<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Auth\SessionTimeout;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-site-session-test-'.uniqid();

    File::ensureDirectoryExists($this->tempDir);
    File::put($this->tempDir.'/reads.twig', "{{ currentUser ? 'in' : 'out' }} {{ session('notice') }} {{ errors.first('email') }} {{ old('email') }}");
    File::put($this->tempDir.'/writes.twig', "{% do session().flash('notice', 'Saved') %}written");
    File::put($this->tempDir.'/form.twig', '<form method="post">{{ csrfInput() }}</form>');

    Aliases::set('@templates', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('sends no cookies for a site page that only reads the session', function () {
    get('/reads')
        ->assertOk()
        ->assertSeeText('out')
        ->assertCookieMissing(config('session.cookie'))
        ->assertCookieMissing('XSRF-TOKEN');
});

it('saves the session when a site page writes to it', function () {
    $response = get('/writes')
        ->assertOk()
        ->assertCookie(config('session.cookie'))
        ->assertCookie('XSRF-TOKEN');

    $saved = unserialize(app(SessionManager::class)->driver()->getHandler()->read($response->getCookie(config('session.cookie'))->getValue()));

    expect($saved['notice'] ?? null)->toBe('Saved');
});

it('saves the session with the CSRF token a site page prints', function () {
    $response = get('/form')
        ->assertOk()
        ->assertCookie(config('session.cookie'))
        ->assertCookie('XSRF-TOKEN');

    preg_match('/name="_token" value="([^"]+)"/', $response->getContent(), $matches);
    $sessionId = $response->getCookie(config('session.cookie'))->getValue();
    $saved = unserialize(app(SessionManager::class)->driver()->getHandler()->read($sessionId));

    expect($matches[1] ?? null)->not->toBeNull()
        ->and($saved['_token'] ?? null)->toBe($matches[1]);
});

it('refreshes the session cookie for requests that arrive with one', function () {
    $this->withCookie(config('session.cookie'), Str::random(40))
        ->get('/reads')
        ->assertOk()
        ->assertCookie(config('session.cookie'));
});

it('tracks signed-in users’ session activity on site pages', function () {
    actingAs(User::findOne());

    get('/reads')
        ->assertOk()
        ->assertSeeText('in')
        ->assertHeader(SessionTimeout::HEADER);
});

it('opts routes in with withLazySession()', function () {
    Route::middleware(['web', 'craft', 'craft.web'])->get('eager-session', fn () => 'eager');
    Route::middleware(['web', 'craft', 'craft.web'])->withLazySession()->get('lazy-session', fn () => 'lazy');
    Route::middleware(['web', 'craft', 'craft.web'])->get('lazy-route-session', fn () => 'lazy')->withLazySession();
    Route::withLazySession()->middleware(['web', 'craft', 'craft.web'])->get('lazy-first-session', fn () => 'lazy');

    get('/eager-session')->assertOk()->assertCookie(config('session.cookie'));
    get('/lazy-session')->assertOk()->assertCookieMissing(config('session.cookie'));
    get('/lazy-route-session')->assertOk()->assertCookieMissing(config('session.cookie'));
    get('/lazy-first-session')->assertOk()->assertCookieMissing(config('session.cookie'));
});
