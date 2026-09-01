<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\CheckForUpdates;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function CraftCms\Cms\cp_url;

beforeEach(function () {
    $this->updates = $this->mock(Updates::class);
    $this->updates->shouldReceive('isCraftSchemaVersionCompatible')->andReturn(true)->byDefault();

    TemplateMode::set(TemplateMode::Cp);
});

it('aborts 503 for site requests when the schema version is incompatible', function () {
    $this->updates->shouldReceive('isCraftSchemaVersionCompatible')->andReturn(false);

    app(CheckForUpdates::class)->handle(Request::create('/site-page'), fn () => 'passed');
})->throws(HttpException::class);

it('throws for CP requests when the schema version is incompatible', function () {
    $this->updates->shouldReceive('isCraftSchemaVersionCompatible')->andReturn(false);

    app(CheckForUpdates::class)->handle(
        Request::create('/'.Cms::config()->cpTrigger.'/dashboard'),
        fn () => 'passed',
    );
})->throws(RuntimeException::class);

it('passes through when no updates pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(false);
    $this->updates->shouldReceive('hasCraftVersionChanged')->andReturn(false);
    $this->updates->shouldReceive('isPluginUpdatePending')->andReturn(false);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('foo');

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('passes through for regular site request when no updates pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(false);
    $this->updates->shouldReceive('hasCraftVersionChanged')->andReturn(false);
    $this->updates->shouldReceive('isPluginUpdatePending')->andReturn(false);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/site-page');

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('passes through for action request when no updates pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(false);
    $this->updates->shouldReceive('hasCraftVersionChanged')->andReturn(false);
    $this->updates->shouldReceive('isPluginUpdatePending')->andReturn(false);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/app/health-check');

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('cleans compiled templates when craft version changed', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(false);
    $this->updates->shouldReceive('hasCraftVersionChanged')->andReturn(true);
    $this->updates->shouldReceive('updateCraftVersionInfo')->once();
    $this->updates->shouldReceive('isPluginUpdatePending')->andReturn(false);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/site-page');

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('renders db update page for cp request when craft update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);
    $this->updates->shouldReceive('wasCraftBreakpointSkipped')->andReturn(false);
    $this->updates->shouldReceive('isUpdateInfoCached')->andReturn(false);
    $this->updates->shouldReceive('areMigrationsPending')->andReturn(false);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create(Cms::config()->cpTrigger);

    $response = $middleware->handle($request, fn () => 'passed');

    expect($response->getContent())
        ->toContain('Complete the Update')
        ->toContain(sprintf('action="%s"', cp_url('updates')))
        ->not->toContain('name="action"');
});

it('renders db update page for cp request when plugin update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(false);
    $this->updates->shouldReceive('hasCraftVersionChanged')->andReturn(false);
    $this->updates->shouldReceive('isPluginUpdatePending')->andReturn(true);
    $this->updates->shouldReceive('wasCraftBreakpointSkipped')->andReturn(false);
    $this->updates->shouldReceive('isUpdateInfoCached')->andReturn(false);
    $this->updates->shouldReceive('areMigrationsPending')->andReturn(false);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create(Cms::config()->cpTrigger);

    $response = $middleware->handle($request, fn () => 'passed');

    expect($response->getContent())->toContain('Complete the Update');
});

it('aborts 503 for site request when craft update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/site-page');

    $middleware->handle($request, fn () => 'passed');
})->throws(HttpException::class);

it('aborts 503 for site request when plugin update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(false);
    $this->updates->shouldReceive('hasCraftVersionChanged')->andReturn(false);
    $this->updates->shouldReceive('isPluginUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/site-page');

    $middleware->handle($request, fn () => 'passed');
})->throws(HttpException::class);

it('allows updater CP routes when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/'.Cms::config()->cpTrigger.'/updates/migrate');
    $request->setRouteResolver(fn () => Route::getRoutes()->getByName('craft.cp.updates.migrate'));

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('allows health check action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/app/health-check');

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('allows migrate action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/'.Cms::config()->actionTrigger.'/migrate');

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('allows pluginstore install migrate action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/pluginstore/install/migrate');

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('allows users login action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);
    $this->updates->shouldReceive('wasCraftBreakpointSkipped')->andReturn(false);
    $this->updates->shouldReceive('isUpdateInfoCached')->andReturn(false);
    $this->updates->shouldReceive('areMigrationsPending')->andReturn(false);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create(Cms::config()->cpTrigger.'/actions/users/login');

    $response = $middleware->handle($request, fn () => 'passed');

    expect($response->getContent())->toContain('Complete the Update');
});

it('aborts 503 for disallowed action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/entries/save');

    $middleware->handle($request, fn () => 'passed');
})->throws(HttpException::class);

it('throws exception when craft breakpoint was skipped', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);
    $this->updates->shouldReceive('wasCraftBreakpointSkipped')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create(Cms::config()->cpTrigger);
    $request->attributes->set('isCpRequest', true);

    $middleware->handle($request, fn () => 'passed');
})->throws(RuntimeException::class);
