<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Contracts;

use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

interface ResolvesOAuthUserGroups
{
    /**
     * @return array<int|string>
     */
    public function handle(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        User $user,
        string $identity,
    ): array;
}
