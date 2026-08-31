<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Http\Controllers\App\HealthCheckController;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController as PluginStoreInstallController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController as PluginStoreRemoveController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Users\PasskeysController;
use CraftCms\Cms\Route\Routes as CraftRoutes;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Update\Updates;
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

test('route exceptions do not expose non-Craft routes using another method', function () {
    auth()->logout();
    Route::get(Cms::config()->actionTrigger.'/users/login', fn () => 'host route');

    get(action_url('users/login'))
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
            $routes->joinRoutePrefix([$cpTrigger, 'updates/force-update']),
            $routes->joinRoutePrefix([$cpTrigger, 'settings/general']),
            $routes->joinRoutePrefix([$actionTrigger, 'users/login']),
            trim((string) parse_url(action([InstallController::class, 'index']), PHP_URL_PATH), '/'),
            trim((string) parse_url(action([PluginStoreInstallController::class, 'migrate']), PHP_URL_PATH), '/'),
            trim((string) parse_url(action([PluginStoreRemoveController::class, 'composerRemove']), PHP_URL_PATH), '/'),
        );
});

test('pending-update actions remain reachable during Laravel maintenance', function (string $method, string|array $controller, int $status, ?string $validationError = null, bool $guest = true) {
    $this->mock(Updates::class)
        ->shouldReceive('isCraftSchemaVersionCompatible')->andReturnTrue()->byDefault()
        ->shouldReceive('isCraftUpdatePending')->andReturnTrue()->byDefault()
        ->shouldReceive('pendingMigrationHandles')->andReturn([])->byDefault();

    if ($guest) {
        auth()->logout();
    }

    $response = $method === 'GET'
        ? get(action($controller))
        : postJson(action($controller));

    $response->assertStatus($status);

    if ($validationError !== null) {
        $response->assertJsonValidationErrors($validationError);
    }
})->with([
    'health check' => ['GET', HealthCheckController::class, 200],
    'migrate' => ['POST', MigrateController::class, 204],
    'Plugin Store migrate' => ['POST', [PluginStoreInstallController::class, 'migrate'], 422, 'data', false],
]);

test('updater routes required by an active update remain reachable for workflow validation', function (string $action, string $query = '') {
    auth()->logout();

    postJson(action([UpdaterController::class, $action]).$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('data');
})->with([
    'backup' => ['backup'],
    'force update' => ['forceUpdate'],
    'server check' => ['serverCheck'],
    'revert' => ['revert'],
    'migrate' => ['migrate'],
    'precheck with query string' => ['precheck', '?site=default'],
    'recheck Composer' => ['recheckComposer'],
    'Composer install' => ['composerInstall'],
    'Composer remove' => ['composerRemove'],
    'finish' => ['finish'],
]);

test('updater index remains reachable during maintenance mode', function () {
    auth()->logout();

    post(action([UpdaterController::class, 'index']))
        ->assertOk();
});

test('a failed backup can reach its emitted revert action during maintenance', function () {
    $this->mock(Updates::class)
        ->shouldReceive('isCraftSchemaVersionCompatible')->andReturnTrue()->byDefault()
        ->shouldReceive('isCraftUpdatePending')->andReturnTrue()->byDefault()
        ->shouldReceive('areMigrationsPending')->andReturnTrue();

    $this->mock(Backups::class)
        ->shouldReceive('backup')->andThrow(new RuntimeException('Backup failed'));

    $this->mock(Composer::class)
        ->shouldReceive('install')->andReturnNull();

    $data = Crypt::encrypt(Json::encode([
        'install' => ['craft' => '6.0.0'],
        'current' => ['craftcms/cms' => '5.0.0'],
        'reverted' => false,
    ]));

    $backup = postJson(action([UpdaterController::class, 'backup']), compact('data'))
        ->assertOk()
        ->assertJsonPath('options.0.nextUrl', action([UpdaterController::class, 'revert']));

    auth()->logout();

    postJson($backup->json('options.0.nextUrl'), [
        'data' => $backup->json('data'),
    ])->assertOk()
        ->assertJsonPath('nextUrl', action([UpdaterController::class, 'finish']));
});

test('config sync finish route is accessible during maintenance mode', function () {
    postJson(action([ConfigSyncController::class, 'finish']), [
        'data' => Crypt::encrypt(Json::encode([])),
    ])->assertOk();
});
