<?php

namespace craft\controllers;

use Craft;
use craft\elements\Asset;
use craft\errors\VolumeException;
use craft\models\VolumeFolder;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;

trait AssetsControllerTrait
{
    /**
     * Requires a volume permission for a given asset.
     *
     * @param string $permissionName The name of the permission to require (sans `:<volume-uid>` suffix)
     * @param Asset $asset The asset whose volume should be checked
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @since 3.4.8
     */
    public function requireVolumePermissionByAsset(string $permissionName, Asset $asset): void
    {
        if (!$asset->getVolumeId()) {
            $userTemporaryFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

            // Skip permission check only if it’s the user’s temporary folder
            if ($userTemporaryFolder->id == $asset->folderId) {
                return;
            }
        }

        $volume = $asset->getVolume();
        $this->requireVolumePermission($permissionName, $volume->uid);
    }

    /**
     * Requires a peer permission for a given asset, unless it was uploaded by the current user.
     *
     * @param string $permissionName The name of the peer permission to require (sans `:<volume-uid>` suffix)
     * @param Asset $asset The asset whose volume should be checked
     * @throws ForbiddenHttpException
     * @since 3.4.8
     */
    public function requirePeerVolumePermissionByAsset(string $permissionName, Asset $asset): void
    {
        if ($asset->getVolumeId()) {
            $userId = Craft::$app->getUser()->getId();
            if ($asset->uploaderId != $userId) {
                $this->requireVolumePermissionByAsset($permissionName, $asset);
            }
        }
    }

    /**
     * Requires a volume permission for a given folder.
     *
     * @param string $permissionName The name of the peer permission to require (sans `:<volume-uid>` suffix)
     * @param VolumeFolder $folder The folder whose volume should be checked
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @since 3.4.8
     */
    public function requireVolumePermissionByFolder(string $permissionName, VolumeFolder $folder): void
    {
        if (!$folder->volumeId) {
            $userTemporaryFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

            // Skip permission check only if it’s the user’s temporary folder
            if ($userTemporaryFolder->id == $folder->id) {
                return;
            }
        }

        $volume = $folder->getVolume();
        $this->requireVolumePermission($permissionName, $volume->uid);
    }

    /**
     * Requires a volume permission by its UID.
     *
     * @param string $permissionName The name of the peer permission to require (sans `:<volume-uid>` suffix)
     * @param string $volumeUid The volume’s UID
     * @throws ForbiddenHttpException
     * @since 3.4.8
     */
    public function requireVolumePermission(string $permissionName, string $volumeUid): void
    {
        $this->requirePermission($permissionName . ':' . $volumeUid);
    }
}
