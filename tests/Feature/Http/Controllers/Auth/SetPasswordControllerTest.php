<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Auth\SetPasswordController;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

test('show validates required id and code parameters', function () {
    getJson(action([SetPasswordController::class, 'show']))
        ->assertJsonValidationErrors(['id', 'code']);
});

test('show returns invalid token response for invalid user id', function () {
    get(action([SetPasswordController::class, 'show'], [
        'id' => 'invalid-uuid',
        'code' => 'some-code',
    ]))->assertRedirect();
});

test('show returns invalid token response for invalid code', function () {
    $user = User::findOne();

    get(action([SetPasswordController::class, 'show'], [
        'id' => $user->uid,
        'code' => 'invalid-code',
    ]))->assertRedirect();
});

test('show renders set-password view for valid token', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    get(action([SetPasswordController::class, 'show'], [
        'id' => $user->uid,
        'code' => $code,
    ]))->assertOk();
});

test('store validates required fields', function () {
    postJson(action([SetPasswordController::class, 'store']), [])
        ->assertJsonValidationErrors(['id', 'code', 'newPassword']);
});

test('store validates password meets requirements', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([SetPasswordController::class, 'store']), [
        'id' => $user->uid,
        'code' => $code,
        'newPassword' => 'short',
    ])->assertJsonValidationErrors(['newPassword']);
});

test('store aborts for invalid user id', function () {
    postJson(action([SetPasswordController::class, 'store']), [
        'id' => 'invalid-uuid',
        'code' => 'some-code',
        'newPassword' => 'validpassword123!',
    ])->assertStatus(400);
});

test('store returns invalid token response for invalid code', function () {
    $user = User::findOne();

    postJson(action([SetPasswordController::class, 'store']), [
        'id' => $user->uid,
        'code' => 'invalid-code',
        'newPassword' => 'validpassword123!',
    ])->assertStatus(400);
});

test('store successfully sets password with valid token', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([SetPasswordController::class, 'store']), [
        'id' => $user->uid,
        'code' => $code,
        'newPassword' => 'newvalidpassword123!',
    ])->assertOk();
});

test('store returns user status on success', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    postJson(action([SetPasswordController::class, 'store']), [
        'id' => $user->uid,
        'code' => $code,
        'newPassword' => 'newvalidpassword123!',
    ])
        ->assertOk()
        ->assertJsonStructure(['status']);
});

test('show validates code format', function () {
    $user = User::findOne();

    getJson(action([SetPasswordController::class, 'show'], [
        'id' => $user->uid,
        'code' => '', // Empty code
    ]))->assertJsonValidationErrors(['code']);
});

test('store handles very long passwords', function () {
    $user = User::findOne();
    $code = Users::setVerificationCodeOnUser($user);

    $longPassword = str_repeat('a', 255).'!1A';

    postJson(action([SetPasswordController::class, 'store']), [
        'id' => $user->uid,
        'code' => $code,
        'newPassword' => $longPassword,
    ])->assertJsonValidationErrorFor('newPassword');
});
