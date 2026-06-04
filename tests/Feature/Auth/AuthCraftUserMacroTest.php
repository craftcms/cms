<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Auth::shouldUse('craft');
});

it('returns the current Craft user', function () {
    $user = User::first();

    actingAs($user);

    expect(Auth::craftUser())->toBe($user);
});

it('throws when the current auth user is not a Craft user', function () {
    Auth::guard('craft')->setUser(new class implements Authenticatable
    {
        public function getAuthIdentifierName()
        {
            return 'id';
        }

        public function getAuthIdentifier()
        {
            return 123;
        }

        public function getAuthPasswordName()
        {
            return 'password';
        }

        public function getAuthPassword()
        {
            return '';
        }

        public function getRememberToken()
        {
            return null;
        }

        public function setRememberToken($value) {}

        public function getRememberTokenName()
        {
            return 'remember_token';
        }
    });

    expect(fn () => Auth::craftUser())
        ->toThrow(AuthenticationException::class, 'The authenticated user must implement');
});

it('returns null when there is no current auth user', function () {
    Auth::guard('craft')->forgetUser();

    expect(Auth::craftUser())->toBeNull();
});
