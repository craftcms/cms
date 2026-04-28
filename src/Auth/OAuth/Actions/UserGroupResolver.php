<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Actions;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUserGroups;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class UserGroupResolver implements ResolvesOAuthUserGroups
{
    public function handle(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        User $user,
        string $identity,
    ): array {
        return $provider->groupIds;
    }
}
