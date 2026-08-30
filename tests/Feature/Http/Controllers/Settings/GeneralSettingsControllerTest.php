<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    app()->maintenanceMode()->deactivate();
    actingAs(User::find()->one());
});

afterEach(function () {
    putenv('GENERAL_SETTINGS_NAME');
    putenv('GENERAL_SETTINGS_TIMEZONE');
    putenv('GENERAL_SETTINGS_MISSING');
    app()->maintenanceMode()->deactivate();
    File::delete(storage_path('framework/maintenance.php'));
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
            ->where('form.values.name', ProjectConfig::get('system.name'))
            ->where('form.values.maintenanceMode', false)
            ->where('form.values.retryDuration', ProjectConfig::get('system.retryDuration'))
            ->where('form.nodes', function ($nodes): bool {
                $maintenanceMode = collect($nodes)
                    ->first(fn (array $node): bool => $node['control']['path'] === ['maintenanceMode']);
                $retryDuration = collect($nodes)
                    ->first(fn (array $node): bool => $node['control']['path'] === ['retryDuration']);

                return $maintenanceMode['control']['component'] === 'craft:lightswitch'
                    && $retryDuration['control']['component'] === 'craft:number'
                    && collect($nodes)->pluck('control.path')->doesntContain(['live']);
            })
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
            ->where('readOnly', false)
            ->where('form.nodes', function ($nodes): bool {
                $controls = collect($nodes)->mapWithKeys(fn (array $node): array => [
                    $node['control']['path'][0] => $node['control']['mode'],
                ]);

                return $controls->get('maintenanceMode') === 'editable'
                    && $controls->get('retryDuration') === 'readOnly'
                    && $controls->get('name') === 'readOnly'
                    && $controls->get('timeZone') === 'readOnly';
            }))
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

    expect(ProjectConfig::get('system.name'))->toBe('A new app name')
        ->and(ProjectConfig::get('system.retryDuration'))->toBe(60)
        ->and(ProjectConfig::get('system.timeZone'))->toBe('America/New_York')
        ->and(app()->maintenanceMode()->data()['retry'])->toBe(60)
        ->and(date_default_timezone_get())->toBe('America/New_York');
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

it('shows and disables active maintenance mode', function () {
    Cms::config()->allowAdminChanges = false;
    app()->maintenanceMode()->activate(['retry' => 60]);

    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.values.maintenanceMode', true))
        ->assertOk();

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
    ])
        ->assertRedirectBack();

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('toggles maintenance mode when admin changes are disabled', function () {
    Cms::config()->allowAdminChanges = false;
    ProjectConfig::set('system.retryDuration', 60);
    $systemName = ProjectConfig::get('system.name');
    $timeZone = ProjectConfig::get('system.timeZone');

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => true,
        'name' => 'Changed name',
        'retryDuration' => 120,
        'timeZone' => 'America/New_York',
    ])
        ->assertRedirectBack();

    expect(app()->isDownForMaintenance())->toBeTrue()
        ->and(app()->maintenanceMode()->data()['retry'])->toBe(60)
        ->and(ProjectConfig::get('system.name'))->toBe($systemName)
        ->and(ProjectConfig::get('system.retryDuration'))->toBe(60)
        ->and(ProjectConfig::get('system.timeZone'))->toBe($timeZone);

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
    ])
        ->assertRedirectBack();

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('preserves an existing maintenance mode payload', function () {
    Cms::config()->allowAdminChanges = false;
    ProjectConfig::set('system.retryDuration', 60);
    app()->maintenanceMode()->activate([
        'retry' => 30,
        'secret' => 'maintenance-secret',
    ]);

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => true,
    ])
        ->assertRedirectBack();

    expect(app()->maintenanceMode()->data())->toBe([
        'retry' => 60,
        'secret' => 'maintenance-secret',
    ]);
});

it('dispatches maintenance mode events and removes the pre-rendered file', function () {
    Cms::config()->allowAdminChanges = false;
    Event::fake([
        MaintenanceModeDisabled::class,
        MaintenanceModeEnabled::class,
    ]);

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => true,
    ])
        ->assertRedirectBack();

    Event::assertDispatched(MaintenanceModeEnabled::class);

    $maintenanceFile = storage_path('framework/maintenance.php');
    File::put($maintenanceFile, '<?php return;');

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
    ])
        ->assertRedirectBack();

    Event::assertDispatched(MaintenanceModeDisabled::class);
    $this->assertFileDoesNotExist($maintenanceFile);
});

it('keeps maintenance mode active when the pre-rendered file cannot be removed', function () {
    Cms::config()->allowAdminChanges = false;
    app()->maintenanceMode()->activate([]);
    Event::fake([MaintenanceModeDisabled::class]);

    $maintenanceFile = storage_path('framework/maintenance.php');
    File::put($maintenanceFile, '<?php return;');
    File::partialMock()
        ->shouldReceive('delete')
        ->once()
        ->with($maintenanceFile)
        ->andReturnFalse();

    post(action([GeneralSettingsController::class, 'store']), [
        'maintenanceMode' => false,
    ])->assertServerError();

    expect(app()->isDownForMaintenance())->toBeTrue();
    Event::assertNotDispatched(MaintenanceModeDisabled::class);
});

it('restricts maintenance mode changes to administrators', function (bool $initiallyActive) {
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

    expect(app()->isDownForMaintenance())->toBe($initiallyActive);
})->with([
    'enable' => [false],
    'disable' => [true],
]);
