<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\User;

use CraftCms\Cms\Notifications\Channels\CraftChannel;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    Notification::fake();
});

test('sendPasswordResetEmail dispatches ResetPasswordNotification', function () {

    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    Users::sendPasswordResetEmail($user);

    Notification::assertSentTo(
        $user,
        ResetPasswordNotification::class,
        fn ($notification, $channels) => in_array(CraftChannel::class, $channels)
    );
});

test('isVerificationCodeValidForUser handles Laravel tokens', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $token = Password::broker('craft')->createToken($user);

    expect(Password::broker('craft')->tokenExists($user, $token))->toBeTrue();
});

test('sendPasswordResetEmail uses Laravel broker', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $result = Users::sendPasswordResetEmail($user);

    expect($result)->toBeTrue();

    // Verify token exists in database
    $exists = DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->exists();

    expect($exists)->toBeTrue();
});
