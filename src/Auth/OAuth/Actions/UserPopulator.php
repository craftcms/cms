<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Actions;

use CraftCms\Cms\Auth\OAuth\Contracts\PopulatesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class UserPopulator implements PopulatesOAuthUser
{
    public function __construct(
        protected readonly GeneralConfig $generalConfig,
    ) {}

    public function handle(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        User $user,
        string $identity,
        bool $isNew,
    ): User {
        if ($email = $socialiteUser->getEmail()) {
            $user->email = $email;
        }

        if ($name = $socialiteUser->getName()) {
            $user->fullName = $name;
        }

        if (! $user->username) {
            $user->username = $this->resolveUsername($provider, $socialiteUser, $identity, $user);
        }

        return $user;
    }

    private function resolveUsername(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        string $identity,
        User $user,
    ): string {
        if ($this->generalConfig->useEmailAsUsername && $user->email) {
            return $user->email;
        }

        return $socialiteUser->getNickname()
            ?: $socialiteUser->getEmail()
            ?: sprintf('%s_%s', $provider->handle, $identity);
    }
}
