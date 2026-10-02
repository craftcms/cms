<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\User\Elements\User;

/**
 * Provides authorization functionality for elements.
 *
 * This trait handles methods related to checking user permissions
 * for viewing, saving, duplicating, copying, and deleting elements.
 *
 * @internal
 */
trait HasAuthorization
{
    public function canView(User $user): bool
    {
        return $user->can('view', $this);
    }

    public function canSave(User $user): bool
    {
        return $user->can('save', $this);
    }

    public function canDuplicate(User $user): bool
    {
        return $user->can('duplicate', $this);
    }

    public function canCopy(User $user): bool
    {
        return $user->can('copy', $this);
    }

    public function canDelete(User $user): bool
    {
        return $user->can('delete', $this);
    }

    public function canDeleteForSite(User $user): bool
    {
        return $user->can('deleteForSite', $this);
    }
}
