<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\UserLocked;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->users = app(Users::class);

    Edition::set(Edition::Team);
});

test('user activation', function () {
    $user = UserModel::factory()->pending()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    expect($user->getStatus())->toBe(User::STATUS_PENDING);

    $this->users->activateUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);
});

test('user activation email as username with an unverified email', function () {
    $user = UserModel::factory()->pending()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    // Set useEmailAsUsername to true and add an unverified email.
    Cms::config()->useEmailAsUsername = true;

    Craft::$app->elements->saveElement($user);

    $this->users->activateUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);
    expect($user->username)->toBe($user->email);
});

test('user activation email as username with no unverified email', function () {
    $user = UserModel::factory()->pending()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    // Run the same test as above but without an unverified email.
    Cms::config()->useEmailAsUsername = true;

    // Remove the unverifiedEmail property from the user record - meaning no username will be set.
    $user->unverifiedEmail = null;

    $this->users->activateUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);
    expect($user->username)->not()->toBe($user->email);
});

test('unlock', function () {
    $user = UserModel::factory()->locked()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    expect($user->locked)->toBeTrue();

    $this->users->unlockUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->locked)->toBeFalse();
    expect($user->lockoutDate)->toBeNull();
    expect($user->invalidLoginCount)->toBeNull();
});

test('suspend', function () {
    $user = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    expect($user->suspended)->toBeFalse();
    expect($user->getStatus())->toBe(User::STATUS_ACTIVE);

    $this->users->suspendUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->suspended)->toBeTrue();
    expect($user->getStatus())->toBe(User::STATUS_SUSPENDED);
});

test('unsuspend', function () {
    $user = UserModel::factory()->suspended()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    expect($user->suspended)->toBeTrue();
    expect($user->getStatus())->toBe(User::STATUS_SUSPENDED);

    $this->users->unsuspendUser($user);

    $user = $this->users->getUserById($user->id);

    expect($user->suspended)->toBeFalse();
    expect($user->getStatus())->not()->toBe(User::STATUS_SUSPENDED);
});

test('set verification code', function () {
    Date::setTestNow(now('UTC'));

    $user = UserModel::factory()->pending()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    $verificationCode = $this->users->setVerificationCodeOnUser($user);

    $model = UserModel::findOrFail($user->id);

    expect(strlen((string) $verificationCode))->toBe(32);
    expect($model->verificationCode)->not()->toBeNull();
    expect($model->verificationCodeIssuedDate)->toEqualCanonicalizing(now('UTC')->startOfSecond());
});

test('assignUserToGroups', function () {
    $user = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

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
    $user = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    $group = UserGroup::factory()->create();

    ProjectConfig::set('users.defaultGroup', $group->uid);

    $this->users->assignUserToDefaultGroup($user);

    $user = $this->users->getUserById($user->id);

    expect($user->getGroups())->toHaveCount(1);
    expect($user->getGroups()[0]->name)->toBe($group->name);
});

test('handleInvalidLogin', function () {
    Date::setTestNow(now('UTC'));

    $user = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    $this->users->handleInvalidLogin($user);

    $user = UserModel::findOrFail($user->id);

    expect($user->invalidLoginCount)->toBe(1);
    expect($user->invalidLoginWindowStart)->toEqualCanonicalizing(now('UTC')->startOfSecond());
    expect($user->lastInvalidLoginDate)->toEqualCanonicalizing(now('UTC')->startOfSecond());
    expect($user->lastLoginAttemptIp)->toBeNull();
});

test('handleInvalidLogin stores ip', function () {
    Cms::config()->storeUserIps = true;

    $user = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

    $this->users->handleInvalidLogin($user);

    $user = UserModel::findOrFail($user->id);

    expect($user->lastLoginAttemptIp)->not()->toBeNull();
});

test('handleInvalidLogin without limit', function () {
    Cms::config()->maxInvalidLogins = false;
    Cms::config()->storeUserIps = true;

    $user = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($user);

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

    $user = UserModel::factory()->active()->make([
        'id' => null,
        'invalidLoginWindowStart' => null,
    ])->asElement();
    Craft::$app->elements->saveElement($user);

    Cms::config()->maxInvalidLogins = 1;

    $this->users->handleInvalidLogin($user);

    $user = UserModel::findOrFail($user->id);

    expect($user->invalidLoginCount)->toBe(1);
    expect($user->locked)->toBeFalse();
    expect($user->invalidLoginWindowStart)->toEqualCanonicalizing(now('UTC')->startOfSecond());
    expect($user->lockoutDate)->toBeNull();
});

test('handleInvalidLogin inside window', function () {
    Event::fake();
    Date::setTestNow(now('UTC'));

    $activeUser = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($activeUser);

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
    expect($user->lockoutDate)->toEqualCanonicalizing(now('UTC')->startOfSecond());
});

test('handleValidLogin', function () {
    $activeUser = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($activeUser);

    $user = UserModel::findOrFail($activeUser->id);

    expect($user->lastLoginDate)->toBeNull();

    $this->users->handleValidLogin($activeUser);

    expect($user->fresh()->lastLoginDate)->not()->toBeNull();
    expect($user->fresh()->lastLoginAttemptIp)->toBeNull();
});

test('handleValidLogin with ip collection', function () {
    Cms::config()->storeUserIps = true;

    $activeUser = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($activeUser);

    $user = UserModel::findOrFail($activeUser->id);

    expect($user->fresh()->lastLoginAttemptIp)->toBeNull();

    $this->users->handleValidLogin($activeUser);

    expect($user->fresh()->lastLoginAttemptIp)->not()->toBeNull();
});

test('handleValidLogin clears values', function () {
    $activeUser = UserModel::factory()->active()->make(['id' => null])->asElement();
    Craft::$app->elements->saveElement($activeUser);

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

test('isVerificationCodeValidForUser', function () {})->todo('Need to be able to fake verification codes');

test('sendActivationEmail', function () {})->todo('Add test after Mails are ported.');
