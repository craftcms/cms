<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Events;

use CraftCms\Cms\Auth\OAuth\Provider;
use CraftCms\Cms\Auth\OAuth\ProviderProfile;
use CraftCms\Cms\User\Elements\User;

final class PopulatingUser
{
    public function __construct(
        public User $user,
        public ProviderProfile $profile,
        public Provider $provider,
    ) {}
}
