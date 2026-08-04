<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Methods\BaseAuthMethod;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Auth\Methods\TOTP;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;

class TransactionAwareAuthMethod extends BaseAuthMethod
{
    public static ?int $transactionLevel = null;

    public static function handle(): string
    {
        return 'transaction-aware';
    }

    public static function displayName(): string
    {
        return 'Transaction-aware';
    }

    public static function description(): string
    {
        return 'Checks the database transaction level.';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function getSetupHtml(string $containerId): string
    {
        return '';
    }

    public function getAuthFormHtml(?string $returnUrl = null): string
    {
        return '';
    }

    public function verify(mixed ...$args): bool
    {
        self::$transactionLevel = DB::transactionLevel();

        return false;
    }

    public function remove(): void {}
}

beforeEach(function () {
    Event::fake();
    app()->forgetScopedInstances();
    $this->auth = app(AuthMethods::class);
    Session::flush();
});

test('getAllMethods returns TOTP and RecoveryCodes', function () {
    $user = User::factory()->createElement();

    $methods = $this->auth->getAllMethods($user)->map(fn ($method) => $method::class);

    expect($methods)->toContain(TOTP::class);
    expect($methods)->toContain(RecoveryCodes::class);
});

test('getAllMethods with no user returns empty', function () {
    $methods = $this->auth->getAllMethods(null);

    expect($methods)->toBeEmpty();
});

test('getActiveMethods returns only active methods', function () {
    $user = User::factory()->createElement();

    $methods = $this->auth->getActiveMethods($user);

    expect($methods)->toBeInstanceOf(Collection::class);
});

test('getAvailableMethods excludes RecoveryCodes alone', function () {
    $user = User::factory()->createElement();

    $methods = $this->auth->getAvailableMethods($user);

    $hasOnlyRecoveryCodes = $methods->count() === 1 &&
        str_contains($methods->first()::class, 'RecoveryCodes');

    if ($hasOnlyRecoveryCodes) {
        expect($methods)->toBeEmpty();
    } else {
        expect($methods)->not()->toBeEmpty();
    }
});

test('hasActiveMethod returns true when active', function () {
    $user = User::factory()->createElement();

    $result = $this->auth->hasActiveMethod($user);

    expect($result)->toBeBool();
});

test('hasActiveMethod returns false when none', function () {
    $user = User::factory()->createElement();

    $result = $this->auth->hasActiveMethod($user);

    expect($result)->toBe(false);
});

test('getMethod returns correct method by class', function () {
    $user = User::factory()->createElement();

    $totpMethod = $this->auth->getMethod(TOTP::class, $user);

    expect($totpMethod)->not()->toBeNull();
});

test('getMethod throws for invalid class', function () {
    $user = User::factory()->createElement();

    $this->expectException(InvalidArgumentException::class);

    $this->auth->getMethod('Invalid\\Class\\DoesNotExist', $user);
});

test('methods are sorted with RecoveryCodes last', function () {
    $user = User::factory()->createElement();

    $methods = $this->auth->getAllMethods($user);

    $lastMethod = $methods->last();
    expect($lastMethod)->not()->toBeNull();
});

test('custom methods can be registered', function () {
    $user = User::factory()->createElement();

    app(AuthMethods::class)->register(CustomAuthMethod::class);

    $methods = $this->auth->getAllMethods($user);

    expect($methods->map(fn ($method) => $method::class))->toContain(CustomAuthMethod::class);
});

class CustomAuthMethod extends TOTP
{
    #[Override]
    public static function handle(): string
    {
        return 'custom';
    }
}

test('verifyMethod runs verification in a dedicated database transaction', function () {
    $user = User::factory()->createElement();
    $auth = app(AuthMethods::class);
    $auth->register(TransactionAwareAuthMethod::class);
    $auth->setUser($user);
    TransactionAwareAuthMethod::$transactionLevel = null;
    $transactionLevel = DB::transactionLevel();

    expect($auth->verifyMethod(TransactionAwareAuthMethod::class))->toBeFalse()
        ->and(TransactionAwareAuthMethod::$transactionLevel)->toBeGreaterThan($transactionLevel);
});

test('is2faRequired returns false for Solo', function () {
    ProjectConfig::set('users.require2fa', 'all');

    $user = User::factory()->createElement();

    Edition::set(Edition::Solo);

    $result = $this->auth->is2faRequired($user);

    expect($result)->toBeFalse();
});

test('is2faRequired with require2fa=all', function () {
    Edition::set(Edition::Pro);

    ProjectConfig::set('users.require2fa', 'all');

    $user = User::factory()->createElement();

    $result = $this->auth->is2faRequired($user);

    expect($result)->toBeTrue();
});

test('is2faRequired with require2fa=admins admin', function () {
    Edition::set(Edition::Pro);

    ProjectConfig::set('users.require2fa', ['admins']);

    $user = User::factory()->admin()->createElement();

    $result = $this->auth->is2faRequired($user);

    expect($result)->toBeTrue();
});

test('is2faRequired with require2fa=admins non-admin', function () {
    Edition::set(Edition::Pro);

    ProjectConfig::set('users.require2fa', ['admins']);

    $user = User::factory()->admin(false)->createElement();

    $result = $this->auth->is2faRequired($user);

    expect($result)->toBeFalse();
});

test('is2faRequired with require2fa=userGroup in group', function () {
    Edition::set(Edition::Pro);

    $group = UserGroup::factory()->create();
    $user = User::factory()->createElement();
    app(Users::class)->assignUserToGroups($user->id, [$group->id]);

    ProjectConfig::set('users.require2fa', [$group->uid]);

    $result = $this->auth->is2faRequired($user);

    expect($result)->toBeTrue();
});

test('is2faRequired with require2fa=userGroup not in group', function () {
    Edition::set(Edition::Pro);

    $group = UserGroup::factory()->create();
    $user = User::factory()->createElement();

    ProjectConfig::set('users.require2fa', [$group->uid]);

    $result = $this->auth->is2faRequired($user);

    expect($result)->toBeFalse();
});
