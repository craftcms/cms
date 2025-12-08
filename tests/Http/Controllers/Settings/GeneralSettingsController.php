<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->firstOrFail());
});

it('requires authentication', function () {
    \Illuminate\Support\Facades\Auth::logout();

    get(action([GeneralSettingsController::class, 'index']))
        ->assertRedirect();

    post(action([GeneralSettingsController::class, 'store']))
        ->assertRedirect();
});

it('can show the settings screen', function () {
    get(action([GeneralSettingsController::class, 'index']))
        ->assertOk()
        ->assertSee(t('General Settings'));
});

it('shows a readonly settings screen when admin changes is disabled', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([GeneralSettingsController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Changes to these settings aren’t permitted in this environment.'));
});

it('can save settings', function () {
    post(action([GeneralSettingsController::class, 'store']), [
        'name' => 'A new app name',
    ])->assertRedirectBack();

    expect(ProjectConfig::get('system.name'))->toBe('A new app name');
});
