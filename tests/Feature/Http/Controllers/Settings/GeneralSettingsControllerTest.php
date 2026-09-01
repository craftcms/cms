<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());
});

afterEach(function () {
    putenv('GENERAL_SETTINGS_NAME');
    putenv('GENERAL_SETTINGS_TIMEZONE');
    putenv('GENERAL_SETTINGS_MISSING');
});

it('requires authentication', function () {
    Auth::logout();

    get(action([GeneralSettingsController::class, 'index']))
        ->assertRedirect();

    post(action([GeneralSettingsController::class, 'store']))
        ->assertRedirect();
});

it('can show the settings screen', function () {
    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Form')
            ->where('craft.maintenanceMode', false)
            ->where('form.values.name', ProjectConfig::get('system.name'))
            ->where('form.values.maintenanceMode', false)
            ->where('form.values.retryDuration', ProjectConfig::get('system.retryDuration'))
            ->where('submit', [
                'method' => 'post',
                'url' => action([GeneralSettingsController::class, 'store']),
            ]))
        ->assertOk();
});

it('shows a readonly settings screen when admin changes is disabled', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('craft.readOnly', true)
            ->where('readOnly', false))
        ->assertOk();
});

it('attaches settings notices to their fields', function () {
    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', function ($nodes): bool {
                $nodes = collect($nodes);
                $notices = $nodes->filter(fn (array $node): bool => in_array(
                    $node['control']['path'],
                    [['name'], ['timeZone']],
                    strict: true,
                ));

                return $notices->count() === 2
                    && $nodes->every(fn (array $node): bool => $node['component'] === 'craft:field')
                    && $notices->every(fn (array $node): bool => isset($node['props']['tipHtml']));
            }))
        ->assertOk();
});

it('exposes timezone options through the settings form', function () {
    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', function ($nodes): bool {
                $timeZone = collect($nodes)
                    ->first(fn (array $node): bool => $node['control']['path'] === ['timeZone']);

                return collect($timeZone['control']['props']['options'])
                    ->pluck('value')
                    ->contains('America/New_York');
            }))
        ->assertOk();
});

it('can save settings', function () {
    Cms::config()->timezone = null;
    date_default_timezone_set('UTC');

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => true,
        'name' => 'A new app name',
        'retryDuration' => 60,
        'timeZone' => 'America/New_York',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    Auth::logout();

    get('/')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '60');

    actingAs(User::find()->admin()->one());

    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('craft.maintenanceMode', true)
            ->where('form.values.name', 'A new app name')
            ->where('form.values.retryDuration', 60)
            ->where('form.values.timeZone', 'America/New_York'))
        ->assertOk();

    expect(date_default_timezone_get())->toBe('America/New_York');
});

it('validates required fields', function (array $data, array $errors) {
    post(action([GeneralSettingsController::class, 'store']), $data)
        ->assertSessionHasErrors($errors);
})->with([
    'all missing' => [
        'data' => [],
        'errors' => ['maintenanceMode', 'name', 'timeZone'],
    ],
    'invalid timezone' => [
        'data' => [
            'maintenanceMode' => false,
            'name' => 'App',
            'timeZone' => 'Not/A_Timezone',
        ],
        'errors' => ['timeZone'],
    ],
    'invalid maintenance mode' => [
        'data' => [
            'maintenanceMode' => 'sometimes',
            'name' => 'App',
            'timeZone' => 'UTC',
        ],
        'errors' => ['maintenanceMode'],
    ],
    'invalid retry duration' => [
        'data' => [
            'maintenanceMode' => false,
            'name' => 'App',
            'retryDuration' => 'soon',
            'timeZone' => 'UTC',
        ],
        'errors' => ['retryDuration'],
    ],
]);

it('can save settings with environment variables', function () {
    putenv('GENERAL_SETTINGS_NAME=Env App');
    putenv('GENERAL_SETTINGS_TIMEZONE=America/New_York');
    Cms::config()->timezone = null;
    date_default_timezone_set('UTC');

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
        'name' => '$GENERAL_SETTINGS_NAME',
        'timeZone' => '$GENERAL_SETTINGS_TIMEZONE',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('system.name'))->toBe('$GENERAL_SETTINGS_NAME')
        ->and(ProjectConfig::get('system.timeZone'))->toBe('$GENERAL_SETTINGS_TIMEZONE')
        ->and(date_default_timezone_get())->toBe('America/New_York');
});

it('validates resolved environment variable timezone values', function () {
    putenv('GENERAL_SETTINGS_TIMEZONE=Not/A_Timezone');

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
        'name' => 'App',
        'timeZone' => '$GENERAL_SETTINGS_TIMEZONE',
    ])->assertSessionHasErrors('timeZone');
});

it('fails required validation for missing environment variables', function () {
    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
        'name' => '$GENERAL_SETTINGS_MISSING',
        'timeZone' => 'America/New_York',
    ])->assertSessionHasErrors('name');
});

it('toggles maintenance mode when admin changes are disabled', function () {
    Cms::config()->allowAdminChanges = false;
    ProjectConfig::set('system.retryDuration', 60);
    $systemName = ProjectConfig::get('system.name');
    $timeZone = ProjectConfig::get('system.timeZone');
    Route::middleware('web')->get('maintenance-toggle-status', fn () => 'Application is live');

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => true,
        'name' => 'Changed name',
        'retryDuration' => 120,
        'timeZone' => 'America/New_York',
    ])
        ->assertRedirectBack();

    Auth::logout();

    get('/maintenance-toggle-status')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '60');

    actingAs(User::find()->admin()->one());

    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.values.maintenanceMode', true)
            ->where('form.values.name', $systemName)
            ->where('form.values.retryDuration', 60)
            ->where('form.values.timeZone', $timeZone))
        ->assertOk();

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
    ])
        ->assertRedirectBack();

    Auth::logout();

    get('/maintenance-toggle-status')
        ->assertOk()
        ->assertSeeText('Application is live');
});

it('updates retry responses without breaking the existing maintenance secret', function () {
    Cms::config()->allowAdminChanges = false;
    ProjectConfig::set('system.retryDuration', 60);
    Route::middleware('web')->get('maintenance-retry-status', fn () => 'Application is live');
    app()->maintenanceMode()->activate([
        'retry' => 30,
        'secret' => 'maintenance-secret',
    ]);

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => true,
    ])
        ->assertRedirectBack();

    Auth::logout();

    get('/maintenance-retry-status')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '60');

    get('/maintenance-secret')
        ->assertRedirect('/');
});

it('removes the retry header when retry duration is cleared', function () {
    app()->maintenanceMode()->activate(['retry' => 60]);

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => true,
        'name' => 'App',
        'timeZone' => 'UTC',
    ])
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    Auth::logout();

    get('/')
        ->assertServiceUnavailable()
        ->assertHeaderMissing('Retry-After');
});

it('restricts maintenance mode changes to administrators', function (bool $initiallyActive) {
    Route::middleware('web')->get('maintenance-authorization-status', fn () => 'Application is live');
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp', 'accessCpWhenSystemIsOff'])
        ->createElement(['admin' => false]));

    if ($initiallyActive) {
        app()->maintenanceMode()->activate([]);
    }

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => ! $initiallyActive,
        'name' => ProjectConfig::get('system.name'),
        'timeZone' => ProjectConfig::get('system.timeZone'),
    ])
        ->assertForbidden();

    Auth::logout();

    if ($initiallyActive) {
        get('/maintenance-authorization-status')->assertServiceUnavailable();

        return;
    }

    get('/maintenance-authorization-status')
        ->assertOk()
        ->assertSeeText('Application is live');
})->with([
    'enable' => [false],
    'disable' => [true],
]);
