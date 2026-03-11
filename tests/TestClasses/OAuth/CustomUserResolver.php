<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\OAuth;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final class CustomUserResolver implements ResolvesOAuthUser
{
    public static ?int $userId = null;

    public static function reset(): void
    {
        self::$userId = null;
    }

    public function handle(ProviderDefinition $provider, SocialiteUser $socialiteUser, string $identity): ?User
    {
        return self::$userId ? User::findOne(self::$userId) : null;
    }
}
