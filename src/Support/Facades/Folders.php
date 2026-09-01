<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder|null getFolderById(int $folderId)
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder|null getFolderByUid(string $folderUid)
 * @method static \Illuminate\Support\Collection findFolders(mixed $criteria = [])
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder|null findFolder(mixed $criteria = [])
 * @method static array getAllDescendantFolders(\CraftCms\Cms\Asset\Data\VolumeFolder $parentFolder, string $orderBy = 'path', bool $withParent = true, bool $asTree = false)
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder|null getRootFolderByVolumeId(int $volumeId)
 * @method static int getTotalFolders(mixed $criteria)
 * @method static bool foldersExist(mixed $criteria = null)
 * @method static void createFolder(\CraftCms\Cms\Asset\Data\VolumeFolder $folder)
 * @method static string renameFolderById(int $folderId, string $newName)
 * @method static void deleteFoldersByIds(int|array $folderIds, bool $deleteDir = true)
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder ensureFolderByFullPathAndVolume(string $fullPath, \CraftCms\Cms\Asset\Data\Volume $volume, bool $justRecord = true)
 * @method static void storeFolderModel(\CraftCms\Cms\Asset\Data\VolumeFolder $folder)
 * @method static \Illuminate\Database\Query\Builder createFolderQuery()
 * @method static void reset()
 *
 * @see \CraftCms\Cms\Asset\Folders
 */
class Folders extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Asset\Folders::class;
    }
}
