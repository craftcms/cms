<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Http\Controllers\Auth\SetPasswordController;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\MessageBag;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

test('show validates required uid and code parameters', function () {
    getJson(action([SetPasswordController::class, 'show']))
        ->assertJsonValidationErrors(['uid', 'code']);
});

test('show returns invalid token response for invalid user uid', function () {
    get(cp_url(CpAuthPath::SetPassword->value, [
        'uid' => 'invalid-uuid',
        'code' => 'some-code',
    ]))->assertRedirect();
});

test('show returns invalid token response for invalid code', function () {
    $user = User::findOne();

    get(cp_url(CpAuthPath::SetPassword->value, [
        'uid' => $user->uid,
        'code' => 'invalid-code',
    ]))->assertRedirect();
});

test('show renders set-password view for valid token', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    get(cp_url('set-password', [
        'uid' => $user->uid,
        'code' => $code,
    ]))->assertInertia(fn (AssertableInertia $page) => $page
        ->component('auth/SetPassword')
        ->where('uid', $user->uid)
        ->where('code', $code)
        ->where('newUser', false));
});

test('fallback template renders set-password web component', function () {
    $html = TemplateMode::with(TemplateMode::Cp, fn () => view('craftcms::set-password', [
        'uid' => 'user-uid',
        'code' => 'token-code',
        'newUser' => false,
        'errors' => new MessageBag,
    ])->render());

    expect($html)->toContain('craft-set-password-form');
});

test('store validates required fields', function () {
    postJson(action([SetPasswordController::class, 'store']), [])
        ->assertJsonValidationErrors(['uid', 'code', 'newPassword']);
});

test('store validates password meets requirements', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([SetPasswordController::class, 'store']), [
        'uid' => $user->uid,
        'code' => $code,
        'newPassword' => 'short',
    ])->assertJsonValidationErrors(['newPassword']);
});

test('store aborts for invalid user uid', function () {
    postJson(action([SetPasswordController::class, 'store']), [
        'uid' => 'invalid-uuid',
        'code' => 'some-code',
        'newPassword' => 'validpassword123!',
    ])->assertStatus(400);
});

test('store returns invalid token response for invalid code', function () {
    $user = User::findOne();

    postJson(action([SetPasswordController::class, 'store']), [
        'uid' => $user->uid,
        'code' => 'invalid-code',
        'newPassword' => 'validpassword123!',
    ])->assertStatus(400);
});

test('store successfully sets password with valid token', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([SetPasswordController::class, 'store']), [
        'uid' => $user->uid,
        'code' => $code,
        'newPassword' => 'newvalidpassword123!',
    ])->assertOk();
});

test('store returns user status on success', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([SetPasswordController::class, 'store']), [
        'uid' => $user->uid,
        'code' => $code,
        'newPassword' => 'newvalidpassword123!',
    ])
        ->assertOk()
        ->assertJsonStructure(['status']);
});

test('show validates code format', function () {
    $user = User::findOne();

    getJson(action([SetPasswordController::class, 'show'], [
        'uid' => $user->uid,
        'code' => '', // Empty code
    ]))->assertJsonValidationErrors(['code']);
});

test('store handles very long passwords', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    $longPassword = str_repeat('a', 255).'!1A';

    postJson(action([SetPasswordController::class, 'store']), [
        'uid' => $user->uid,
        'code' => $code,
        'newPassword' => $longPassword,
    ])->assertJsonValidationErrorFor('newPassword');
});
