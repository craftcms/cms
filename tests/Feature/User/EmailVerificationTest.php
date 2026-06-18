<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\User;

use Carbon\CarbonInterval;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ActivationNotification;
use CraftCms\Cms\User\Notifications\VerifyEmailNotification;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    Notification::fake();
});

test('sendActivationEmail dispatches ActivationNotification', function () {
    $user = UserModel::factory()->createElement([
        'active' => false,
        'pending' => true,
    ]);

    Users::sendActivationEmail($user);

    Notification::assertSentTo(
        $user,
        ActivationNotification::class,
        fn ($notification, $channels) => in_array(MailChannel::class, $channels)
    );
});

test('sendNewEmailVerifyEmail dispatches VerifyEmailNotification', function () {
    $user = UserModel::factory()->createElement();

    Users::sendNewEmailVerifyEmail($user);

    Notification::assertSentTo(
        $user,
        VerifyEmailNotification::class,
        fn ($notification, $channels) => in_array(MailChannel::class, $channels)
    );
});

test('activation notification uses set password link for passwordless users', function () {
    $user = UserModel::factory()->createElement([
        'active' => false,
        'pending' => true,
        'password' => null,
    ]);
    $token = Users::setVerificationCodeOnUser($user);
    $mailable = new ActivationNotification($token)->toMail($user);

    expect($mailable->key)->toBe('account_activation')
        ->and((string) $mailable->variables['link'])->toContain('setpassword?code='.$token)
        ->and((string) $mailable->variables['link'])->not->toContain('verifyemail?code=');
});

test('activation notification uses verify email link for users with passwords', function () {
    $user = UserModel::factory()->createElement([
        'active' => false,
        'pending' => true,
    ]);
    $token = Users::setVerificationCodeOnUser($user);
    $mailable = new ActivationNotification($token)->toMail($user);

    expect($mailable->key)->toBe('account_activation')
        ->and((string) $mailable->variables['link'])->toContain('verifyemail?code='.$token)
        ->and((string) $mailable->variables['link'])->not->toContain('setpassword?code=');
});

test('email verification notification uses verify new email message and link', function () {
    $user = UserModel::factory()->createElement();
    $token = Users::setVerificationCodeOnUser($user);
    $mailable = new VerifyEmailNotification($token)->toMail($user);

    expect($mailable->key)->toBe('verify_new_email')
        ->and((string) $mailable->variables['link'])->toContain('verifyemail?code='.$token);
});

test('purgeExpiredPendingUsers deletes users with expired tokens', function () {
    $user = UserModel::factory()->createElement(['pending' => true]);
    $user = User::find()->id($user->id)->one();

    // Create a token and backdate it
    Password::broker()->createToken($user);
    DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->update(['created_at' => now()->subDays(10)]);

    Cms::config()->purgePendingUsersDuration = CarbonInterval::day()->totalSeconds;

    Users::purgeExpiredPendingUsers();

    $exists = User::find()->id($user->id)->status(null)->exists();
    expect($exists)->toBeFalse();
});

test('purgeExpiredPendingUsers does not delete users with an active token', function () {
    $user = UserModel::factory()->createElement(['pending' => true]);
    $user = User::find()->id($user->id)->one();

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
