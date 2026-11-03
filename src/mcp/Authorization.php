<?php

declare(strict_types=1);

namespace craft\mcp;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\models\VolumeFolder;

/**
 * Applies Craft's native element and volume authorization rules to MCP operations.
 */
class Authorization
{
    public static function allows(User $user, string $ability, mixed $subject): bool
    {
        if ($subject instanceof ElementInterface) {
            $elements = Craft::$app->getElements();

            if ($ability === 'changeAuthor' && $subject instanceof Entry) {
                $authorIds = $subject->getAuthorIds();

                return !$authorIds || in_array($user->id, $authorIds, true)
                    || $user->can("changeAuthorForPeerEntries:{$subject->getSection()->uid}");
            }

            $method = 'can' . ucfirst($ability);

            if (method_exists($elements, $method)) {
                return $elements->$method($subject, $user);
            }

            return false;
        }

        if ($ability === 'moveFile' && is_array($subject)) {
            [$asset, $folder] = $subject;

            return self::assetPermission($user, $asset, 'deleteAssets', 'deletePeerAssets')
                && self::assetPermission($user, $asset, 'saveAssets', 'savePeerAssets')
                && self::allows($user, 'uploadAsset', $folder);
        }

        if ($subject instanceof VolumeFolder) {
            if (!$subject->volumeId) {
                return false;
            }

            $uid = $subject->getVolume()->uid;

            if ($ability === 'renameFolder') {
                return $user->can("createFolders:$uid") && $user->can("deleteAssets:$uid");
            }

            if ($ability === 'deleteFolder') {
                return $user->can("deletePeerAssets:$uid");
            }

            $permission = match ($ability) {
                'viewContents' => 'viewAssets',
                'uploadAsset' => 'saveAssets',
                'createFolder', 'renameFolder' => 'createFolders',
                'deleteFolder' => 'deleteAssets',
                default => null,
            };

            return $permission !== null && $user->can("{$permission}:{$subject->getVolume()->uid}");
        }

        return false;
    }

    public static function assetPermission(User $user, Asset $asset, string $permission, string $peerPermission): bool
    {
        $volume = $asset->getVolume();

        return $user->can("{$permission}:{$volume->uid}")
            && ($asset->uploaderId === $user->id || $user->can("{$peerPermission}:{$volume->uid}"));
    }
}
