<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Events\LoginUserRetrieved;
use CraftCms\Cms\Auth\Events\RetrievingLoginUser;
use CraftCms\Cms\Auth\UserProvider;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->provider = app(UserProvider::class);
});

test('retrieveById returns user with password', function () {
    $user = UserModel::factory()->createElement();

    $retrieved = $this->provider->retrieveById($user->id);

    expect($retrieved)->toBeInstanceOf(User::class);
    expect($retrieved->id)->toBe($user->id);
    expect($retrieved->password)->not->toBeNull();
});

test('retrieveById returns null for invalid id', function () {
    $retrieved = $this->provider->retrieveById(9999);

    expect($retrieved)->toBeNull();
});

test('retrieveByToken returns user when token matches', function () {
    $user = UserModel::factory()->createElement([
        'rememberToken' => 'test-token',
    ]);

    $retrieved = $this->provider->retrieveByToken($user->id, 'test-token');

    expect($retrieved)->toBeInstanceOf(User::class);
    expect($retrieved->id)->toBe($user->id);
});

test('retrieveByToken returns null when token mismatch', function () {
    $user = UserModel::factory()->createElement([
        'rememberToken' => 'test-token',
    ]);

    $retrieved = $this->provider->retrieveByToken($user->id, 'wrong-token');

    expect($retrieved)->toBeNull();
});

test('updateRememberToken updates database', function () {
    $user = UserModel::factory()->createElement();

    $this->provider->updateRememberToken($user, 'new-token');

    $dbToken = DB::table(Table::USERS)
        ->where('id', $user->id)
        ->value('rememberToken');

    expect($dbToken)->toBe('new-token');
});

test('retrieveByCredentials works with username', function () {
    $user = UserModel::factory()->createElement([
        'username' => 'testuser',
    ]);

    $retrieved = $this->provider->retrieveByCredentials(['loginName' => 'testuser']);

    expect($retrieved)->toBeInstanceOf(User::class);
    expect($retrieved->id)->toBe($user->id);
});

test('retrieveByCredentials works with email', function () {
    $user = UserModel::factory()->createElement([
        'email' => 'test@example.com',
    ]);

    $retrieved = $this->provider->retrieveByCredentials(['loginName' => 'test@example.com']);

    expect($retrieved)->toBeInstanceOf(User::class);
    expect($retrieved->id)->toBe($user->id);
});

test('retrieveByCredentials respects RetrievingLoginUser event', function () {
    $user = UserModel::factory()->createElement();

    Event::listen(RetrievingLoginUser::class, function (RetrievingLoginUser $event) use ($user) {
        $event->user = $user;
    });

    $retrieved = $this->provider->retrieveByCredentials(['loginName' => 'some-other-user']);

    expect($retrieved->id)->toBe($user->id);
});

test('retrieveByCredentials respects LoginUserRetrieved event', function () {
    $user = UserModel::factory()->createElement();
    $newUser = UserModel::factory()->createElement();

    Event::listen(LoginUserRetrieved::class, function (LoginUserRetrieved $event) use ($newUser) {
        $event->user = $newUser;
    });

    $retrieved = $this->provider->retrieveByCredentials(['loginName' => $user->username]);

    expect($retrieved->id)->toBe($newUser->id);
});

test('validateCredentials delegates to Auth service', function () {
    $user = UserModel::factory()->admin()->createElement();

    $result = $this->provider->validateCredentials($user, ['password' => 'password']);

    expect($result)->toBeTrue();
});

test('rehashPasswordIfRequired updates password when needed', function () {
    $user = UserModel::factory()->createElement([
        'password' => Hash::make('password', ['rounds' => 4]),
    ]);

    // Force a rehash by using a different rounds count or just using $force = true
    $this->provider->rehashPasswordIfRequired($user, ['password' => 'newpassword'], true);

    $newPasswordHash = DB::table(Table::USERS)
        ->where('id', $user->id)
        ->value('password');

    expect(Hash::check('newpassword', $newPasswordHash))->toBeTrue();
    expect($newPasswordHash)->not->toBe($user->password);
});
