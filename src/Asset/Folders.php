<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Craft;
use CraftCms\Cms\Asset\Data\FolderCriteria;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\AssetOperationException;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Exceptions\FsObjectExistsException;
use CraftCms\Cms\Filesystem\Exceptions\FsObjectNotFoundException;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

use function CraftCms\Cms\t;

#[Singleton]
class Folders
{
    /** @var array<int, VolumeFolder|null> */
    private array $foldersById = [];

    /** @var array<string, VolumeFolder|null> */
    private array $foldersByUid = [];

    /** @var array<int, VolumeFolder|null> */
    private array $rootFolders = [];

    public function getFolderById(int $folderId): ?VolumeFolder
    {
        if (! array_key_exists($folderId, $this->foldersById)) {
            $result = $this->createFolderQuery()
                ->where('id', $folderId)
                ->first();

            $this->foldersById[$folderId] = $result ? new VolumeFolder((array) $result) : null;
        }

        return $this->foldersById[$folderId];
    }

    public function getFolderByUid(string $folderUid): ?VolumeFolder
    {
        if (! array_key_exists($folderUid, $this->foldersByUid)) {
            $result = $this->createFolderQuery()
                ->where('uid', $folderUid)
                ->first();

            $this->foldersByUid[$folderUid] = $result ? new VolumeFolder((array) $result) : null;
        }

        return $this->foldersByUid[$folderUid];
    }

    /**
     * @return Collection<int, VolumeFolder>
     */
    public function findFolders(mixed $criteria = []): Collection
    {
        if (! $criteria instanceof FolderCriteria) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = $this->createFolderQuery();

        $this->applyFolderConditions($query, $criteria);

        if ($criteria->order) {
            if (is_string($criteria->order)) {
                $parts = explode(' ', $criteria->order, 2);
                $query->orderBy($parts[0], $parts[1] ?? 'asc');
            } else {
                foreach ($criteria->order as $column => $direction) {
                    if (is_int($column)) {
                        $query->orderByRaw((string) $direction);
                    } else {
                        $query->orderBy($column, $direction === SORT_DESC ? 'desc' : 'asc');
                    }
                }
            }
        }

        if ($criteria->offset) {
            $query->offset($criteria->offset);
        }

        if ($criteria->limit) {
            $query->limit($criteria->limit);
        }

        return $query->get()
            ->map(fn ($result) => new VolumeFolder((array) $result))
            ->each(fn (VolumeFolder $folder) => $this->foldersById[$folder->id] = $folder)
            ->keyBy('id');
    }

    public function findFolder(mixed $criteria = []): ?VolumeFolder
    {
        if (! $criteria instanceof FolderCriteria) {
            $criteria = new FolderCriteria($criteria);
        }

        $criteria->limit = 1;

        return $this->findFolders($criteria)->first();
    }

    /**
     * @return array<int, VolumeFolder>
     */
    public function getAllDescendantFolders(
        VolumeFolder $parentFolder,
        string $orderBy = 'path',
        bool $withParent = true,
        bool $asTree = false,
    ): array {
        $query = $this->createFolderQuery()
            ->where('volumeId', $parentFolder->volumeId)
            ->whereNotNull('parentId');

        if ($parentFolder->path !== null) {
            $escapedPath = str_replace('_', '\\_', $parentFolder->path);
            $query->where('path', 'like', $escapedPath.'%');
        }

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        if (! $withParent) {
            $query->where('id', '!=', $parentFolder->id);
        }

        $results = $query->get();
        $descendantFolders = [];

        foreach ($results as $result) {
            $folder = new VolumeFolder((array) $result);
            $this->foldersById[$folder->id] = $folder;
            $descendantFolders[$folder->id] = $folder;
        }

        if ($asTree) {
            return $this->buildFolderTree($descendantFolders);
        }

        return $descendantFolders;
    }

    public function getRootFolderByVolumeId(int $volumeId): ?VolumeFolder
    {
        if (! array_key_exists($volumeId, $this->rootFolders)) {
            $volume = Volumes::getVolumeById($volumeId);

            if (! $volume) {
                return $this->rootFolders[$volumeId] = null;
            }

            $folder = $this->findFolder([
                'volumeId' => $volumeId,
                'parentId' => ':empty:',
            ]);

            if (! $folder) {
                $folder = new VolumeFolder;
                $folder->volumeId = $volume->id;
                $folder->parentId = null;
                $folder->name = $volume->name;
                $folder->path = '';
                $this->storeFolderModel($folder);
            }

            $this->rootFolders[$volumeId] = $folder;
        }

        return $this->rootFolders[$volumeId];
    }

    public function getTotalFolders(mixed $criteria): int
    {
        if (! $criteria instanceof FolderCriteria) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = DB::table(Table::VOLUMEFOLDERS);

        $this->applyFolderConditions($query, $criteria);

        return $query->count('id');
    }

    public function foldersExist(mixed $criteria = null): bool
    {
        if (! ($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = DB::table(Table::VOLUMEFOLDERS);

        $this->applyFolderConditions($query, $criteria);

        return $query->exists();
    }

    /**
     * @throws FsObjectExistsException if a folder already exists with such a name
     * @throws FilesystemException if unable to create the directory on volume
     * @throws AssetException if invalid folder provided
     */
    public function createFolder(VolumeFolder $folder): void
    {
        $parent = $folder->getParent();

        if (! $parent) {
            throw new AssetException("Folder {$folder->id} doesn’t have a parent.");
        }

        $existingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $folder->name,
        ]);

        if ($existingFolder && (! $folder->id || $folder->id !== $existingFolder->id)) {
            throw new FsObjectExistsException(t(
                'A folder with the name "{folderName}" already exists in the volume.',
                ['folderName' => $folder->name]
            ));
        }

        $path = rtrim((string) $folder->path, '/');

        if (! $parent->getVolume()->sourceDisk()->makeDirectory($path)) {
            throw new FilesystemException("Unable to create directory at path: $path");
        }

        $this->storeFolderModel($folder);
    }

    /**
     * @return string The new folder name after cleaning it.
     *
     * @throws AssetOperationException
     * @throws FsObjectExistsException
     * @throws FsObjectNotFoundException
     */
    public function renameFolderById(int $folderId, string $newName): string
    {
        $newName = AssetsHelper::prepareAssetName($newName, false);
        $folder = $this->getFolderById($folderId);

        if (! $folder) {
            throw new AssetOperationException(t('No folder exists with the ID "{id}"', [
                'id' => $folderId,
            ]));
        }

        if (! $folder->parentId) {
            throw new AssetOperationException(t("It\u{2019}s not possible to rename the top folder of a Volume."));
        }

        $conflictingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $newName,
        ]);

        if ($conflictingFolder) {
            throw new FsObjectExistsException(t('A folder with the name "{folderName}" already exists in the folder.', [
                'folderName' => $newName,
            ]));
        }

        $parentFolderPath = dirname((string) $folder->path);
        $newFolderPath = (($parentFolderPath && $parentFolderPath !== '.') ? $parentFolderPath.'/' : '').$newName.'/';

        $volume = $folder->getVolume();

        $this->renameDirectoryOnDisk($volume, rtrim((string) $folder->path, '/'), rtrim($newFolderPath, '/'));
        $descendantFolders = $this->getAllDescendantFolders($folder);

        foreach ($descendantFolders as $descendantFolder) {
            $descendantFolder->path = preg_replace('#^'.$folder->path.'#', $newFolderPath, (string) $descendantFolder->path);
            $this->storeFolderModel($descendantFolder);
        }

        $folder->name = $newName;
        $folder->path = $newFolderPath;
        $this->storeFolderModel($folder);

        return $newName;
    }

    /**
     * @param  bool  $deleteDir  Whether the volume directory should be deleted along the record.
     */
    public function deleteFoldersByIds(int|array $folderIds, bool $deleteDir = true): void
    {
        $allFolderIds = [];

        foreach ((array) $folderIds as $folderId) {
            $folder = $this->getFolderById((int) $folderId);

            if (! $folder) {
                continue;
            }

            $allFolderIds[] = $folder->id;
            $descendants = $this->getAllDescendantFolders($folder, withParent: false);
            array_push($allFolderIds, ...array_map(fn (VolumeFolder $folder) => $folder->id, $descendants));

            if ($folder->path && $deleteDir) {
                $volume = $folder->getVolume();
                try {
                    $volume->sourceDisk()->deleteDirectory(trim($folder->path, '/'));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }

        $assetQuery = Asset::find()->folderId($allFolderIds);
        $elementService = Craft::$app->getElements();

        $assetQuery->each(function (Asset $asset) use ($deleteDir, $elementService) {
            $asset->keepFileOnDelete = ! $deleteDir;
            $elementService->deleteElement($asset, true);
        }, 100);

        VolumeFolderModel::whereIn('id', $allFolderIds)->delete();
    }

    /**
     * @throws FilesystemException
     */
    public function ensureFolderByFullPathAndVolume(string $fullPath, Volume $volume, bool $justRecord = true): VolumeFolder
    {
        $parentFolder = $this->getRootFolderByVolumeId($volume->id);
        $folderModel = $parentFolder;
        $parentId = $parentFolder->id;

        $fullPath = trim($fullPath, '/\\');

        if ($fullPath !== '') {
            $parts = preg_split('/\\\\|\//', $fullPath);
            $path = '';

            while (($part = array_shift($parts)) !== null) {
                $path .= $part.'/';

                $parameters = new FolderCriteria([
                    'path' => $path,
                    'volumeId' => $volume->id,
                ]);

                if (($folderModel = $this->findFolder($parameters)) === null) {
                    $folderModel = new VolumeFolder;
                    $folderModel->volumeId = $volume->id;
                    $folderModel->parentId = $parentId;
                    $folderModel->name = $part;
                    $folderModel->path = $path;
                    $this->storeFolderModel($folderModel);
                }

                if (! $justRecord && ! $volume->sourceDisk()->makeDirectory($path)) {
                    throw new FilesystemException("Unable to create directory at path: $path");
                }

                $parentId = $folderModel->id;
            }
        }

        return $folderModel;
    }

    public function storeFolderModel(VolumeFolder $folder): void
    {
        if (! $folder->id) {
            $model = new VolumeFolderModel;
        } else {
            $model = VolumeFolderModel::findOrFail($folder->id);
        }

        $model->parentId = $folder->parentId;
        $model->volumeId = $folder->volumeId;
        $model->name = $folder->name;
        $model->path = $folder->path;
        $model->save();

        $folder->id = $model->id;
        $folder->uid = $model->uid;
    }

    public function createFolderQuery(): Builder
    {
        return DB::table(Table::VOLUMEFOLDERS)
            ->select(['id', 'parentId', 'volumeId', 'name', 'path', 'uid']);
    }

    public function reset(): void
    {
        $this->foldersById = [];
        $this->foldersByUid = [];
        $this->rootFolders = [];
    }

    private function applyFolderConditions(Builder $query, FolderCriteria $criteria): void
    {
        if ($criteria->id) {
            $this->applyParam($query, 'id', $criteria->id);
        }

        if ($criteria->volumeId) {
            $this->applyParam($query, 'volumeId', $criteria->volumeId);
        }

        if ($criteria->parentId) {
            $this->applyParam($query, 'parentId', $criteria->parentId);
        }

        if ($criteria->name) {
            $this->applyParam($query, 'name', $criteria->name);
        }

        if ($criteria->uid) {
            $this->applyParam($query, 'uid', $criteria->uid);
        }

        if ($criteria->path !== null) {
            $this->applyParam($query, 'path', $criteria->path);
        }
    }

    private function applyParam(Builder $query, string $column, mixed $value): void
    {
        if ($value === ':empty:') {
            $query->whereNull($column);

            return;
        }

        if ($value === 'not :empty:') {
            $query->whereNotNull($column);

            return;
        }

        if (is_array($value)) {
            $query->whereIn($column, $value);

            return;
        }

        $query->where($column, $value);
    }

    /**
     * @param  VolumeFolder[]  $folders
     * @return VolumeFolder[]
     */
    private function buildFolderTree(array $folders): array
    {
        $tree = [];
        /** @var VolumeFolder[] $referenceStore */
        $referenceStore = [];

        foreach ($folders as $folder) {
            $folder->setChildren([]);

            if ($folder->parentId && isset($referenceStore[$folder->parentId])) {
                $referenceStore[$folder->parentId]->addChild($folder);
            } else {
                $tree[] = $folder;
            }

            $referenceStore[$folder->id] = $folder;
        }

        return $tree;
    }

    /**
     * @throws FilesystemException
     * @throws FsObjectNotFoundException
     */
    private function renameDirectoryOnDisk(Volume $volume, string $sourcePath, string $targetPath): void
    {
        $sourcePath = trim($sourcePath, '/');
        $targetPath = trim($targetPath, '/');

        $disk = $volume->sourceDisk();

        if ($sourcePath === '' || ! $disk->directoryExists($sourcePath)) {
            throw new FsObjectNotFoundException("No folder exists at path: $sourcePath");
        }

        if ($targetPath === '') {
            throw new FilesystemException('New directory name cannot be empty.');
        }

        if ($targetPath === $sourcePath) {
            return;
        }

        if (! $disk->makeDirectory($targetPath)) {
            throw new FilesystemException("Unable to create directory at path: $targetPath");
        }

        $directories = $disk->allDirectories($sourcePath);
        usort($directories, fn (string $a, string $b) => substr_count($a, '/') <=> substr_count($b, '/'));

        foreach ($directories as $directory) {
            $targetDirectory = preg_replace(
                '/^'.preg_quote($sourcePath, '/').'(?=\/|$)/',
                $targetPath,
                trim((string) $directory, '/'),
                1,
            ) ?? trim((string) $directory, '/');

            if (! $disk->makeDirectory($targetDirectory)) {
                throw new FilesystemException("Unable to create directory at path: $targetDirectory");
            }
        }

        foreach ($disk->allFiles($sourcePath) as $file) {
            $targetFile = preg_replace(
                '/^'.preg_quote($sourcePath, '/').'(?=\/|$)/',
                $targetPath,
                trim((string) $file, '/'),
                1,
            ) ?? trim((string) $file, '/');

            if (! $disk->move($file, $targetFile)) {
                throw new FilesystemException("Unable to move $file to $targetFile");
            }
        }

        $disk->deleteDirectory($sourcePath);
    }
}
