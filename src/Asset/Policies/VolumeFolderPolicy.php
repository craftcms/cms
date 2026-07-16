<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Policies;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\User\Contracts\CraftUser;

class VolumeFolderPolicy
{
    public function createFolder(CraftUser $user, VolumeFolder $parentFolder): bool
    {
        return $this->hasVolumePermission($user, $parentFolder, 'createFolders');
    }

    public function deleteFolder(CraftUser $user, VolumeFolder $folder): bool
    {
        return $this->hasVolumePermission($user, $folder, 'deletePeerAssets');
    }

    public function renameFolder(CraftUser $user, VolumeFolder $folder): bool
    {
        return $this->hasVolumePermission($user, $folder, 'deleteAssets') &&
            $this->hasVolumePermission($user, $folder, 'createFolders');
    }

    public function viewContents(CraftUser $user, VolumeFolder $folder): bool
    {
        return $this->hasVolumePermission($user, $folder, 'viewAssets');
    }

    public function moveFolder(CraftUser $user, VolumeFolder $folder, VolumeFolder $destinationFolder): bool
    {
        return $this->moveFolderFrom($user, $folder) &&
            $this->moveIntoFolder($user, $destinationFolder);
    }

    public function moveFolderFrom(CraftUser $user, VolumeFolder $folder): bool
    {
        return $this->hasVolumePermission($user, $folder, 'savePeerAssets') &&
            $this->hasVolumePermission($user, $folder, 'deletePeerAssets');
    }

    public function moveIntoFolder(CraftUser $user, VolumeFolder $folder): bool
    {
        return $this->hasVolumePermission($user, $folder, 'saveAssets') &&
            $this->hasVolumePermission($user, $folder, 'deleteAssets') &&
            $this->hasVolumePermission($user, $folder, 'savePeerAssets') &&
            $this->hasVolumePermission($user, $folder, 'deletePeerAssets');
    }

    public function uploadAsset(CraftUser $user, VolumeFolder $folder): bool
    {
        return $this->hasVolumePermission($user, $folder, 'saveAssets');
    }

    private function hasVolumePermission(CraftUser $user, VolumeFolder $folder, string $permissionName): bool
    {
        if (! $folder->volumeId) {
            $userTemporaryFolder = Assets::getUserTemporaryUploadFolder();

            if ($userTemporaryFolder->id == $folder->id) {
                return true;
            }
        }

        $volume = $folder->getVolume();

        return $user->can("$permissionName:$volume->uid");
    }
}
