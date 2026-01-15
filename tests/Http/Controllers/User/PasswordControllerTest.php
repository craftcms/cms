<?php

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->addSelect('password')->first());
    Session::passwordConfirmed();
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

it('allows anonymous access for sendPasswordResetEmail', function () {
    auth()->logout();

    $response = postJson(action([PasswordController::class, 'sendPasswordResetEmail']), [
        'loginName' => 'test@example.com',
    ]);

    expect($response->status())->not()->toBe(401);
});

it('requires loginName when not providing userId for sendPasswordResetEmail', function () {
    $response = postJson(action([PasswordController::class, 'sendPasswordResetEmail']), []);

    expect($response->json('message'))->toContain('Username or email is required.');
});

it('returns error for invalid loginName on sendPasswordResetEmail', function () {
    $response = postJson(action([PasswordController::class, 'sendPasswordResetEmail']), [
        'loginName' => 'nonexistent@example.com',
    ]);

    expect($response->json('message'))->toContain('Invalid username or email.');
});

it('returns error for non-existent userId when logged in with editUsers for sendPasswordResetEmail', function () {
    Edition::set(Edition::Pro);

    $user = User::find()->addSelect('password')->first();
    UserPermissions::saveUserPermissions($user->id, [
        'viewUsers',
        'editUsers',
    ]);

    postJson(action([PasswordController::class, 'sendPasswordResetEmail']), [
        'userId' => 999999,
    ])->assertStatus(400);
});

it('requires login for store', function () {
    auth()->logout();

    post(action([PasswordController::class, 'store']), [
        'newPassword' => 'validPassword123!',
    ])->assertRedirect(Cms::config()->cpTrigger.'/'.CpAuthPath::Login->value);
});

it('requires password confirmation for store', function () {
    Session::forget('auth.password_confirmed_at');

    postJson(action([PasswordController::class, 'store']), [
        'newPassword' => 'validPassword123!',
    ])->assertStatus(423);
});

it('aborts for users without current password', function () {
    UserModel::first()->update(['password' => null]);

    actingAs(User::find()->addSelect('password')->first());

    post(action([PasswordController::class, 'store']), [
        'newPassword' => 'validPassword123!',
    ])->assertStatus(400);
});

it('returns to previous page when newPassword is empty', function () {
    post(action([PasswordController::class, 'store']), [
        'newPassword' => '',
    ])->assertRedirect();
});

it('validates newPassword meets requirements', function () {
    post(action([PasswordController::class, 'store']), [
        'newPassword' => 'short',
    ])->assertSessionHasErrors('newPassword');
});

it('successfully saves password', function () {
    $newPassword = 'NewValidPassword123!';

    post(action([PasswordController::class, 'store']), [
        'newPassword' => $newPassword,
    ])->assertRedirectBack()->assertSessionHasNoErrors();
});

it('returns failure when save fails', function () {
    post(action([PasswordController::class, 'store']), [
        'newPassword' => 'short',
    ])->assertSessionHasErrors('newPassword');
});
