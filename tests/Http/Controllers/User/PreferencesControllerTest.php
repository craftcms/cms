<?php

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

    get(action([PreferencesController::class, 'index']))->assertRedirect('admin/login');
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
