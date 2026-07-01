<?php

use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(cp_url('myaccount/preferences'))->assertRedirect();
    patchJson(cp_url('myaccount/preferences'))->assertUnauthorized();
});

test('index shows preferences page', function () {
    $response = get(cp_url('myaccount/preferences'));

    $response
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Preferences')
            ->where('preferences.preferredLanguage', 'en-US')
            ->where('readOnly', false)
            ->has('languageOptions')
            ->has('localeOptions')
            ->has('weekStartDayOptions', 7)
            ->has('timeZoneOptions')
            ->has('subnav')
            ->has('details'));
});

test('update saves language', function () {
    /** @var User $user */
    $user = currentUser();

    expect($user->getPreference('language'))->toBe('en-US');

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'de',
    ])->assertOk();

    expect($user->getPreference('language'))->toBe('de');
});

test('update saves multiple preferences at once', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'fr',
        'weekStartDay' => 1,
        'useShapes' => true,
        'underlineLinks' => true,
    ])->assertOk();

    expect($user->getPreference('language'))->toBe('fr');
    expect($user->getPreference('weekStartDay'))->toBe(1);
    expect($user->getPreference('useShapes'))->toBeTrue();
    expect($user->getPreference('underlineLinks'))->toBeTrue();
});

test('update handles blank values', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLocale' => '__blank__',
        'timeZone' => '__blank__',
    ])->assertOk();

    expect($user->getPreference('locale'))->toBeNull();
    expect($user->getPreference('timeZone'))->toBeNull();
});

test('update saves notification preferences', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'notificationDuration' => 5000,
        'notificationPosition' => 'start-end',
    ])->assertOk();

    expect($user->getPreference('notificationDuration'))->toBe(5000);
    expect($user->getPreference('notificationPosition'))->toBe('start-end');
});

test('update saves slideout position preference', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'slideoutPosition' => 'start',
    ])->assertOk();

    expect($user->getPreference('slideoutPosition'))->toBe('start');
});

test('update saves admin-only preferences for admin users', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'showFieldHandles' => true,
        'showExceptionView' => true,
        'profileTemplates' => true,
    ])->assertOk();

    expect($user->getPreference('showFieldHandles'))->toBeTrue();
    expect($user->getPreference('showExceptionView'))->toBeTrue();
    expect($user->getPreference('profileTemplates'))->toBeTrue();
});

test('update rejects admin-only preferences for non-admin users', function () {
    $user = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->create();

    actingAs($user->asElement());

    patchJson(cp_url('myaccount/preferences'), [
        'showFieldHandles' => true,
    ])->assertForbidden();
});

test('update preserves existing preferences when not provided', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'de',
        'weekStartDay' => 0,
    ])->assertOk();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'es',
    ])->assertOk();

    expect($user->getPreference('language'))->toBe('es');
    expect($user->getPreference('weekStartDay'))->toBe(0);
});

test('update handles boolean preferences correctly', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'useShapes' => false,
        'underlineLinks' => false,
        'disableAutofocus' => true,
    ])->assertOk();

    expect($user->getPreference('useShapes'))->toBeFalse();
    expect($user->getPreference('underlineLinks'))->toBeFalse();
    expect($user->getPreference('disableAutofocus'))->toBeTrue();
});

test('update validates preference values', function () {
    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'not-a-locale',
        'weekStartDay' => 8,
        'timeZone' => 'not-a-timezone',
        'notificationDuration' => 1234,
        'notificationPosition' => 'top-right',
        'slideoutPosition' => 'left',
    ])->assertJsonValidationErrors([
        'preferredLanguage',
        'weekStartDay',
        'timeZone',
        'notificationDuration',
        'notificationPosition',
        'slideoutPosition',
    ]);
});

test('update returns success message', function () {
    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'en-US',
    ])
        ->assertOk()
        ->assertJson([
            'message' => t('Preferences saved.'),
        ]);
});
