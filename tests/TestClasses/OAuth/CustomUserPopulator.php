<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\OAuth;

use CraftCms\Cms\Auth\OAuth\Contracts\PopulatesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class CustomUserPopulator implements PopulatesOAuthUser
{
    public function handle(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        User $user,
        string $identity,
        bool $isNew,
    ): User {
        $user->email = $socialiteUser->getEmail() ?: sprintf('%s@example.com', $identity);
        $user->username = 'custom-oauth-user';
        $user->fullName = 'Custom Populated User';

        return $user;
    }
}
