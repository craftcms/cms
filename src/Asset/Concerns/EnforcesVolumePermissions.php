<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Concerns;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Support\Facades\Assets;
use Illuminate\Support\Facades\Auth;

trait EnforcesVolumePermissions
{
    use EnforcesPermissions;

    /**
     * Requires a volume permission for a given asset.
     *
     * Skips permission check for assets in the user's temporary upload folder.
     */
    protected function requireVolumePermissionByAsset(string $permissionName, Asset $asset): void
    {
        if (! $asset->getVolumeId()) {
            $userTemporaryFolder = Assets::getUserTemporaryUploadFolder();

            // Skip permission check only if it's the user's temporary folder
            if ($userTemporaryFolder->id == $asset->folderId) {
                return;
            }
        }

        $volume = $asset->getVolume();
        $this->requireVolumePermission($permissionName, $volume->uid);
    }

    /**
     * Requires a peer permission for a given asset, unless it was uploaded by the current user.
     */
    protected function requirePeerVolumePermissionByAsset(string $permissionName, Asset $asset): void
    {
        if (! $asset->getVolumeId()) {
            return;
        }

        if ($asset->uploaderId != Auth::id()) {
            $this->requireVolumePermissionByAsset($permissionName, $asset);
        }
    }

    /**
     * Requires a volume permission for a given folder.
     *
     * Skips permission check for the user's temporary upload folder.
     */
    protected function requireVolumePermissionByFolder(string $permissionName, VolumeFolder $folder): void
    {
        if (! $folder->volumeId) {
            $userTemporaryFolder = Assets::getUserTemporaryUploadFolder();

            // Skip permission check only if it's the user's temporary folder
            if ($userTemporaryFolder->id == $folder->id) {
                return;
            }
        }

        $volume = $folder->getVolume();
        $this->requireVolumePermission($permissionName, $volume->uid);
    }

    /**
     * Requires a volume permission by its UID.
     */
    protected function requireVolumePermission(string $permissionName, string $volumeUid): void
    {
        $this->requirePermission($permissionName.':'.$volumeUid);
    }
}
