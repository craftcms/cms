<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Actions;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

class IdentityResolver implements ResolvesOAuthIdentity
{
    public function handle(ProviderDefinition $provider, SocialiteUser $socialiteUser): string
    {
        $identity = $socialiteUser->getId();

        if (! is_scalar($identity) || trim((string) $identity) === '') {
            throw new RuntimeException(sprintf('OAuth provider [%s] did not return an identity.', $provider->handle));
        }

        return trim((string) $identity);
    }
}
