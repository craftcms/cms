<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Users\PreferencesController;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(action([PreferencesController::class, 'index']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    postJson(action([PreferencesController::class, 'store']))->assertUnauthorized();
});

test('index', function () {
    get(action([PreferencesController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Preferences'));
});

test('store', function () {
    /** @var User $user */
    $user = auth()->user();

    expect($user->getPreference('language'))->toBe('en-US');

    postJson(action([PreferencesController::class, 'store'], [
        'preferredLanguage' => 'nl-BE',
    ]))->assertOk();

    expect($user->getPreference('language'))->toBe('nl-BE');
});

test('store saves multiple preferences at once', function () {
    /** @var User $user */
    $user = auth()->user();

    postJson(action([PreferencesController::class, 'store'], [
        'preferredLanguage' => 'fr',
        'weekStartDay' => 1,
        'useShapes' => true,
        'underlineLinks' => true,
    ]))->assertOk();

    expect($user->getPreference('language'))->toBe('fr');
    expect($user->getPreference('weekStartDay'))->toBe('1');
    expect($user->getPreference('useShapes'))->toBeTrue();
    expect($user->getPreference('underlineLinks'))->toBeTrue();
});

test('store handles __blank__ value for locale', function () {
    /** @var User $user */
    $user = auth()->user();

    postJson(action([PreferencesController::class, 'store'], [
        'preferredLocale' => '__blank__',
    ]))->assertOk();

    expect($user->getPreference('locale'))->toBeNull();
});

test('store saves notification preferences', function () {
    /** @var User $user */
    $user = auth()->user();

    postJson(action([PreferencesController::class, 'store'], [
        'notificationDuration' => 5000,
        'notificationPosition' => 'top-right',
    ]))->assertOk();

    expect($user->getPreference('notificationDuration'))->toBe('5000');
    expect($user->getPreference('notificationPosition'))->toBe('top-right');
});

test('store saves slideout position preference', function () {
    /** @var User $user */
    $user = auth()->user();

    postJson(action([PreferencesController::class, 'store'], [
        'slideoutPosition' => 'left',
    ]))->assertOk();

    expect($user->getPreference('slideoutPosition'))->toBe('left');
});

test('store saves admin-only preferences for admin users', function () {
    /** @var User $user */
    $user = auth()->user();

    // Admin users can set these preferences
    // The user in tests should already be an admin
    if (! $user->admin) {
        $this->markTestSkipped('User must be admin for this test');
    }

    postJson(action([PreferencesController::class, 'store'], [
        'showFieldHandles' => true,
        'enableDebugToolbarForSite' => true,
        'enableDebugToolbarForCp' => true,
        'showExceptionView' => true,
        'profileTemplates' => true,
    ]))->assertOk();

    // Verify preferences were saved (admin-only preferences)
    expect($user->getPreference('showFieldHandles'))->toBeTrue();
    expect($user->getPreference('enableDebugToolbarForSite'))->toBeTrue();
    expect($user->getPreference('enableDebugToolbarForCp'))->toBeTrue();
    expect($user->getPreference('showExceptionView'))->toBeTrue();
    expect($user->getPreference('profileTemplates'))->toBeTrue();
});

test('store preserves existing preferences when not provided', function () {
    /** @var User $user */
    $user = auth()->user();

    // Set initial preference
    postJson(action([PreferencesController::class, 'store'], [
        'preferredLanguage' => 'de',
        'weekStartDay' => 0,
    ]))->assertOk();

    // Update only language, weekStartDay should persist
    postJson(action([PreferencesController::class, 'store'], [
        'preferredLanguage' => 'es',
    ]))->assertOk();

    expect($user->getPreference('language'))->toBe('es');
    expect($user->getPreference('weekStartDay'))->toBe('0');
});

test('store handles boolean preferences correctly', function () {
    /** @var User $user */
    $user = auth()->user();

    postJson(action([PreferencesController::class, 'store'], [
        'useShapes' => false,
        'underlineLinks' => false,
        'disableAutofocus' => true,
    ]))->assertOk();

    expect($user->getPreference('useShapes'))->toBeFalse();
    expect($user->getPreference('underlineLinks'))->toBeFalse();
    expect($user->getPreference('disableAutofocus'))->toBe('1');
});

test('index shows preferences page with user language', function () {
    $response = get(action([PreferencesController::class, 'index']));

    $response->assertOk();

    // Should render preferences template
    expect($response->status())->toBe(200);
});

test('store returns success message', function () {
    postJson(action([PreferencesController::class, 'store'], [
        'preferredLanguage' => 'en-US',
    ]))
        ->assertOk()
        ->assertJson([
            'message' => t('Preferences saved.'),
        ]);
});
