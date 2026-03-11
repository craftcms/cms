<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Events;

use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final class ResolvingOAuthUserLink
{
    public function __construct(
        public ProviderDefinition $provider,
        public SocialiteUser $socialiteUser,
        public string $identity,
        public ?User $user = null,
    ) {}
}
