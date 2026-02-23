<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\SettingsIndexController;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires authentication', function () {
    \Illuminate\Support\Facades\Auth::logout();

    get(action(SettingsIndexController::class))->assertRedirect();
});

it('loads the settings page', function () {
    get(action(SettingsIndexController::class))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('SettingsIndexPage'))
        ->assertOk();
});

it('shows a readonly settings screen when admin changes is disabled', function () {
    Cms::config()->allowAdminChanges = false;

    get(action(SettingsIndexController::class))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true))
        ->assertOk();
});
