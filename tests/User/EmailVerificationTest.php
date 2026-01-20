<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\User;

use Carbon\CarbonInterval;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Notifications\Channels\CraftChannel;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    Notification::fake();
});

test('sendActivationEmail dispatches VerifyEmailNotification', function () {
    $user = UserModel::factory()->createElement();
    $user->pending = true;
    $user = User::find()->id($user->id)->one();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo(
        $user,
        VerifyEmailNotification::class,
        fn ($notification, $channels) => in_array(CraftChannel::class, $channels)
    );
});

test('sendNewEmailVerifyEmail dispatches VerifyEmailNotification', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo(
        $user,
        VerifyEmailNotification::class,
        fn ($notification, $channels) => in_array(CraftChannel::class, $channels)
    );
});

test('purgeExpiredPendingUsers deletes users with expired tokens', function () {
    $user = UserModel::factory()->createElement(['pending' => true]);
    $user = User::find()->id($user->id)->one();

    // Create a token and backdate it
    Password::broker('craft')->createToken($user);
    DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->update(['created_at' => now()->subDays(10)]);

    Cms::config()->purgePendingUsersDuration = CarbonInterval::day()->totalSeconds;

    Users::purgeExpiredPendingUsers();

    $exists = User::find()->id($user->id)->status(null)->exists();
    expect($exists)->toBeFalse();
});

test('purgeExpiredPendingUsers does not delete users with at least one active token', function () {
    $user = UserModel::factory()->createElement(['pending' => true]);
    $user = User::find()->id($user->id)->one();

    // Create an expired token
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => 'expired-token',
        'created_at' => now()->subDays(10),
    ]);

    // Create a fresh token
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => 'fresh-token',
        'created_at' => now(),
    ]);

    Cms::config()->purgePendingUsersDuration = CarbonInterval::day()->totalSeconds;

    Users::purgeExpiredPendingUsers();

    $exists = User::find()->id($user->id)->status(null)->exists();
    expect($exists)->toBeTrue();
});
