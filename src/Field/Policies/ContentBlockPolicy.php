<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Policies;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\User\Contracts\CraftUser;

class ContentBlockPolicy extends ElementPolicy
{
    public function view(CraftUser $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('view', $owner);
    }

    public function save(CraftUser $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function delete(CraftUser $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function duplicate(CraftUser $user, ContentBlock $contentBlock): bool
    {
        if (! $owner = $this->getOwner($contentBlock)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function copy(CraftUser $user, ContentBlock $contentBlock): bool
    {
        return $user->can('duplicate', $contentBlock);
    }

    public function createDrafts(CraftUser $user, ContentBlock $contentBlock): bool
    {
        return true;
    }

    private function getOwner(ContentBlock $contentBlock): ?ElementInterface
    {
        return $contentBlock->getOwner()?->getCanonical(true);
    }
}
