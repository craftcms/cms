<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Http\Requests\CreateAssetFolderRequest;
use CraftCms\Cms\Http\Requests\DeleteAssetFolderRequest;
use CraftCms\Cms\Http\Requests\MoveAssetFolderRequest;
use CraftCms\Cms\Http\Requests\RenameAssetFolderRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Str;
use Exception;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class FolderController
{
    use RespondsWithFlash;

    public function __construct(
        private Folders $folders,
    ) {}

    public function create(CreateAssetFolderRequest $request): Response
    {
        $parentFolder = $request->parentFolder();

        try {
            Gate::authorize('createFolder', $parentFolder);

            $folderModel = new VolumeFolder;
            $folderModel->name = $request->folderName();
            $folderModel->parentId = $request->parentFolderId();
            $folderModel->volumeId = $parentFolder->volumeId;
            $folderModel->path = $parentFolder->path.$folderModel->name.'/';

            $this->folders->createFolder($folderModel);

            return $this->asSuccess(data: [
                'folderName' => $folderModel->name,
                'folderUid' => $folderModel->uid,
                'folderId' => $folderModel->id,
            ]);
        } catch (Exception $exception) {
            return $this->asFailure($exception->getMessage());
        }
    }

    public function delete(DeleteAssetFolderRequest $request): Response
    {
        $folder = $request->folder();

        Gate::authorize('deleteFolder', $folder);
        $this->folders->deleteFoldersByIds($request->folderId());

        return $this->asSuccess();
    }

    public function rename(RenameAssetFolderRequest $request): Response
    {
        $folder = $request->folder();

        Gate::authorize('renameFolder', $folder);

        $newName = $this->folders->renameFolderById($request->folderId(), $request->newName());

        return $this->asSuccess(data: ['newName' => $newName]);
    }

    public function move(MoveAssetFolderRequest $request): Response
    {
        $force = $request->force();
        $folderToMove = $request->folderToMove();
        $destinationFolder = $request->destinationFolder();

        Gate::authorize('moveFolder', [$folderToMove, $destinationFolder]);

        $targetVolume = $destinationFolder->getVolume();

        $existingFolder = $this->folders->findFolder([
            'parentId' => $request->newParentFolderId(),
            'name' => $folderToMove->name,
        ]);

        if (! $existingFolder) {
            $existingFolder = $targetVolume->sourceDisk()->directoryExists(Str::ltrim(Str::finish($destinationFolder->path, '/').$folderToMove->name, '/'));
        }

        // If there's a conflict and `force`/`merge` flags weren't passed in, then stop
        if ($existingFolder && ! $force && ! $request->shouldMergeFolders()) {
            return $this->asJsonSuccess(data: [
                'conflict' => t('Folder “{folder}” already exists at target location', ['folder' => $folderToMove->name]),
                'folderId' => $request->folderBeingMovedId(),
                'parentId' => $request->newParentFolderId(),
            ]);
        }

        $sourceTree = $this->folders->getAllDescendantFolders($folderToMove);

        if (! $existingFolder) {
            $folderIdChanges = AssetsHelper::mirrorFolderStructure($folderToMove, $destinationFolder);

            $allSourceFolderIds = array_keys($sourceTree);
            $allSourceFolderIds[] = $request->folderBeingMovedId();
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
            $allSourceFolderIds[] = $request->folderBeingMovedId();
            $foundAssets = Asset::find()
                ->folderId($allSourceFolderIds)
                ->all();
            $fileTransferList = AssetsHelper::fileTransferList($foundAssets, $folderIdChanges);
        }

        $newFolderId = $folderIdChanges[$request->folderBeingMovedId()] ?? null;
        $newFolder = $this->folders->getFolderById($newFolderId);

        return $this->asSuccess(data: [
            'transferList' => $fileTransferList,
            'newFolderUid' => $newFolder->uid,
            'newFolderId' => $newFolderId,
        ]);
    }
}
