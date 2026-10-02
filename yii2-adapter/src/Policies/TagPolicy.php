<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Policies;

use craft\elements\Tag;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Contracts\CraftUser;

class TagPolicy extends ElementPolicy
{
    public function view(CraftUser $user, Tag $tag): bool
    {
        return true;
    }

    public function save(CraftUser $user, Tag $tag): bool
    {
        return true;
    }

    public function duplicate(CraftUser $user, Tag $tag): bool
    {
        return true;
    }

    public function delete(CraftUser $user, Tag $tag): bool
    {
        return true;
    }
}
