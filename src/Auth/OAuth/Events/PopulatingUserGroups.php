<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Events;

use CraftCms\Cms\Auth\OAuth\Provider;
use CraftCms\Cms\Auth\OAuth\ProviderProfile;
use CraftCms\Cms\User\Elements\User;

final class PopulatingUserGroups
{
    public function __construct(
        public User $user,
        /** @var int[] */
        public array $groupIds,
        public ProviderProfile $profile,
        public Provider $provider,
    ) {}
}
