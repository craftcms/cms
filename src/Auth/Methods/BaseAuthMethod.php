<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Methods;

use CraftCms\Cms\User\Elements\User;

abstract class BaseAuthMethod implements AuthMethodInterface
{
    /**
     * @var User The current user
     */
    protected User $user;

    /**
     * {@inheritdoc}
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionMenuItems(): array
    {
        return [];
    }

    public static function isSelectable(): bool
    {
        return true;
    }
}
