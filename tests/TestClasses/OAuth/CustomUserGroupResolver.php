<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\OAuth;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUserGroups;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class CustomUserGroupResolver implements ResolvesOAuthUserGroups
{
    public static array $groups = [];

    public static function reset(): void
    {
        self::$groups = [];
    }

    public function handle(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        User $user,
        string $identity,
    ): array {
        return self::$groups;
    }
}
