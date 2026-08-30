<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    app()->maintenanceMode()->activate([]);
});

afterEach(function () {
    app()->maintenanceMode()->deactivate();
});

test('control panel actions remain accessible during maintenance mode', function () {
    $cpTrigger = Cms::config()->cpTrigger;
    $actionTrigger = Cms::config()->actionTrigger;

    postJson("/{$cpTrigger}/{$actionTrigger}/app/api-headers")
        ->assertOk();
});

test('control panel login remains accessible during maintenance mode', function () {
    auth()->logout();

    get(cp_url(CpAuthPath::Login->value))
        ->assertOk();
});

test('users with maintenance mode access can log into the control panel', function () {
    $user = UserModel::factory()
        ->withPermissions(['accessCp', 'accessCpWhenSystemIsOff'])
        ->createElement(['admin' => false]);
    auth()->logout();

    post(cp_url(CpAuthPath::Login->value), [
        'loginName' => $user->username,
        'password' => 'password',
    ])->assertRedirect();

    auth()->forgetGuards();

    get(cp_url('dashboard'))
        ->assertOk();
});

test('control panel access requires the maintenance mode permission', function () {
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement());

    get(cp_url('dashboard'))
        ->assertServiceUnavailable();
});

test('control panel access allows the maintenance mode permission', function () {
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp', 'accessCpWhenSystemIsOff'])
        ->createElement());

    get(cp_url('dashboard'))
        ->assertOk();
});

test('public GraphQL requests are blocked during maintenance mode', function () {
    auth()->logout();

    get(action_url('graphql/api').'?query=%7B__typename%7D')
        ->assertServiceUnavailable();
});

test('public-only actions are blocked during maintenance mode', function () {
    auth()->logout();

    get(action_url('users/impersonate-with-token'))
        ->assertServiceUnavailable();
});

test('plain Laravel routes are blocked during maintenance mode', function () {
    auth()->logout();
    Route::middleware('web')->get('maintenance-mode-test', fn () => 'ok');

    get('/maintenance-mode-test')
        ->assertServiceUnavailable();
});

test('migrate action route is accessible during maintenance mode', function () {
    $actionTrigger = Cms::config()->actionTrigger;

    post("/{$actionTrigger}/migrate")
        ->assertNoContent();
});

test('updater routes with query strings are accessible during maintenance mode', function () {
    $data = Crypt::encrypt(Json::encode([
        'postPrecheckState' => [],
    ]));

    postJson(action([UpdaterController::class, 'precheck']).'?site=default', [
        'data' => $data,
    ])->assertOk();
});

test('active updater routes remain accessible to authenticated update users during maintenance mode', function () {
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp', 'performUpdates'])
        ->createElement(['admin' => false]));

    $response = postJson(action([UpdaterController::class, 'backup']));

    expect($response->getStatusCode())->not->toBe(503);
});

test('only updater routes required by an active update remain accessible during maintenance mode', function (string $action, bool $accessible) {
    auth()->logout();

    $response = postJson(action([UpdaterController::class, $action]));

    if ($accessible) {
        expect($response->getStatusCode())->not->toBe(503);

        return;
    }

    $response->assertServiceUnavailable();
})->with([
    'index' => ['index', false],
    'force update' => ['forceUpdate', false],
    'backup' => ['backup', true],
    'server check' => ['serverCheck', true],
    'revert' => ['revert', false],
    'migrate' => ['migrate', true],
    'precheck' => ['precheck', true],
    'recheck Composer' => ['recheckComposer', false],
    'Composer install' => ['composerInstall', true],
    'Composer remove' => ['composerRemove', false],
    'finish' => ['finish', true],
]);

test('config sync finish route is accessible during maintenance mode', function () {
    postJson(action([ConfigSyncController::class, 'finish']), [
        'data' => Crypt::encrypt(Json::encode([])),
    ])->assertOk();
});
