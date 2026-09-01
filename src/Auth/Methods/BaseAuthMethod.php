<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Methods;

use CraftCms\Cms\Shared\Concerns\LegacyEventConstants;
use CraftCms\Cms\User\Elements\User;

abstract class BaseAuthMethod implements AuthMethodInterface
{
    use LegacyEventConstants;

    /**
     * @var User The current user
     */
    protected User $user;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /** @return list<array<string, mixed>> */
    public function getActionMenuItems(): array
    {
        return [];
    }

    public static function isSelectable(): bool
    {
        return true;
    }
}
