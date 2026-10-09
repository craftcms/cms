<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Policies;

use craft\elements\Category;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Contracts\CraftUser;

class CategoryPolicy extends ElementPolicy
{
    public function view(CraftUser $user, Category $category): bool
    {
        $group = $category->getGroup();

        if ($category->getIsDraft() && $category->getIsDerivative()) {
            return $category->draftCreatorId === $user->getCraftUserId()
                || $user->can("viewPeerCategoryDrafts:$group->uid");
        }

        return $user->can("viewCategories:$group->uid");
    }

    public function save(CraftUser $user, Category $category): bool
    {
        $group = $category->getGroup();

        if ($category->getIsDraft()) {
            return $category->draftCreatorId === $user->getCraftUserId()
                || $user->can("savePeerCategoryDrafts:$group->uid");
        }

        return $user->can("saveCategories:$group->uid");
    }

    public function duplicate(CraftUser $user, Category $category): bool
    {
        $group = $category->getGroup();

        return $user->can("saveCategories:$group->uid");
    }

    public function delete(CraftUser $user, Category $category): bool
    {
        $group = $category->getGroup();

        if ($category->getIsDraft() && $category->getIsDerivative()) {
            return $category->draftCreatorId === $user->getCraftUserId()
                || $user->can("deletePeerCategoryDrafts:$group->uid");
        }

        return $user->can("deleteCategories:$group->uid");
    }

    public function createDrafts(CraftUser $user, Category $category): bool
    {
        // Everyone with view permissions can create drafts
        return true;
    }
}
