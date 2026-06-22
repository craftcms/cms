<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Policies;

use craft\elements\GlobalSet;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Contracts\CraftUser;

class GlobalSetPolicy extends ElementPolicy
{
    public function view(CraftUser $user, GlobalSet $globalSet): bool
    {
        return $user->can("editGlobalSet:$globalSet->uid");
    }

    public function save(CraftUser $user, GlobalSet $globalSet): bool
    {
        return true;
    }

    public function duplicate(CraftUser $user, GlobalSet $globalSet): bool
    {
        return false;
    }

    public function delete(CraftUser $user, GlobalSet $globalSet): bool
    {
        return false;
    }
}
