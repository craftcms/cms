<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Settings\UserSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::first());
});

it('requires authentication', function () {
    Auth::logout();

    get(action([UserSettingsController::class, 'index']))->assertRedirect();
    post(action([UserSettingsController::class, 'store']))->assertRedirect();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    // Read only
    get(action([UserSettingsController::class, 'index']))
        ->assertSee(t('Changes to these settings aren’t permitted in this environment.'));

    // Not allowed
    post(action([UserSettingsController::class, 'store']))->assertForbidden();
});

test('index', function () {
    get(action([UserSettingsController::class, 'index']))->assertOk();
});

test('store', function () {
    post(action([UserSettingsController::class, 'store']))->assertRedirectBack();
});

test('require2fa only gets saved when above team edition', function () {
    Edition::set(Edition::Solo);

    expect(ProjectConfig::get('users.require2fa'))->toBeFalsy(false);

    post(action([UserSettingsController::class, 'store'], [
        'require2fa' => true,
    ]))->assertRedirectBack();

    expect(ProjectConfig::get('users.require2fa'))->toBeFalsy(false);

    Edition::set(Edition::Team);

    post(action([UserSettingsController::class, 'store'], [
        'require2fa' => true,
    ]))->assertRedirectBack();

    expect(ProjectConfig::get('users.require2fa'))->toBe(true);
});

test('user settings only get saved when above pro edition', function (string $property, mixed $default, mixed $value) {
    Edition::set(Edition::Team);

    if ($default) {
        expect(ProjectConfig::get("users.$property"))->toBeTruthy();
    } else {
        expect(ProjectConfig::get("users.$property"))->toBeFalsy();
    }

    post(action([UserSettingsController::class, 'store'], [
        $property => $value,
    ]))->assertRedirectBack();

    if ($default) {
        expect(ProjectConfig::get("users.$property"))->toBeTruthy();
    } else {
        expect(ProjectConfig::get("users.$property"))->toBeFalsy();
    }

    Edition::set(Edition::Pro);

    post(action([UserSettingsController::class, 'store'], [
        $property => $value,
    ]))->assertRedirectBack();

    expect(ProjectConfig::get("users.$property"))->toBe($value);
})->with([
    ['requireEmailVerification', true, false],
    ['validateOnPublicRegistration', null, true],
    ['allowPublicRegistration', null, true],
    ['deactivateByDefault', null, true],
    ['defaultGroup', null, Str::uuid()->toString()],
]);
