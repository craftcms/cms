<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Policies;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Support\Facades\Gate;

class AssetPolicy extends ElementPolicy
{
    public function view(CraftUser $user, Asset $asset): bool
    {
        if ($asset->isFolder) {
            return false;
        }

        $volume = $asset->getVolume();
        $userId = $user->getCraftUserId();

        if ($asset->uploaderId !== $userId) {
            return $user->can("viewPeerAssets:$volume->uid");
        }

        if ($volume->isTemporary()) {
            return true;
        }

        return $user->can("viewAssets:$volume->uid");
    }

    public function save(CraftUser $user, Asset $asset): bool
    {
        $volume = $asset->getVolume();
        $userId = $user->getCraftUserId();

        if ($asset->uploaderId !== $userId) {
            return $user->can("savePeerAssets:$volume->uid");
        }

        return $user->can("saveAssets:$volume->uid");
    }

    public function delete(CraftUser $user, Asset $asset): bool
    {
        if ($asset->isFolder) {
            return false;
        }

        $volume = $asset->getVolume();

        if ($volume->isTemporary()) {
            return true;
        }

        if ($asset->uploaderId !== $user->getCraftUserId()) {
            return $user->can("deletePeerAssets:$volume->uid");
        }

        return $user->can("deleteAssets:$volume->uid");
    }

    public function copy(CraftUser $user, Asset $asset): bool
    {
        return $this->view($user, $asset);
    }

    public function deleteFile(CraftUser $user, Asset $asset): bool
    {
        return $this->hasVolumePermission($user, $asset, 'deleteAssets') &&
            $this->hasPeerVolumePermission($user, $asset, 'deletePeerAssets');
    }

    public function moveFile(CraftUser $user, Asset $asset, VolumeFolder $folder): bool
    {
        return Gate::forUser($user)->check('uploadAsset', $folder) &&
            $this->hasVolumePermission($user, $asset, 'deleteAssets') &&
            $this->hasPeerVolumePermission($user, $asset, 'savePeerAssets') &&
            $this->hasPeerVolumePermission($user, $asset, 'deletePeerAssets');
    }

    public function viewFile(CraftUser $user, Asset $asset): bool
    {
        return $this->hasVolumePermission($user, $asset, 'viewAssets') &&
            $this->hasPeerVolumePermission($user, $asset, 'viewPeerAssets');
    }

    public function replaceFile(CraftUser $user, Asset $asset): bool
    {
        return $this->hasVolumePermission($user, $asset, 'replaceFiles') &&
            $this->hasPeerVolumePermission($user, $asset, 'replacePeerFiles');
    }

    public function editImage(CraftUser $user, Asset $asset): bool
    {
        return $this->hasVolumePermission($user, $asset, 'editImages') &&
            $this->hasPeerVolumePermission($user, $asset, 'editPeerImages');
    }

    private function hasVolumePermission(CraftUser $user, Asset $asset, string $permissionName): bool
    {
        if (! $asset->getVolumeId()) {
            $userTemporaryFolder = Assets::getUserTemporaryUploadFolder();

            if ($userTemporaryFolder->id == $asset->folderId) {
                return true;
            }
        }

        $volume = $asset->getVolume();

        return $user->can("$permissionName:$volume->uid");
    }

    private function hasPeerVolumePermission(CraftUser $user, Asset $asset, string $permissionName): bool
    {
        if (! $asset->getVolumeId()) {
            return true;
        }

        if ($asset->uploaderId != $user->getCraftUserId()) {
            return $this->hasVolumePermission($user, $asset, $permissionName);
        }

        return true;
    }
}
