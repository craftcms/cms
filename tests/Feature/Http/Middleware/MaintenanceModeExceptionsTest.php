<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Users\PasskeysController;
use CraftCms\Cms\Route\Routes as CraftRoutes;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Facades\Auth;
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

test('control panel actions remain accessible during maintenance mode', function () {
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp', 'accessCpWhenSystemIsOff'])
        ->createElement(['admin' => false]));
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

test('users with maintenance mode access can log into the site', function () {
    $user = UserModel::factory()
        ->withPermissions(['accessSiteWhenSystemIsOff'])
        ->createElement();
    Auth::logout();

    post(action_url('users/login'), [
        'loginName' => $user->username,
        'password' => 'password',
    ])->assertRedirect();

    expect(Auth::id())->toBe($user->id);
});

test('control panel access requires the maintenance mode permission', function () {
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement());

    get(cp_url('dashboard'))
        ->assertServiceUnavailable();
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

test('valid site tokens do not bypass maintenance mode on plain Laravel routes', function () {
    auth()->logout();
    Route::middleware('web')->get('maintenance-mode-token-test', fn () => 'ok');

    get('/maintenance-mode-token-test?'.http_build_query([
        Cms::config()->siteToken => Crypt::encrypt((string) Sites::getPrimarySite()->id),
    ]))
        ->assertServiceUnavailable();
});

test('valid but unvalidated site tokens do not bypass maintenance mode on Craft web routes', function () {
    auth()->logout();
    Route::middleware(['web', 'craft.web'])
        ->get('maintenance-mode-unvalidated-site-token-test', fn () => 'ok');

    get('/maintenance-mode-unvalidated-site-token-test?'.http_build_query([
        Cms::config()->siteToken => Crypt::encrypt((string) Sites::getPrimarySite()->id),
    ]))
        ->assertServiceUnavailable();
});

test('users without maintenance access can log out of the control panel', function () {
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement());

    get(cp_url(CpAuthPath::Logout->value))
        ->assertRedirect();

    expect(Auth::guest())->toBeTrue();
});

test('account security actions require control panel maintenance access', function () {
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement());

    postJson(action([PasskeysController::class, 'delete']), [
        'uid' => 'missing-passkey',
    ])->assertServiceUnavailable();
});

test('General Settings remains restricted to administrators during maintenance mode', function () {
    app()->maintenanceMode()->activate([
        'template' => 'Scheduled maintenance',
    ]);

    actingAs(UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement());

    get(cp_url('settings/general'))
        ->assertForbidden();

    actingAs(User::find()->admin()->one());

    get(cp_url('settings/general'))
        ->assertOk();
});

test('annotated routes are registered as maintenance exceptions', function () {
    $routes = app(CraftRoutes::class);
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
    $actionTrigger = trim(Cms::config()->actionTrigger, '/');
    $exceptions = app(PreventRequestsDuringMaintenance::class)->getExcludedPaths();

    expect($exceptions)
        ->toContain(
            $routes->joinRoutePrefix([$cpTrigger, 'updates/backup']),
            $routes->joinRoutePrefix([$cpTrigger, 'settings/general']),
            $routes->joinRoutePrefix([$actionTrigger, 'users/login']),
        )
        ->not->toContain($routes->joinRoutePrefix([$cpTrigger, 'updates/force-update']));
});

test('updater routes required by an active update remain reachable for workflow validation', function (string $action, string $query = '') {
    auth()->logout();

    postJson(action([UpdaterController::class, $action]).$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('data');
})->with([
    'backup' => ['backup'],
    'server check' => ['serverCheck'],
    'migrate' => ['migrate'],
    'precheck with query string' => ['precheck', '?site=default'],
    'Composer install' => ['composerInstall'],
]);

test('unrelated updater routes remain blocked during maintenance mode', function (string $action) {
    auth()->logout();

    postJson(action([UpdaterController::class, $action]))
        ->assertServiceUnavailable();
})->with([
    'index' => ['index'],
    'force update' => ['forceUpdate'],
    'revert' => ['revert'],
    'recheck Composer' => ['recheckComposer'],
    'Composer remove' => ['composerRemove'],
]);

test('config sync finish route is accessible during maintenance mode', function () {
    postJson(action([ConfigSyncController::class, 'finish']), [
        'data' => Crypt::encrypt(Json::encode([])),
    ])->assertOk();
});
