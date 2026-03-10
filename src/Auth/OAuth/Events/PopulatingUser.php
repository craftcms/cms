<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Events;

use CraftCms\Cms\Auth\OAuth\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\Profile;
use CraftCms\Cms\User\Elements\User;

final class PopulatingUser
{
    public function __construct(
        public User $user,
        public Profile $profile,
        public ProviderDefinition $provider,
    ) {}
}
