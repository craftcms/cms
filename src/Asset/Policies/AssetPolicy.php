<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Policies;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Elements\User;

class AssetPolicy extends ElementPolicy
{
    public function view(User $user, Asset $asset): bool
    {
        if ($asset->isFolder) {
            return false;
        }

        $volume = $asset->getVolume();

        if ($asset->uploaderId !== $user->id) {
            return $user->can("viewPeerAssets:$volume->uid");
        }

        if (AssetsHelper::isTempUploadFs($volume->getFs())) {
            return true;
        }

        return $user->can("viewAssets:$volume->uid");
    }

    public function save(User $user, Asset $asset): bool
    {
        $volume = $asset->getVolume();

        if ($asset->uploaderId !== $user->id) {
            return $user->can("savePeerAssets:$volume->uid");
        }

        return $user->can("saveAssets:$volume->uid");
    }

    public function delete(User $user, Asset $asset): bool
    {
        if ($asset->isFolder) {
            return false;
        }

        $volume = $asset->getVolume();

        if (AssetsHelper::isTempUploadFs($volume->getFs())) {
            return true;
        }

        if ($asset->uploaderId !== $user->id) {
            return $user->can("deletePeerAssets:$volume->uid");
        }

        return $user->can("deleteAssets:$volume->uid");
    }

    public function copy(User $user, Asset $asset): bool
    {
        return $this->view($user, $asset);
    }
}
