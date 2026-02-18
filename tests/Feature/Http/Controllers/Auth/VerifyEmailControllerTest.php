<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function CraftCms\Cms\t;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

test('show redirects for invalid user id', function () {
    get(action([VerifyEmailController::class, 'show'], [
        'id' => 'invalid-uuid',
        'code' => 'some-code',
    ]))->assertRedirect(CpAuthPath::Login->value);
});

test('show redirects for invalid code', function () {
    $user = User::findOne();

    get(action([VerifyEmailController::class, 'show'], [
        'id' => $user->uid,
        'code' => 'invalid-code',
    ]))->assertRedirect(CpAuthPath::Login->value);
});

test('show renders verify-email view for valid token', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    get(action([VerifyEmailController::class, 'show'], [
        'id' => $user->uid,
        'code' => $code,
    ]))
        ->assertOk()
        ->assertSee(t('Verify your email address'));
});

test('show passes id and code to view', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    get(action([VerifyEmailController::class, 'show'], [
        'id' => $user->uid,
        'code' => $code,
    ]))
        ->assertOk()
        ->assertSee($user->uid)
        ->assertSee($code)
        ->assertSee(t('Verify your email address'));
});

test('store validates required fields', function () {
    postJson(action([VerifyEmailController::class, 'store']), [])
        ->assertJsonValidationErrors(['code', 'uid']);
});

test('store aborts for invalid user id', function () {
    postJson(action([VerifyEmailController::class, 'store']), [
        'uid' => 'invalid-uuid',
        'code' => 'some-code',
    ])->assertStatus(400);
});

test('store returns invalid token response for invalid code', function () {
    $user = User::findOne();

    postJson(action([VerifyEmailController::class, 'store']), [
        'uid' => $user->uid,
        'code' => 'invalid-code',
    ])->assertStatus(400);
});

test('store verifies email when user has unverified email and is active', function () {
    $user = UserModel::factory()->createElement([
        'active' => true,
        'pending' => false,
    ]);

    expect($user)->not->toBeNull();
    expect($user->uid)->not->toBeNull();

    $code = Users::setVerificationCodeOnUser($user);

    $unverifiedEmail = 'new.'.$user->email;

    UserModel::where('id', $user->id)->update([
        'unverifiedEmail' => $unverifiedEmail,
    ]);

    postJson(action([VerifyEmailController::class, 'store']), [
        'uid' => $user->uid,
        'code' => $code,
    ])->assertRedirect();

    $refreshedUser = User::find()
        ->id($user->id)
        ->status(null)
        ->first();

    expect($refreshedUser->email)->toBe($unverifiedEmail);
    expect($refreshedUser->unverifiedEmail)->toBeNull();
    expect($refreshedUser->active)->toBeTrue();
});

test('store activates user when pending with no unverified email', function () {
    $userModel = UserModel::factory()->create([
        'active' => false,
        'pending' => true,
    ]);

    $user = User::find()
        ->id($userModel->id)
        ->status(null)
        ->first();

    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([VerifyEmailController::class, 'store']), [
        'uid' => $user->uid,
        'code' => $code,
    ])->assertRedirect();

    $refreshedUser = User::find()
        ->id($userModel->id)
        ->status(null)
        ->first();

    expect($refreshedUser->active)->toBeTrue();
    expect($refreshedUser->pending)->toBeFalse();
});

test('store redirects user after verification', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([VerifyEmailController::class, 'store']), [
        'uid' => $user->uid,
        'code' => $code,
    ])->assertRedirect();
});
