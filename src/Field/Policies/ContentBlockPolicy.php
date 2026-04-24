<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Policies;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\User\Elements\User;

class ContentBlockPolicy extends ElementPolicy
{
    public function view(User $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('view', $owner);
    }

    public function save(User $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function delete(User $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function duplicate(User $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function copy(User $user, ContentBlock $contentBlock): bool
    {
        return $user->can('duplicate', $contentBlock);
    }

    public function createDrafts(User $user, ContentBlock $contentBlock): bool
    {
        return true;
    }

    private function getOwner(ContentBlock $contentBlock): ?ElementInterface
    {
        return $contentBlock->getOwner()?->getCanonical(true);
    }
}
