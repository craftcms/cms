<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User\Models\User;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::first());
});

it('needs authentication for the routes', function (string $method, array $route) {
    auth()->logout();

    $this->$method(action($route))->assertUnauthorized();

    User::first()->update(['admin' => false]);

    actingAs(User::first());

    $this->$method(action($route))->assertForbidden();
})->with([
    ['getJson', [ProjectConfigController::class, 'diff']],
    ['postJson', [ProjectConfigController::class, 'rebuild']],
    ['postJson', [ProjectConfigController::class, 'discard']],
    ['getJson', [ProjectConfigController::class, 'download']],
]);

it('can get a diff', function () {
    getJson(action([ProjectConfigController::class, 'diff']))
        ->assertOk();
});

it('can rebuild the project config', function () {
    post(action([ProjectConfigController::class, 'rebuild']))
        ->assertRedirectBack()
        ->assertSessionHas('success', t('Project config rebuilt successfully.'));
});

it('cannot rebuild the project config if it is readonly', function () {
    app(ProjectConfig::class)->readOnly = true;

    post(action([ProjectConfigController::class, 'rebuild']))
        ->assertForbidden();
});

it('can discard the project config changes', function () {
    post(action([ProjectConfigController::class, 'discard']))
        ->assertRedirectBack()
        ->assertSessionHas('success', t('External project config changes discarded.'));
});

it('cannot discard the project config changes if it is readonly', function () {
    app(ProjectConfig::class)->readOnly = true;

    post(action([ProjectConfigController::class, 'discard']))
        ->assertForbidden();
});

it('can download the project config', function () {
    getJson(action([ProjectConfigController::class, 'download']))
        ->assertDownload('project.zip');
});
