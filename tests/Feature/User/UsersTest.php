<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\UserActivated;
use CraftCms\Cms\User\Events\UserActivating;
use CraftCms\Cms\User\Events\UserDeactivated;
use CraftCms\Cms\User\Events\UserLocked;
use CraftCms\Cms\User\Events\UserSuspended;
use CraftCms\Cms\User\Events\UserUnlocked;
use CraftCms\Cms\User\Events\UserUnsuspended;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Notifications\ActivationNotification;
use CraftCms\Cms\User\Users;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->users = app(Users::class);

    Edition::set(Edition::Team);
});

test('ensureUserByEmail', function () {
    $email = fake()->email();

    expect(UserModel::where('email', $email)->exists())->toBeFalse();

    $this->users->ensureUserByEmail($email);

    expect(UserModel::where('email', $email)->exists())->toBeTrue();

    expect($this->users->getUserByUsernameOrEmail($email))->toBeInstanceOf(User::class);
});

test('retrieval', function () {
    $user = UserModel::factory()->pending()->createElement();

    expect($this->users->getUserById($user->id))->toBeInstanceOf(User::class);
    expect($this->users->getUserByUid($user->uid))->toBeInstanceOf(User::class);
    expect($this->users->getUserByUsernameOrEmail($user->email))->toBeInstanceOf(User::class);
    expect($this->users->getUserByUsernameOrEmail($user->username))->toBeInstanceOf(User::class);
});

test('getUserByUserNameOrEmail gets active users first', function () {
    $inactive = UserModel::factory()->createElement([
        'id' => null,
        'active' => false,
        'pending' => false,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    $active = UserModel::factory()->createElement([
        'id' => null,
        'active' => true,
        'pending' => false,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    expect($this->users->getUserByUsernameOrEmail('john')->id)->toBe($active->id);
});

test('getUserByUserNameOrEmail gets pending users first', function () {
    $nonPending = UserModel::factory()->create([
        'active' => false,
        'pending' => false,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    $pending = UserModel::factory()->create([
        'id' => null,
        'active' => false,
        'pending' => true,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    expect($this->users->getUserByUsernameOrEmail('john')->id)->toBe($pending->id);
});

test('userPreferences', function () {
    $user = UserModel::factory()->pending()->createElement();

    expect($this->users->getUserPreferences($user->id))->toBeEmpty();

    $this->users->saveUserPreferences($user, ['foo' => 'bar']);

    expect($this->users->getUserPreferences($user->id))->toBe(['foo' => 'bar']);
    expect($this->users->getUserPreference($user->id, 'foo'))->toBe('bar');
});

test('getActivationUrl', function () {
    $user = UserModel::factory()->pending()->createElement();

    expect($this->users->getActivationUrl($user))->toBeUrl();
});

test('getEmailVerifyUrl', function () {
    $user = UserModel::factory()->pending()->createElement();

    expect($this->users->getEmailVerifyUrl($user))->toBeUrl();
});

test('getPasswordResetUrl', function () {
    $user = UserModel::factory()->pending()->createElement();

    expect($this->users->getPasswordResetUrl($user))->toBeUrl();
});

test('removeCredentials', function () {
    $userElement = UserModel::factory()->createElement();

    $user = UserModel::findOrFail($userElement->id);
    $user->update([
        'active' => true,
        'pending' => true,
        'password' => Hash::make('password'),
    ]);

    expect($user->active)->toBeTrue();
    expect($user->pending)->toBeTrue();
    expect($user->password)->not()->toBeNull();

    $this->users->removeCredentials($userElement);

    $user = UserModel::findOrFail($userElement->id);

    expect($user->active)->toBeFalse();
    expect($user->pending)->toBeFalse();
    expect($user->password)->toBeNull();
});

test('saveUserPhoto rejects non image filenames', function () {
    $user = UserModel::factory()->pending()->createElement();

    $this->users->saveUserPhoto(
        storage_path('framework/testing/avatar.gif'),
        $user,
        'avatar.js',
        'image/gif',
    );
})->throws(ImageException::class, 'User photo must be an image that Craft can manipulate.');

test('user activation', function () {
    $user = UserModel::factory()->pending()->createElement();

    expect($user->getStatus())->toBe(User::STATUS_PENDING);

    $this->users->activateUser($user);

    expect($user->active)->toBeTrue()->and($user->pending)->toBeFalse();

    $user = $this->users->getUserById($user->id);

    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);
});

test('unverify updates the supplied user and is repeatable', function () {
    $user = UserModel::factory()->active()->createElement(['unverifiedEmail' => null]);

    $this->users->unverifyEmailForUser($user);
    $this->users->unverifyEmailForUser($user);

    expect($user->unverifiedEmail)->toBe($user->email)
        ->and(UserModel::findOrFail($user->id)->unverifiedEmail)->toBe($user->email);
});

test('account transitions do not publish failed writes', function (string $method, bool $throws) {
    $user = UserModel::factory()->locked()->pending()->createElement([
        'active' => false,
        'suspended' => $method === 'unsuspendUser',
        'unverifiedEmail' => in_array($method, ['activateUser', 'verifyEmailForUser']) ? 'verified@example.test' : null,
    ]);
    $attributes = ['active', 'pending', 'locked', 'suspended', 'unverifiedEmail', 'email', 'username', 'invalidLoginCount', 'lastLoginDate', 'lastInvalidLoginDate'];
    $original = UserModel::findOrFail($user->id)->only($attributes);
    Event::fake([UserActivated::class, UserDeactivated::class, UserSuspended::class, UserUnsuspended::class, UserUnlocked::class, UserLocked::class]);
    Event::listen('eloquent.saving: '.UserModel::class, fn () => $throws ? throw new RuntimeException('Write failed') : false);

    expect(fn () => $this->users->$method($user))->toThrow($throws ? RuntimeException::class : InvalidElementException::class);

    foreach ($attributes as $attribute) {
        expect($user->$attribute)->toBe($original[$attribute]);
    }
    expect(UserModel::findOrFail($user->id)->only($attributes))->toBe($original);
    if (! $throws) {
        expect($user->errors()->isNotEmpty())->toBeTrue();
    }
    Event::assertNothingDispatched();
})->with(['activateUser', 'deactivateUser', 'unlockUser', 'suspendUser', 'unsuspendUser', 'verifyEmailForUser', 'unverifyEmailForUser', 'handleValidLogin', 'handleInvalidLogin', 'setVerificationCodeOnUser'])
    ->with(['save false' => false, 'exception' => true]);

test('invalid account transitions preserve flags and validation feedback', function (string $method) {
    $user = UserModel::factory()->createElement(['active' => false, 'pending' => false]);
    $user->email = ' invalid ';
    Password::shouldReceive('broker')->never();
    Event::fake([UserActivated::class]);

    expect(fn () => $this->users->$method($user))->toThrow(InvalidElementException::class);

    expect($user->active)->toBeFalse()->and($user->pending)->toBeFalse()
        ->and($user->email)->toBe('invalid')->and($user->errors()->has('email'))->toBeTrue()
        ->and(UserModel::findOrFail($user->id)->pending)->toBeFalse();
    Event::assertNothingDispatched();
})->with(['activateUser', 'setVerificationCodeOnUser']);

test('user activation email as username with an unverified email', function (string $method) {
    $user = UserModel::factory()->pending()->createElement(['unverifiedEmail' => 'verified@example.test']);

    // Set useEmailAsUsername to true and add an unverified email.
    Cms::config()->useEmailAsUsername = true;

    Elements::saveElement($user);

    Event::listen(UserActivated::class, function ($event) use ($user) {
        expect($event->user)->toBe($user)->and($user->active)->toBeTrue()
            ->and($user->email)->toBe('verified@example.test');
    });
    $this->users->$method($user);

    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);
    expect($user->username)->toBe($user->email);
    expect(UserModel::findOrFail($user->id)->email)->toBe($user->email)
        ->and($user->unverifiedEmail)->toBeNull();
})->with(['activateUser', 'verifyEmailForUser']);

test('user activation email as username with no unverified email', function () {
    $user = UserModel::factory()->pending()->createElement();

    // Run the same test as above but without an unverified email.
    Cms::config()->useEmailAsUsername = true;

    // Remove the unverifiedEmail property from the user record - meaning no username will be set.
    $user->unverifiedEmail = null;

    $this->users->activateUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);
    expect($user->username)->not()->toBe($user->email);
});

test('standalone verification remains saved when activation is cancelled', function () {
    $user = UserModel::factory()->pending()->createElement(['active' => false, 'unverifiedEmail' => 'verified@example.test']);
    Event::fake([UserActivated::class]);
    Event::listen(UserActivating::class, fn ($event) => $event->isValid = false);

    expect(fn () => $this->users->verifyEmailForUser($user))->toThrow(InvalidElementException::class);

    expect($user->email)->toBe('verified@example.test')->and($user->unverifiedEmail)->toBeNull()
        ->and($user->active)->toBeFalse()->and($user->pending)->toBeTrue()
        ->and(UserModel::findOrFail($user->id)->email)->toBe($user->email);
    Event::assertNothingDispatched();
});

test('unlock', function () {
    $user = UserModel::factory()->locked()->createElement();

    expect($user->locked)->toBeTrue();

    $this->users->unlockUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->locked)->toBeFalse();
    expect($user->lockoutDate)->toBeNull();
    expect($user->invalidLoginCount)->toBeNull();
});

test('suspend', function () {
    $user = UserModel::factory()->active()->createElement();

    expect($user->suspended)->toBeFalse();
    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);

    $this->users->suspendUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->suspended)->toBeTrue();
    expect($user->getStatus())->toBe(User::STATUS_SUSPENDED);
});

test('unsuspend', function () {
    $user = UserModel::factory()->suspended()->createElement();

    expect($user->suspended)->toBeTrue();
    expect($user->getStatus())->toBe(User::STATUS_SUSPENDED);

    $this->users->unsuspendUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->suspended)->toBeFalse();
    expect($user->getStatus())->not()->toBe(User::STATUS_SUSPENDED);
});

test('shunned messages', function () {
    $user = UserModel::factory()->create();

    expect(DB::table(Table::SHUNNEDMESSAGES)->count())->toBe(0);
    expect($this->users->hasUserShunnedMessage($user->id, 'Some message'))->toBeFalse();

    $this->users->shunMessageForUser($user->id, 'Some message');

    expect(DB::table(Table::SHUNNEDMESSAGES)->count())->toBe(1);
    expect($this->users->hasUserShunnedMessage($user->id, 'Some message'))->toBeTrue();

    $this->users->shunMessageForUser($user->id, 'Some message');

    expect(DB::table(Table::SHUNNEDMESSAGES)->count())->toBe(1);
    expect($this->users->hasUserShunnedMessage($user->id, 'Some message'))->toBeTrue();

    $this->users->unshunMessageForUser($user->id, 'Some message');

    expect(DB::table(Table::SHUNNEDMESSAGES)->count())->toBe(0);
    expect($this->users->hasUserShunnedMessage($user->id, 'Some message'))->toBeFalse();
});

test('set verification code', function (bool $tokenFailure) {
    $user = UserModel::factory()->createElement(['active' => false, 'pending' => false]);

    if ($tokenFailure) {
        Password::shouldReceive('getDefaultDriver')->once()->andReturn('users');
        Password::shouldReceive('broker->createToken')->once()->andThrow(new RuntimeException('Token failed'));
        expect(fn () => $this->users->setVerificationCodeOnUser($user))->toThrow(RuntimeException::class, 'Token failed');
        expect($user->pending)->toBeFalse()->and(UserModel::findOrFail($user->id)->pending)->toBeFalse();

        return;
    }

    $verificationCode = $this->users->setVerificationCodeOnUser($user);

    expect(strlen((string) $verificationCode))->toBe(64);
    expect($user->pending)->toBeTrue()->and(UserModel::findOrFail($user->id)->pending)->toBeTrue();
})->with(['success' => false, 'token failure' => true]);

test('assignUserToGroups', function () {
    Edition::set(Edition::Pro);

    $user = UserModel::factory()->active()->createElement();

    $group1 = UserGroup::factory()->create();
    $group2 = UserGroup::factory()->create();
    $group3 = UserGroup::factory()->create();

    expect($user->getGroups())->toBeEmpty();

    $this->users->assignUserToGroups($user->id, [$group1->id, $group2->id]);

    $user = $this->users->getUserById($user->id);

    expect($user->getGroups())->toHaveCount(2);

    $this->users->assignUserToGroups($user->id, [$group3->id]);

    $user = $this->users->getUserById($user->id);

    expect($user->getGroups())->toHaveCount(1);
});

test('assignUserToDefaultGroup', function () {
    $user = UserModel::factory()->active()->createElement();

    $group = UserGroup::factory()->create();

    ProjectConfig::set('users.defaultGroup', $group->uid);

    $this->users->assignUserToDefaultGroup($user);

    $user = $this->users->getUserById($user->id);

    expect($user->getGroups())->toHaveCount(1);
    expect($user->getGroups()[0]->name)->toBe($group->name);
});

test('handleInvalidLogin', function () {
    Date::setTestNow(now('UTC'));

    $user = UserModel::factory()->active()->createElement();

    $this->users->handleInvalidLogin($user);

    $user = UserModel::findOrFail($user->id);

    expect($user->invalidLoginCount)->toBe(1);
    expect($user->invalidLoginWindowStart->format('Y-m-d H:i:s'))->toBe(now('UTC')->startOfSecond()->format('Y-m-d H:i:s'));
    expect($user->lastInvalidLoginDate->format('Y-m-d H:i:s'))->toBe(now('UTC')->startOfSecond()->format('Y-m-d H:i:s'));
    expect($user->lastLoginAttemptIp)->toBeNull();
});

test('handleInvalidLogin stores ip', function () {
    Cms::config()->storeUserIps = true;

    $user = UserModel::factory()->active()->createElement();

    $this->users->handleInvalidLogin($user);

    $user = UserModel::findOrFail($user->id);

    expect($user->lastLoginAttemptIp)->not()->toBeNull();
});

test('handleInvalidLogin without limit', function () {
    Cms::config()->maxInvalidLogins = false;
    Cms::config()->storeUserIps = true;

    $user = UserModel::factory()->active()->createElement();

    $this->users->handleInvalidLogin($user);

    $user = UserModel::findOrFail($user->id);

    expect($user->invalidLoginWindowStart)->toBeNull();
    expect($user->invalidLoginCount)->toBeNull();
    expect($user->lockoutDate)->toBeNull();
    expect($user->lastLoginAttemptIp)->not()->toBeNull();
    expect($user->lastInvalidLoginDate)->not()->toBeNull();
});

test('handleInvalidLogin with max outside window', function () {
    Date::setTestNow(now('UTC'));

    $user = UserModel::factory()->active()->createElement([
        'invalidLoginWindowStart' => null,
    ]);

    Cms::config()->maxInvalidLogins = 1;

    $this->users->handleInvalidLogin($user);

    $user = UserModel::findOrFail($user->id);

    expect($user->invalidLoginCount)->toBe(1);
    expect($user->locked)->toBeFalse();
    expect($user->invalidLoginWindowStart->format('Y-m-d H:i:s'))->toBe(now('UTC')->startOfSecond()->format('Y-m-d H:i:s'));
    expect($user->lockoutDate)->toBeNull();
});

test('handleInvalidLogin inside window', function () {
    Event::fake();
    Date::setTestNow(now('UTC'));

    $activeUser = UserModel::factory()->active()->createElement();

    UserModel::findOrFail($activeUser->id)->update([
        'invalidLoginWindowStart' => now(),
        'invalidLoginCount' => 1,
    ]);

    // 3 max - that's important for a little bit later. Also a 2 day invalidLoginWindowDuration
    Cms::config()->maxInvalidLogins = 3;
    Cms::config()->invalidLoginWindowDuration = 172800;

    // 1 st invalid login.
    $this->users->handleInvalidLogin($activeUser);

    // This should just increment the invalidLoginCount
    $user = UserModel::findOrFail($activeUser->id);
    expect($user->invalidLoginCount)->toBe(2);
    expect($user->locked)->toBeFalse();

    // 2nd invalid login.
    $this->users->handleInvalidLogin($activeUser);

    Event::assertDispatchedOnce(UserLocked::class);

    $user = UserModel::findOrFail($user->id);
    expect($user->locked)->toBeTrue();
    expect($user->invalidLoginCount)->toBeNull();
    expect($user->invalidLoginWindowStart)->toBeNull();
    expect($user->lockoutDate->format('Y-m-d H:i:s'))->toBe(now('UTC')->startOfSecond()->format('Y-m-d H:i:s'));
});

test('handleValidLogin', function () {
    $activeUser = UserModel::factory()->active()->createElement();

    $user = UserModel::findOrFail($activeUser->id);

    expect($user->lastLoginDate)->toBeNull();

    $this->users->handleValidLogin($activeUser);

    expect($user->fresh()->lastLoginDate)->not()->toBeNull();
    expect($user->fresh()->lastLoginAttemptIp)->toBeNull();
});

test('handleValidLogin with ip collection', function () {
    Cms::config()->storeUserIps = true;

    $activeUser = UserModel::factory()->active()->createElement();

    $user = UserModel::findOrFail($activeUser->id);

    expect($user->fresh()->lastLoginAttemptIp)->toBeNull();

    $this->users->handleValidLogin($activeUser);

    expect($user->fresh()->lastLoginAttemptIp)->not()->toBeNull();
});

test('handleValidLogin clears values', function () {
    $activeUser = UserModel::factory()->active()->createElement();

    $user = UserModel::findOrFail($activeUser->id);
    $user->update([
        'invalidLoginWindowStart' => now(),
        'invalidLoginCount' => 5,
    ]);

    $this->users->handleValidLogin($activeUser);

    $user = UserModel::findOrFail($activeUser->id);
    expect($user->invalidLoginWindowStart)->toBeNull();
    expect($user->invalidLoginCount)->toBeNull();
});

test('setVerificationCodeOnUser creates a valid broker token', function () {
    $user = UserModel::factory()->createElement();
    $token = $this->users->setVerificationCodeOnUser($user);

    expect(Password::broker()->tokenExists($user, $token))->toBeTrue();
});

test('sendActivationEmail', function () {
    Notification::fake();

    $user = UserModel::factory()->createElement([
        'active' => false,
        'pending' => true,
    ]);

    $this->users->sendActivationEmail($user);

    Notification::assertSentTo(
        $user,
        ActivationNotification::class,
        fn ($notification, $channels) => in_array(MailChannel::class, $channels)
    );
});

test('mail notifications use the user preferred language as the Laravel locale', function () {
    Mail::fake();

    $originalLocale = app()->getLocale();
    $capturedLocale = null;

    $user = UserModel::factory()->createElement();
    $this->users->saveUserPreferences($user, [
        'language' => 'de',
        'locale' => 'fr-BE',
    ]);

    $user->notifyNow(new class(function (string $locale) use (&$capturedLocale): void {
        $capturedLocale = $locale;
    }) extends LaravelNotification {
        public function __construct(private readonly Closure $captureLocale) {}

        public function via(mixed $notifiable): array
        {
            return [MailChannel::class];
        }

        public function toMail(mixed $notifiable): MailMessage
        {
            ($this->captureLocale)(app()->getLocale());

            return (new MailMessage)->line('Test');
        }
    });

    expect($capturedLocale)
        ->toBe('de')
        ->and(app()->getLocale())->toBe($originalLocale);
});

test('canImpersonate', function () {
    Edition::set(Edition::Pro);

    $admin1 = UserModel::factory()->active()->admin()->createElement();
    $admin2 = UserModel::factory()->active()->admin()->createElement();
    $user1 = UserModel::factory()->active()->createElement();
    $user2 = UserModel::factory()->active()->createElement();

    // Admins can impersonate anyone
    expect($this->users->canImpersonate($admin1, $user1))->toBeTrue();
    expect($this->users->canImpersonate($admin1, $admin2))->toBeTrue();

    // A normal user cannot impersonate an admin
    expect($this->users->canImpersonate($user1, $admin1))->toBeFalse();

    // A normal user cannot impersonate another user without the permission
    expect($this->users->canImpersonate($user1, $user2))->toBeFalse();
    UserPermissions::saveUserPermissions($user1->id, ['viewUsers', 'editUsers', 'impersonateUsers']);
    expect($this->users->canImpersonate($user1, $user2))->toBeTrue();
});
