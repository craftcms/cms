<?php

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travel;

class TestConfirmsPasswords
{
    use ConfirmsPasswords;

    public function __call(string $name, array $arguments)
    {
        return $this->$name(...$arguments);
    }
}

beforeEach(function () {
    actingAs(User::find()->first());
});

test('it can mark a password as confirmed', function () {
    Date::setTestNow(now());

    expect(Session::get('auth.password_confirmed_at'))->toBeNull();
    expect(new TestConfirmsPasswords()->isPasswordConfirmed())->toBeFalse();

    new TestConfirmsPasswords()->confirmPassword();

    expect(Session::get('auth.password_confirmed_at'))->toBe(now()->unix());
    expect(new TestConfirmsPasswords()->isPasswordConfirmed())->toBeTrue();
});

test('it can require password to be confirmed', function () {
    $this->expectException(HttpException::class);

    new TestConfirmsPasswords()->requireConfirmedPassword();
});

test('timeout returns seconds until confirmation is required', function () {
    Date::setTestNow(now());
    $timeout = config('auth.password_timeout');

    expect(new TestConfirmsPasswords()->confirmedPasswordTimeout())->toBe(0);

    new TestConfirmsPasswords()->confirmPassword();

    expect(new TestConfirmsPasswords()->confirmedPasswordTimeout())->toBe($timeout);

    travel(5)->seconds();

    expect(new TestConfirmsPasswords()->confirmedPasswordTimeout())->toBe($timeout - 5);
});

test('password confirmation can be disabled', function () {
    expect(new TestConfirmsPasswords()->confirmedPasswordTimeout())->toBe(0);
    expect(new TestConfirmsPasswords()->isPasswordConfirmed())->toBeFalse();

    config()->set('auth.password_timeout', -1);

    expect(new TestConfirmsPasswords()->confirmedPasswordTimeout())->toBeFalse();
    expect(new TestConfirmsPasswords()->isPasswordConfirmed())->toBeTrue();
});
