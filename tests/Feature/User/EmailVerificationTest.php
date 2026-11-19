<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\User;

use Carbon\CarbonInterval;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
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
    $mailable = new ActivationNotification($token, $user)->toMail($user);
    parse_str(parse_url((string) $mailable->variables['link'], PHP_URL_QUERY), $query);

    expect($mailable->key)->toBe('account_activation')
        ->and($query)->toMatchArray([
            'code' => $token,
            'uid' => $user->uid,
        ])
        ->and((string) $mailable->variables['link'])->not->toContain('verifyemail?code=');
});

test('activation notification uses verify email link for users with passwords', function () {
    $user = UserModel::factory()->createElement([
        'active' => false,
        'pending' => true,
    ]);
    $token = Users::setVerificationCodeOnUser($user);
    $mailable = new ActivationNotification($token, $user)->toMail($user);
    parse_str(parse_url((string) $mailable->variables['link'], PHP_URL_QUERY), $query);

    expect($mailable->key)->toBe('account_activation')
        ->and($query)->toMatchArray([
            'code' => $token,
            'uid' => $user->uid,
        ])
        ->and((string) $mailable->variables['link'])->not->toContain('setpassword?code=');
});

test('email verification notification uses verify new email message and link', function () {
    $user = UserModel::factory()->createElement();
    $token = Users::setVerificationCodeOnUser($user);
    $mailable = new VerifyEmailNotification($token, $user)->toMail($user);
    parse_str(parse_url((string) $mailable->variables['link'], PHP_URL_QUERY), $query);

    expect($mailable->key)->toBe('verify_new_email')
        ->and($query)->toMatchArray([
            'code' => $token,
            'uid' => $user->uid,
        ]);
});

test('queued activation and email verification emails keep the front-end site of the request', function (string $notification, bool $admin) {
    Edition::set(Edition::Pro);
    $siteB = Site::factory()->create(['handle' => 'siteB', 'language' => 'nl', 'baseUrl' => 'https://example.test/b/']);
    $user = ($admin ? UserModel::factory()->admin() : UserModel::factory())->createElement(['pending' => true]);

    // Send it in a front-end request on site B
    (fn () => $this->isRunningInConsole = false)->call(app());
    Sites::setCurrentSite($siteB->id);
    $notification === ActivationNotification::class ? Users::sendActivationEmail($user) : Users::sendNewEmailVerifyEmail($user);

    // Build the message in a queue worker, which runs in the console on the primary site
    (fn () => $this->isRunningInConsole = true)->call(app());
    Sites::setCurrentSite(null);
    app()->setLocale(Sites::getPrimarySite()->getLanguage());
    $mailable = unserialize(serialize(Notification::sent($user, $notification)->sole()))->toMail($user);

    expect((string) $mailable->variables['link'])->toStartWith('https://example.test/b/'.Cms::config()->getVerifyEmailPath().'?code=')
        ->and($mailable->siteId)->toBe($siteB->id)
        ->and($mailable->renderedMessage()->language)->toBe('nl');
})->with([
    'activation' => ActivationNotification::class,
    'email verification' => VerifyEmailNotification::class,
])->with([
    'user without control panel access' => false,
    'user with control panel access' => true,
]);

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
