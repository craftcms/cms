<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\MessageBag;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

test('show redirects for invalid user uid', function () {
    get(cp_url(CpAuthPath::VerifyEmail->value, [
        'uid' => 'invalid-uuid',
        'code' => 'some-code',
    ]))->assertRedirect(CpAuthPath::Login->value);
});

test('show redirects for invalid code', function () {
    $user = User::findOne();

    get(cp_url(CpAuthPath::VerifyEmail->value, [
        'uid' => $user->uid,
        'code' => 'invalid-code',
    ]))->assertRedirect(CpAuthPath::Login->value);
});

test('show renders verify-email view for valid token', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    get(cp_url(CpAuthPath::VerifyEmail->value, [
        'uid' => $user->uid,
        'code' => $code,
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/VerifyEmail')
            ->where('uid', $user->uid)
            ->where('code', $code));
});

test('fallback template renders verify-email web component', function () {
    $html = TemplateMode::with(TemplateMode::Cp, fn () => view('craftcms::verify-email', [
        'uid' => 'user-uid',
        'code' => 'token-code',
        'errors' => new MessageBag(['code' => ['Invalid verification code.']]),
    ])->render());

    expect($html)->toContain('craft-verify-email-form');
    expect($html)->toContain('initial-error');
});

test('store validates required fields', function () {
    postJson(action([VerifyEmailController::class, 'store']), [])
        ->assertJsonValidationErrors(['code', 'uid']);
});

test('store aborts for invalid user uid', function () {
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
