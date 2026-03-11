<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\OAuth;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

final class FakeOAuthProvider extends AbstractProvider
{
    public static ?SocialiteUserContract $fakeUser = null;

    public static ?Throwable $exception = null;

    public static array $withParameters = [];

    public static bool $usedStateless = false;

    public static function reset(): void
    {
        self::$fakeUser = null;
        self::$exception = null;
        self::$withParameters = [];
        self::$usedStateless = false;
    }

    public static function fakeUser(array $attributes = []): SocialiteUser
    {
        $attributes = array_merge([
            'id' => 'oauth-user',
            'nickname' => 'oauth-user',
            'name' => 'OAuth User',
            'email' => 'oauth@example.com',
            'avatar' => null,
        ], $attributes);

        return tap(new SocialiteUser, function (SocialiteUser $user) use ($attributes) {
            $user
                ->setRaw($attributes)
                ->map($attributes);
        });
    }

    #[\Override]
    public function redirect(): RedirectResponse
    {
        return new RedirectResponse('https://provider.test/oauth/authorize');
    }

    #[\Override]
    public function user(): SocialiteUserContract
    {
        if (self::$exception) {
            throw self::$exception;
        }

        return self::$fakeUser ?? self::fakeUser();
    }

    #[\Override]
    public function with(array $parameters)
    {
        self::$withParameters = $parameters;

        return parent::with($parameters);
    }

    #[\Override]
    public function stateless()
    {
        self::$usedStateless = true;

        return parent::stateless();
    }

    protected function getAuthUrl($state): string
    {
        return 'https://provider.test/oauth/authorize';
    }

    protected function getTokenUrl(): string
    {
        return 'https://provider.test/oauth/token';
    }

    protected function getUserByToken($token): array
    {
        return [];
    }

    protected function mapUserToObject(array $user): SocialiteUser
    {
        return self::fakeUser($user);
    }
}
