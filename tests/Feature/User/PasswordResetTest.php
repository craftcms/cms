<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\User;

use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ResetPasswordNotification;
use Illuminate\Notifications\Channels\MailChannel;
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
        UserModel::findOrFail($user->id),
        ResetPasswordNotification::class,
        fn ($notification, $channels) => in_array(MailChannel::class, $channels)
    );
});

test('isVerificationCodeValidForUser handles Laravel tokens', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $token = Password::broker()->createToken($user);

    expect(Password::broker()->tokenExists($user, $token))->toBeTrue();
});

test('reset password notification uses the broker token', function () {
    $user = UserModel::factory()->createElement();
    $token = Password::broker()->createToken($user);
    $mailable = new ResetPasswordNotification($token)->toMail($user);
    parse_str(parse_url((string) $mailable->variables['link'], PHP_URL_QUERY), $query);

    expect($query)->toMatchArray([
        'code' => $token,
        'uid' => $user->uid,
    ])
        ->and(Password::broker()->tokenExists($user, $token))->toBeTrue();
});
