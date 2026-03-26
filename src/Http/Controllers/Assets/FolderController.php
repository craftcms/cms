<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Concerns\EnforcesVolumePermissions;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use yii\base\UserException;

use function CraftCms\Cms\t;

readonly class FolderController
{
    use EnforcesVolumePermissions;
    use RespondsWithFlash;

    public function __construct(
        private Folders $folders,
    ) {}

    public function create(Request $request): Response
    {
        $request->validate([
            'parentId' => ['required', 'integer'],
            'folderName' => ['required', 'string'],
        ]);

        $parentId = $request->integer('parentId');
        $folderName = AssetsHelper::prepareAssetName($request->input('folderName'), false);

        $parentFolder = $this->folders->findFolder(['id' => $parentId]);

        abort_if(! $parentFolder, 400, 'The parent folder cannot be found');

        try {
            $this->requireVolumePermissionByFolder('createFolders', $parentFolder);

            $folderModel = new VolumeFolder;
            $folderModel->name = $folderName;
            $folderModel->parentId = $parentId;
            $folderModel->volumeId = $parentFolder->volumeId;
            $folderModel->path = $parentFolder->path.$folderName.'/';

            $this->folders->createFolder($folderModel);

            return $this->asSuccess(data: [
                'folderName' => $folderModel->name,
                'folderUid' => $folderModel->uid,
                'folderId' => $folderModel->id,
            ]);
        } catch (UserException $exception) {
            return $this->asFailure($exception->getMessage());
        }
    }

    public function delete(Request $request): Response
    {
        $request->validate([
            'folderId' => ['required', 'integer'],
        ]);

        $folderId = $request->integer('folderId');
        $folder = $this->folders->getFolderById($folderId);

        abort_if(! $folder, 400, 'The folder cannot be found');

        $this->requireVolumePermissionByFolder('deleteAssets', $folder);
        $this->folders->deleteFoldersByIds($folderId);

        return $this->asSuccess();
    }

    public function rename(Request $request): Response
    {
        $request->validate([
            'folderId' => ['required', 'integer'],
            'newName' => ['required', 'string'],
        ]);

        $folderId = $request->integer('folderId');
        $newName = $request->input('newName');
        $folder = $this->folders->getFolderById($folderId);

        abort_if(! $folder, 400, 'The folder cannot be found');

        $this->requireVolumePermissionByFolder('deleteAssets', $folder);
        $this->requireVolumePermissionByFolder('createFolders', $folder);

        $newName = $this->folders->renameFolderById($folderId, $newName);

        return $this->asSuccess(data: ['newName' => $newName]);
    }

    public function move(Request $request): Response
    {
        $request->validate([
            'folderId' => ['required', 'integer'],
            'parentId' => ['required', 'integer'],
        ]);

        $folderBeingMovedId = $request->integer('folderId');
        $newParentFolderId = $request->integer('parentId');
        $force = $request->boolean('force');
        $merge = ! $force && $request->boolean('merge');

        $folderToMove = $this->folders->getFolderById($folderBeingMovedId);
        $destinationFolder = $this->folders->getFolderById($newParentFolderId);

        abort_if(! $folderToMove, 400, 'The folder you are trying to move does not exist');
        abort_if(! $destinationFolder, 400, 'The destination folder does not exist');

        $this->requireVolumePermissionByFolder('deleteAssets', $folderToMove);
        $this->requireVolumePermissionByFolder('createFolders', $destinationFolder);
        $this->requireVolumePermissionByFolder('saveAssets', $destinationFolder);

        $targetVolume = $destinationFolder->getVolume();

        $existingFolder = $this->folders->findFolder([
            'parentId' => $newParentFolderId,
            'name' => $folderToMove->name,
        ]);

        if (! $existingFolder) {
            $existingFolder = $targetVolume->sourceDisk()->directoryExists(Str::ltrim(Str::finish($destinationFolder->path, '/').$folderToMove->name, '/'));
        }

        // If there's a conflict and `force`/`merge` flags weren't passed in, then stop
        if ($existingFolder && ! $force && ! $merge) {
            return $this->asJsonSuccess(data: [
                'conflict' => t('Folder "{folder}" already exists at target location', ['folder' => $folderToMove->name]),
                'folderId' => $folderBeingMovedId,
                'parentId' => $newParentFolderId,
            ]);
        }

        $sourceTree = $this->folders->getAllDescendantFolders($folderToMove);

        if (! $existingFolder) {
            $folderIdChanges = AssetsHelper::mirrorFolderStructure($folderToMove, $destinationFolder);

            $allSourceFolderIds = array_keys($sourceTree);
            $allSourceFolderIds[] = $folderBeingMovedId;
            $foundAssets = Asset::find()
                ->folderId($allSourceFolderIds)
                ->all();
            $fileTransferList = AssetsHelper::fileTransferList($foundAssets, $folderIdChanges);
        } else {
            $targetTreeMap = [];

            if ($existingFolder instanceof VolumeFolder) {
                if ($force) {
                    try {
                        $this->folders->deleteFoldersByIds($existingFolder->id);
                    } catch (VolumeException $exception) {
                        report($exception);

                        return $this->asFailure(t('Directories cannot be deleted while moving assets.'));
                    }
                } else {
                    $targetTree = $this->folders->getAllDescendantFolders($existingFolder);
                    $targetPrefixLength = strlen($destinationFolder->path);

                    foreach ($targetTree as $existingFolder) {
                        $targetTreeMap[substr((string) $existingFolder->path, $targetPrefixLength)] = $existingFolder->id;
                    }
                }
            } elseif ($force) {
                $targetVolume->sourceDisk()->deleteDirectory(trim(rtrim($destinationFolder->path, '/').'/'.$folderToMove->name, '/'));
            }

            $folderIdChanges = AssetsHelper::mirrorFolderStructure($folderToMove, $destinationFolder, $targetTreeMap);

            $allSourceFolderIds = array_keys($sourceTree);
            $allSourceFolderIds[] = $folderBeingMovedId;
            $foundAssets = Asset::find()
                ->folderId($allSourceFolderIds)
                ->all();
            $fileTransferList = AssetsHelper::fileTransferList($foundAssets, $folderIdChanges);
        }

        $newFolderId = $folderIdChanges[$folderBeingMovedId] ?? null;
        $newFolder = $this->folders->getFolderById($newFolderId);

        return $this->asSuccess(data: [
            'transferList' => $fileTransferList,
            'newFolderUid' => $newFolder->uid,
            'newFolderId' => $newFolderId,
        ]);
    }
}
