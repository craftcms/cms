<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\File;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('needs authentication for the routes', function (string $method, array $route) {
    auth()->logout();

    $this->$method(action($route))->assertUnauthorized();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    UserPermissions::saveUserPermissions(CraftCms\Cms\User\Models\User::first()->id, ['accessCp']);

    actingAs(User::find()->one());

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
        ->assertCpMessage('success', t('Project config rebuilt successfully.'));
});

it('cannot rebuild the project config if it is readonly', function () {
    app(ProjectConfig::class)->readOnly = true;

    post(action([ProjectConfigController::class, 'rebuild']))
        ->assertForbidden();
});

it('can discard the project config changes', function () {
    post(action([ProjectConfigController::class, 'discard']))
        ->assertRedirectBack()
        ->assertCpMessage('success', t('External project config changes discarded.'));
});

it('reports when the project config files can’t be written', function () {
    $blocker = config_path('craft/project-config-write-blocker');
    File::ensureDirectoryExists(dirname($blocker));
    File::put($blocker, '');
    app(ProjectConfig::class)->folderName = 'project-config-write-blocker/project';

    try {
        post(action([ProjectConfigController::class, 'discard']))
            ->assertRedirectBack()
            ->assertCpMessage('error', t('Project config YAML files can’t be written in this environment.'));
    } finally {
        File::delete($blocker);
    }
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
