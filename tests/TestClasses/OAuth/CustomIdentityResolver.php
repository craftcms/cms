<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\OAuth;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class CustomIdentityResolver implements ResolvesOAuthIdentity
{
    public function handle(ProviderDefinition $provider, SocialiteUser $socialiteUser): string
    {
        return sprintf('custom:%s', $socialiteUser->getId());
    }
}
