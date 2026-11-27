<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\User;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
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

test('queued reset password emails keep the front-end site of the request', function (bool $admin) {
    Edition::set(Edition::Pro);
    $siteB = Site::factory()->create(['handle' => 'siteB', 'language' => 'nl', 'baseUrl' => 'https://example.test/b/']);
    $user = ($admin ? UserModel::factory()->admin() : UserModel::factory())->createElement();

    // Ask for the reset in a front-end request on site B
    (fn () => $this->isRunningInConsole = false)->call(app());
    Sites::setCurrentSite($siteB->id);
    Users::sendPasswordResetEmail($user);

    // Send it from a queue worker, which runs in the console on the primary site
    (fn () => $this->isRunningInConsole = true)->call(app());
    Sites::setCurrentSite(null);
    app()->setLocale(Sites::getPrimarySite()->getLanguage());
    $notification = Notification::sent(UserModel::findOrFail($user->id), ResetPasswordNotification::class)->sole();
    $mailable = unserialize(serialize($notification))->toMail($user);

    expect((string) $mailable->variables['link'])->toStartWith('https://example.test/b/'.Cms::config()->routing->getSetPasswordPath().'?code=')
        ->and($mailable->siteId)->toBe($siteB->id)
        ->and($mailable->renderedMessage()->language)->toBe('nl');
})->with([
    'user without control panel access' => false,
    'user with control panel access' => true,
]);

test('reset password notification uses the broker token', function () {
    $user = UserModel::factory()->createElement();
    $token = Password::broker()->createToken($user);
    $mailable = new ResetPasswordNotification($token, $user)->toMail($user);
    parse_str(parse_url((string) $mailable->variables['link'], PHP_URL_QUERY), $query);

    expect($query)->toMatchArray([
        'code' => $token,
        'uid' => $user->uid,
    ])
        ->and(Password::broker()->tokenExists($user, $token))->toBeTrue();
});
