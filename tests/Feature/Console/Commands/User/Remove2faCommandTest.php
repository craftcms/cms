<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Methods\BaseAuthMethod;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    TestRemove2faSmsMethod::$removeCount = 0;
    TestRemove2faEmailMethod::$removeCount = 0;
});

test('removes passed method during non-interactive runs', function () {
    $user = User::factory()->createElement(['username' => 'tester']);
    $sms = new TestRemove2faSmsMethod;
    $email = new TestRemove2faEmailMethod;

    $auth = mockRemove2faAuthMethods(
        activeMethods: collect([$sms, $email]),
        activeMethodsAfterRemoval: fn () => collect([$email]),
    );

    app()->instance(AuthMethods::class, $auth);

    $this->artisan('craft:users:remove-2fa', [
        'user' => $user->id,
        '--method' => TestRemove2faSmsMethod::displayName(),
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(TestRemove2faSmsMethod::$removeCount)->toBe(1)
        ->and(TestRemove2faEmailMethod::$removeCount)->toBe(0);
});

test('removes all methods when all is passed', function () {
    $user = User::factory()->createElement(['username' => 'tester']);
    $sms = new TestRemove2faSmsMethod;
    $email = new TestRemove2faEmailMethod;

    $auth = mockRemove2faAuthMethods(
        activeMethods: collect([$sms, $email]),
        activeMethodsAfterRemoval: fn () => TestRemove2faEmailMethod::$removeCount === 0 ? collect([$email]) : collect(),
    );
    $auth->shouldReceive('getMethod')
        ->once()
        ->with(RecoveryCodes::class, Mockery::type(UserElement::class))
        ->andReturn(tap(new RecoveryCodes, fn (RecoveryCodes $method) => $method->setUser($user)));

    app()->instance(AuthMethods::class, $auth);

    $this->artisan('craft:users:remove-2fa', [
        'user' => $user->id,
        '--method' => 'all',
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(TestRemove2faSmsMethod::$removeCount)->toBe(1)
        ->and(TestRemove2faEmailMethod::$removeCount)->toBe(1);
});

test('does not remove an inactive passed method', function () {
    $user = User::factory()->createElement(['username' => 'tester']);

    $auth = mockRemove2faAuthMethods(
        activeMethods: collect([new TestRemove2faSmsMethod]),
        activeMethodsAfterRemoval: fn () => collect(),
        expectAfterRemovalCheck: false,
    );

    app()->instance(AuthMethods::class, $auth);

    $this->artisan('craft:users:remove-2fa', [
        'user' => $user->id,
        '--method' => TestRemove2faEmailMethod::displayName(),
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('User “tester” doesn’t have the “Email” two-step verification method.')
        ->assertSuccessful();

    expect(TestRemove2faSmsMethod::$removeCount)->toBe(0)
        ->and(TestRemove2faEmailMethod::$removeCount)->toBe(0);
});

function mockRemove2faAuthMethods(
    Collection $activeMethods,
    Closure $activeMethodsAfterRemoval,
    bool $expectAfterRemovalCheck = true,
): AuthMethods {
    $auth = Mockery::mock(AuthMethods::class);
    $auth->shouldReceive('getActiveMethods')
        ->once()
        ->with(Mockery::type(UserElement::class))
        ->andReturn($activeMethods);

    if ($expectAfterRemovalCheck) {
        $auth->shouldReceive('getActiveMethods')
            ->zeroOrMoreTimes()
            ->with(Mockery::type(UserElement::class))
            ->andReturnUsing($activeMethodsAfterRemoval);
    }

    return $auth;
}

class TestRemove2faSmsMethod extends BaseAuthMethod
{
    public static int $removeCount = 0;

    public static function handle(): string
    {
        return 'sms';
    }

    public static function displayName(): string
    {
        return 'SMS';
    }

    public static function description(): string
    {
        return 'SMS';
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
        return true;
    }

    public function remove(): void
    {
        self::$removeCount++;
    }
}

class TestRemove2faEmailMethod extends BaseAuthMethod
{
    public static int $removeCount = 0;

    public static function handle(): string
    {
        return 'email';
    }

    public static function displayName(): string
    {
        return 'Email';
    }

    public static function description(): string
    {
        return 'Email';
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
        return true;
    }

    public function remove(): void
    {
        self::$removeCount++;
    }
}
