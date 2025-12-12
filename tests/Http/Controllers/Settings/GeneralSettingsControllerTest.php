<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Storage::fake('rebrand');
    actingAs(User::first());
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
        ->assertInertia(fn (AssertableInertia $page) => $page->component('SettingsGeneralPage'))
        ->assertOk();
});

it('shows a readonly settings screen when admin changes is disabled', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true))
        ->assertOk();
});

it('can save settings', function () {
    post(action([GeneralSettingsController::class, 'store']), [
        'name' => 'A new app name',
        'live' => true,
        'timeZone' => 'America/New_York',
    ])->assertRedirectBack();

    expect(ProjectConfig::get('system.name'))->toBe('A new app name');
});

it('can does not allow uploads when edition is too low', function () {
    Edition::set(Edition::Solo);
    Storage::fake('rebrand');

    post(action([GeneralSettingsController::class, 'store']), [
        'name' => 'My Site',
        'live' => true,
        'timeZone' => 'America/New_York',
        'siteIcon' => UploadedFile::fake()->image('my-icon.svg', 50, 50),
        'siteLogo' => UploadedFile::fake()->image('my-logo.jpg', 1200, 400),
    ])
        ->assertSessionHasErrors()
        ->assertRedirect();

    // Assert files are stored in correct directories
    Storage::disk('rebrand')->assertMissing('icon/my-icon.svg');
    Storage::disk('rebrand')->assertMissing('logo/my-logo.jpg');
});

it('can upload site icon and logo', function () {
    Edition::set(Edition::Pro);
    Storage::fake();

    post(action([GeneralSettingsController::class, 'store']), [
        'name' => 'My Site',
        'live' => true,
        'timeZone' => 'America/New_York',
        'siteIcon' => UploadedFile::fake()->image('my-icon.svg', 50, 50),
        'siteLogo' => UploadedFile::fake()->image('my-logo.jpg', 1200, 400),
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Assert files are stored in correct directories
    Storage::disk('rebrand')->assertExists('icon/my-icon.svg');
    Storage::disk('rebrand')->assertExists('logo/my-logo.jpg');
});
