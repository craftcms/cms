<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->addSelect('password')->first());
});

it('requires login', function () {
    auth()->logout();

    get(action([PasswordController::class, 'index']))->assertRedirect(Cms::config()->cpTrigger.'/login');
});

test('index', function () {
    get(action([PasswordController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Password'));
});

it('requires login for requireReset', function () {
    auth()->logout();

    postJson(action([PasswordController::class, 'requireReset']))->assertUnauthorized();
});

it('requires login for removeResetRequirement', function () {
    auth()->logout();

    postJson(action([PasswordController::class, 'removeResetRequirement']))->assertUnauthorized();
});

it('requires administrateUsers permission for requireReset and removeResetRequirement', function () {
    Gate::before(function ($user, $ability) {
        if ($ability === 'administrateUsers') {
            return false;
        }

        return null;
    });

    postJson(action([PasswordController::class, 'requireReset']), [
        'userId' => auth()->id(),
    ])->assertForbidden();

    postJson(action([PasswordController::class, 'removeResetRequirement']), [
        'userId' => auth()->id(),
    ])->assertForbidden();
});

it('validates userId exists for requireReset and removeResetRequirement', function (array $action) {
    postJson(action($action), ['userId' => 999999])
        ->assertJsonValidationErrorFor('userId');
})->with([
    [[PasswordController::class, 'requireReset']],
    [[PasswordController::class, 'removeResetRequirement']],
]);

it('validates userId is required for requireReset and removeResetRequirement', function (array $action) {
    postJson(action($action))->assertJsonValidationErrorFor('userId');
})->with([
    [[PasswordController::class, 'requireReset']],
    [[PasswordController::class, 'removeResetRequirement']],
]);

test('requireReset sets passwordResetRequired to true', function () {
    $user = User::findOne();
    $user->passwordResetRequired = false;
    Craft::$app->getElements()->saveElement($user, false);
    $userId = $user->id;

    postJson(action([PasswordController::class, 'requireReset']), [
        'userId' => $userId,
    ])->assertOk();

    expect(User::find()->id($userId)->status(null)->addSelect('passwordResetRequired')->first()->passwordResetRequired)->toBeTrue();
});

test('removeResetRequirement sets passwordResetRequired to false', function () {
    $user = User::findOne();
    $user->passwordResetRequired = true;
    Craft::$app->getElements()->saveElement($user, false);
    $userId = $user->id;

    postJson(action([PasswordController::class, 'removeResetRequirement']), [
        'userId' => $userId,
    ])->assertOk();

    expect(User::find()->id($userId)->status(null)->addSelect('passwordResetRequired')->first()->passwordResetRequired)->toBeFalse();
});

it('requires login for passwordResetUrl', function () {
    auth()->logout();

    postJson(action([PasswordController::class, 'passwordResetUrl']))->assertUnauthorized();
});

it('requires administrateUsers permission for passwordResetUrl', function () {
    Gate::before(function ($user, $ability) {
        if ($ability === 'administrateUsers') {
            return false;
        }

        return null;
    });

    postJson(action([PasswordController::class, 'passwordResetUrl']), [
        'userId' => auth()->id(),
    ])->assertForbidden();
});

it('validates userId exists for passwordResetUrl', function () {
    Session::passwordConfirmed();

    postJson(action([PasswordController::class, 'passwordResetUrl']), ['userId' => 999999])
        ->assertJsonValidationErrorFor('userId');
});

it('validates userId is required for passwordResetUrl', function () {
    Session::passwordConfirmed();

    postJson(action([PasswordController::class, 'passwordResetUrl']))
        ->assertJsonValidationErrorFor('userId');
});

it('requires password confirmation for passwordResetUrl', function () {
    Session::forget('auth.password_confirmed_at');

    postJson(action([PasswordController::class, 'passwordResetUrl']), [
        'userId' => auth()->id(),
    ])->assertStatus(400);
});

it('returns password reset URL when password is confirmed', function () {
    Session::passwordConfirmed();

    $user = User::findOne();

    postJson(action([PasswordController::class, 'passwordResetUrl']), [
        'userId' => $user->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url']);
});

it('returns password reset URL when current password is sent along', function () {
    $user = User::findOne();

    postJson(action([PasswordController::class, 'passwordResetUrl']), [
        'userId' => $user->id,
        'currentPassword' => 'craftcms2018!!',
    ])
        ->assertOk()
        ->assertJsonStructure(['url']);
});
