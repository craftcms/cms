<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());
});

afterEach(function () {
    putenv('GENERAL_SETTINGS_NAME');
    putenv('GENERAL_SETTINGS_LIVE');
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
            ->where('form.values.name', ProjectConfig::get('system.name'))
            ->where('form.nodes', function ($nodes): bool {
                $retryDuration = collect($nodes)
                    ->first(fn (array $node): bool => $node['control']['path'] === ['retryDuration']);

                return $retryDuration['control']['component'] === 'craft:number';
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
            ->where('form.nodes', fn ($nodes): bool => collect($nodes)
                ->every(fn (array $node): bool => $node['control']['mode'] === 'readOnly')))
        ->assertOk();
});

it('attaches settings notices to their fields', function () {
    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', function ($nodes): bool {
                $nodes = collect($nodes);
                $notices = $nodes->filter(fn (array $node): bool => in_array(
                    $node['control']['path'],
                    [['name'], ['live'], ['timeZone']],
                    strict: true,
                ));

                return $notices->count() === 3
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
        'name' => 'A new app name',
        'live' => true,
        'retryDuration' => 60,
        'timeZone' => 'America/New_York',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('system.name'))->toBe('A new app name')
        ->and(ProjectConfig::get('system.live'))->toBe(true)
        ->and(ProjectConfig::get('system.retryDuration'))->toBe(60)
        ->and(ProjectConfig::get('system.timeZone'))->toBe('America/New_York')
        ->and(date_default_timezone_get())->toBe('America/New_York');
});

it('clears retryDuration when not provided', function () {
    post(action([GeneralSettingsController::class, 'store']), [
        'name' => 'App',
        'live' => true,
        'timeZone' => 'America/New_York',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('system.retryDuration'))->toBeNull();
});

it('validates required fields', function (array $data, array $errors) {
    post(action([GeneralSettingsController::class, 'store']), $data)
        ->assertSessionHasErrors($errors);
})->with([
    'all missing' => [
        'data' => [],
        'errors' => ['name', 'live', 'timeZone'],
    ],
    'invalid timezone' => [
        'data' => [
            'name' => 'App',
            'live' => true,
            'timeZone' => 'Not/A_Timezone',
        ],
        'errors' => ['timeZone'],
    ],
    'invalid live boolean' => [
        'data' => [
            'name' => 'App',
            'live' => 'definitely',
            'timeZone' => 'America/New_York',
        ],
        'errors' => ['live'],
    ],
    'invalid retryDuration' => [
        'data' => [
            'name' => 'App',
            'live' => true,
            'retryDuration' => 'soon',
            'timeZone' => 'America/New_York',
        ],
        'errors' => ['retryDuration'],
    ],
]);

it('can save settings with environment variables', function () {
    putenv('GENERAL_SETTINGS_NAME=Env App');
    putenv('GENERAL_SETTINGS_LIVE=true');
    putenv('GENERAL_SETTINGS_TIMEZONE=America/New_York');
    Cms::config()->timezone = null;
    date_default_timezone_set('UTC');

    post(action([GeneralSettingsController::class, 'store']), [
        'name' => '$GENERAL_SETTINGS_NAME',
        'live' => '$GENERAL_SETTINGS_LIVE',
        'timeZone' => '$GENERAL_SETTINGS_TIMEZONE',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('system.name'))->toBe('$GENERAL_SETTINGS_NAME')
        ->and(ProjectConfig::get('system.live'))->toBe('$GENERAL_SETTINGS_LIVE')
        ->and(ProjectConfig::get('system.timeZone'))->toBe('$GENERAL_SETTINGS_TIMEZONE')
        ->and(date_default_timezone_get())->toBe('America/New_York');
});

it('validates resolved environment variable live values', function () {
    putenv('GENERAL_SETTINGS_LIVE=maybe');

    post(action([GeneralSettingsController::class, 'store']), [
        'name' => 'App',
        'live' => '$GENERAL_SETTINGS_LIVE',
        'timeZone' => 'America/New_York',
    ])->assertSessionHasErrors('live');
});

it('validates resolved environment variable timezone values', function () {
    putenv('GENERAL_SETTINGS_TIMEZONE=Not/A_Timezone');

    post(action([GeneralSettingsController::class, 'store']), [
        'name' => 'App',
        'live' => true,
        'timeZone' => '$GENERAL_SETTINGS_TIMEZONE',
    ])->assertSessionHasErrors('timeZone');
});

it('fails required validation for missing environment variables', function () {
    post(action([GeneralSettingsController::class, 'store']), [
        'name' => '$GENERAL_SETTINGS_MISSING',
        'live' => true,
        'timeZone' => 'America/New_York',
    ])->assertSessionHasErrors('name');
});
