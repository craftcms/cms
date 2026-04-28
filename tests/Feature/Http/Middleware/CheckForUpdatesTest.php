<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\CheckForUpdates;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->updates = $this->mock(Updates::class);

    Cms::config()->enableCsrfProtection = false;

    TemplateMode::set(TemplateMode::Cp);
});

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

    expect($response->getContent())->toContain('Complete the Update');
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

it('allows updater action requests when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/updater/migrate');
    $request->attributes->set('isActionRequest', true);
    $request->attributes->set('actionSegments', ['updater', 'migrate']);

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('allows health check action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/app/health-check');
    $request->attributes->set('isActionRequest', true);
    $request->attributes->set('actionSegments', ['app', 'health-check']);

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('allows migrate action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/app/migrate');
    $request->attributes->set('isActionRequest', true);
    $request->attributes->set('actionSegments', ['app', 'migrate']);

    $result = $middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('allows pluginstore install migrate action when update pending', function () {
    $this->updates->shouldReceive('isCraftUpdatePending')->andReturn(true);

    $middleware = app(CheckForUpdates::class);
    $request = Request::create('/actions/pluginstore/install/migrate');
    $request->attributes->set('isActionRequest', true);
    $request->attributes->set('actionSegments', ['pluginstore', 'install', 'migrate']);

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
    $request->attributes->set('isActionRequest', true);
    $request->attributes->set('actionSegments', ['entries', 'save']);

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
