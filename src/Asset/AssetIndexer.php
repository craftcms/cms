<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Data\AssetIndexEntry;
use CraftCms\Cms\Asset\Data\IndexingSession;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\AssetNotIndexableException;
use CraftCms\Cms\Asset\Exceptions\MissingAssetException;
use CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Asset\Models\AssetIndexingSession as AssetIndexingSessionModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use DateTime;
use Generator;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Flysystem\StorageAttributes;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

#[Singleton]
class AssetIndexer
{
    public Collection $existingIndexingSessions {
        get => $this->getExistingIndexingSessions();
    }

    public function __construct(
        private readonly Elements $elements,
        private readonly Folders $folders,
        private readonly Volumes $volumes,
    ) {}

    public function getIndexListOnVolume(Volume $volume, string $directory = ''): Generator
    {
        try {
            $fileList = $volume->sourceDisk()->listContents(trim($directory, '/'), true);
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        $fsSubpath = $volume->getSubpath();

        try {
            foreach ($fileList as $listing) {
                if (! $listing instanceof StorageAttributes) {
                    continue;
                }

                $uri = trim($listing->path(), '/');
                if ($uri === '') {
                    continue;
                }

                $dirname = pathinfo($uri, PATHINFO_DIRNAME);
                if ($dirname === '.') {
                    $dirname = '';
                }

                $listing = new FsListing([
                    'dirname' => $dirname,
                    'basename' => pathinfo($uri, PATHINFO_BASENAME),
                    'type' => $listing->isDir() ? 'dir' : 'file',
                    'dateModified' => $listing->lastModified(),
                    'fileSize' => ! $listing->isDir() && method_exists($listing, 'fileSize') ? $listing->fileSize() : null,
                ]);

                $path = $listing->getAdjustedUri($fsSubpath);
                $segments = preg_split('/\\\\|\//', $path);
                $lastSegmentIndex = count($segments) - 1;

                foreach ($segments as $i => $segment) {
                    if (str_starts_with($segment, '_') && ($listing->getIsDir() || $i < $lastSegmentIndex)) {
                        continue 2;
                    }
                }

                yield $listing;
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @return Collection<IndexingSession> */
    public function getExistingIndexingSessions(): Collection
    {
        return AssetIndexingSessionModel::query()
            ->where('isCli', false)
            ->get()
            ->map(fn (AssetIndexingSessionModel $model) => new IndexingSession($model->toArray()));
    }

    public function removeCliIndexingSessions(): int
    {
        return DB::table(Table::ASSETINDEXINGSESSIONS)
            ->where('isCli', true)
            ->delete();
    }

    public function getIndexingSessionById(int $sessionId): ?IndexingSession
    {
        $row = AssetIndexingSessionModel::find($sessionId);

        if (! $row) {
            return null;
        }

        return new IndexingSession($row->toArray());
    }

    /** @param array<int|string> $volumes */
    public function startIndexingSession(
        array $volumes,
        bool $cacheRemoteImages = true,
        bool $listEmptyFolders = false,
    ): IndexingSession {
        $volumeList = [];

        foreach ($volumes as $volumeId) {
            if ($volume = $this->volumes->getVolumeById((int) $volumeId)) {
                $volumeList[] = $volume;
            }
        }

        $session = $this->createIndexingSession($volumeList, $cacheRemoteImages, listEmptyFolders: $listEmptyFolders);
        $total = 0;

        /** @var Volume $volume */
        foreach ($volumeList as $volume) {
            $fileList = $this->getIndexListOnVolume($volume);
            $total += $this->storeIndexList($fileList, $session->id, $volume);
        }

        if ($total === 0) {
            $session->processIfRootEmpty = true;
        }

        $session->totalEntries = $total;
        $this->storeIndexingSession($session);

        return $session;
    }

    public function stopIndexingSession(IndexingSession $session): void
    {
        AssetIndexingSessionModel::find($session->id)?->delete();
    }

    /** @param Volume[] $volumeList */
    public function createIndexingSession(
        array $volumeList,
        bool $cacheRemoteImages = true,
        bool $isCli = false,
        bool $listEmptyFolders = false,
    ): IndexingSession {
        $indexedVolumes = [];

        foreach ($volumeList as $volume) {
            $indexedVolumes[$volume->id] = $volume->name;
        }

        $session = new IndexingSession([
            'totalEntries' => 0,
            'indexedVolumes' => Json::encode($indexedVolumes),
            'processedEntries' => 0,
            'cacheRemoteImages' => $cacheRemoteImages,
            'listEmptyFolders' => $listEmptyFolders,
            'actionRequired' => false,
            'isCli' => $isCli,
            'dateUpdated' => null,
            'processIfRootEmpty' => false,
        ]);

        $this->storeIndexingSession($session);

        return $session;
    }

    /**
     * @param  Generator  $indexList  Index list generated by `AssetIndexer::getIndexListOnVolume()`
     * @return int Number of entries inserted
     */
    public function storeIndexList(Generator $indexList, int $sessionId, Volume $volume): int
    {
        $values = [];
        $fsSubpath = $volume->getSubpath();
        $now = now();

        /** @var FsListing $volumeListing */
        foreach ($indexList as $volumeListing) {
            if ($volumeListing->getIsDir()) {
                $timestamp = null;
            } else {
                $dateModified = $volumeListing->getDateModified();
                $timestamp = $dateModified !== null ? new DateTime('@'.$dateModified) : $now;
            }

            $values[] = [
                'volumeId' => $volume->id,
                'sessionId' => $sessionId,
                'uri' => $volumeListing->getAdjustedUri($fsSubpath),
                'size' => $volumeListing->getFileSize(),
                'timestamp' => $timestamp,
                'isDir' => $volumeListing->getIsDir(),
                'inProgress' => false,
                'completed' => false,
                'dateCreated' => $now,
                'dateUpdated' => $now,
                'uid' => Str::uuid(),
            ];
        }

        return DB::table(Table::ASSETINDEXDATA)->insertOrIgnore($values);
    }

    /**
     * @throws VolumeException if unable to index file because of volume issue
     * @throws LockTimeoutException if unable to acquire a lock
     */
    public function processIndexSession(IndexingSession $indexingSession): IndexingSession
    {
        $lockName = "idx--{$indexingSession->id}--";
        $indexEntry = null;

        Cache::lock($lockName, 3)->block(3, function () use ($indexingSession, &$indexEntry) {
            $indexEntry = $this->getNextIndexEntry($indexingSession);

            if (! $indexEntry && ! $indexingSession->processIfRootEmpty) {
                if ($indexingSession->processedEntries < $indexingSession->totalEntries) {
                    $count = DB::table(Table::ASSETINDEXDATA)
                        ->where([
                            'sessionId' => $indexingSession->id,
                            'completed' => false,
                        ])
                        ->count();

                    if ($count === 0) {
                        Log::info("The assetindexdata table is empty; Can't proceed with indexing.");
                        $indexingSession->forceStop = true;
                    }
                }

                return;
            }

            if ($indexEntry) {
                $this->updateIndexEntry($indexEntry->id, ['inProgress' => true]);
            }
        });

        if ($indexEntry) {
            try {
                if ($indexEntry->isDir) {
                    $recordId = $this->indexFolderByEntry($indexEntry)->id;
                } else {
                    $recordId = $this->indexFileByEntry($indexEntry, $indexingSession->cacheRemoteImages)->id;
                }

                $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'recordId' => $recordId]);
            } catch (AssetDisallowedExtensionException|AssetNotIndexableException) {
                $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'isSkipped' => true]);
            } catch (Throwable $exception) {
                report($exception);
                $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'isSkipped' => true]);
            }

            $session = $this->incrementProcessedEntryCount($indexingSession);
        } else {
            $session = $indexingSession;
        }

        if ($session->processedEntries == $session->totalEntries) {
            $session->actionRequired = true;

            if ($session->processIfRootEmpty) {
                $session->processIfRootEmpty = false;
            }

            $this->storeIndexingSession($session);
        }

        return $indexingSession;
    }

    /** @return string[] */
    public function getSkippedItemsForSession(IndexingSession $session): array
    {
        $skippedItems = DB::table(Table::ASSETINDEXDATA)
            ->select(['volumeId', 'uri'])
            ->where('sessionId', $session->id)
            ->where('isSkipped', true)
            ->get();

        $skipped = [];

        foreach ($skippedItems as $skippedItem) {
            $skipped[] = $this->volumes->getVolumeById((int) $skippedItem->volumeId)->name.'/'.$skippedItem->uri;
        }

        return $skipped;
    }

    /**
     * @return array{
     *     folders: array<int, string>,
     *     files: array<int, string>,
     * }
     *
     * @throws AssetException
     */
    public function getMissingEntriesForSession(IndexingSession $session, string $path = ''): array
    {
        if (! $session->actionRequired) {
            throw new AssetException('A session must be finished before missing entries can be fetched');
        }

        $missing = [
            'folders' => [],
            'files' => [],
        ];

        $cutoff = $session->dateCreated;

        $volumeList = Json::decodeIfJson($session->indexedVolumes);
        if (! $volumeList || ! is_array($volumeList)) {
            return $missing;
        }

        $volumeList = array_keys($volumeList);

        $missingFolders = DB::table(Table::VOLUMEFOLDERS, 'folders')
            ->select([
                'folders.path as path',
                'volumes.name as volumeName',
                'volumes.id as volumeId',
                'folders.id as folderId',
            ])
            ->leftJoin(new Alias(Table::VOLUMES, 'volumes'), 'volumes.id', 'folders.volumeId')
            ->where('folders.dateCreated', '<', $cutoff)
            ->whereIn('folders.volumeId', $volumeList)
            ->whereNotNull('folders.parentId')
            ->when(
                $path !== '',
                fn (Builder $query) => $query->where('folders.path', 'like', "$path%"),
            )
            ->unless(
                $session->listEmptyFolders,
                fn (Builder $query) => $query
                    ->leftJoin(new Alias(Table::ASSETINDEXDATA, 'indexData'), function (JoinClause $join) {
                        $join->whereColumn('folders.id', 'indexData.recordId')
                            ->where('indexData.isDir', true);
                    })
                    ->whereNull('indexData.id'),
            )
            ->get()
            ->map(fn (object $row) => (array) $row);

        $missingFiles = DB::table(Table::ASSETS, 'assets')
            ->select([
                'folders.path as path',
                'volumes.name as volumeName',
                'assets.filename as filename',
                'assets.id as assetId',
            ])
            ->leftJoin(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', 'assets.id')
            ->leftJoin(new Alias(Table::VOLUMEFOLDERS, 'folders'), 'folders.id', 'assets.folderId')
            ->leftJoin(new Alias(Table::VOLUMES, 'volumes'), 'volumes.id', 'assets.volumeId')
            ->leftJoin(new Alias(Table::ASSETINDEXDATA, 'indexData'), function (JoinClause $join) {
                $join->whereColumn('assets.id', 'indexData.recordId')
                    ->where('indexData.isDir', false);
            })
            ->where('assets.dateCreated', '<', $cutoff)
            ->whereIn('assets.volumeId', $volumeList)
            ->whereNull('elements.dateDeleted')
            ->whereNull('indexData.id')
            ->when(
                $path !== '',
                fn (Builder $query) => $query->where('folders.path', 'like', "$path%"),
            )
            ->get()
            ->map(fn (object $row) => (array) $row);

        foreach ($missingFolders as ['folderId' => $folderId, 'path' => $path, 'volumeName' => $volumeName, 'volumeId' => $volumeId]) {
            $hasAssets = DB::table(Table::ASSETS, 'assets')
                ->join(new Alias(Table::VOLUMEFOLDERS, 'folders'), 'folders.id', 'assets.folderId')
                ->leftJoin(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', 'assets.id')
                ->where('assets.volumeId', $volumeId)
                ->whereLike('folders.path', "$path%")
                ->where(function (Builder $query) {
                    $query->whereNull('elements.dateDeleted')
                        ->orWhere('assets.keptFile', 1);
                })
                ->count();

            if ($hasAssets === 0) {
                $missing['folders'][$folderId] = "$volumeName/$path";
            }

            if ($session->listEmptyFolders && $hasAssets > 0) {
                if ($hasAssets === $missingFiles->filter(fn ($file) => str_starts_with((string) $file['path'], (string) $path))->count()) {
                    $missing['folders'][$folderId] = "$volumeName/$path";
                }
            }
        }

        foreach ($missingFiles as ['assetId' => $assetId, 'path' => $path, 'volumeName' => $volumeName, 'filename' => $filename]) {
            $missing['files'][$assetId] = "$volumeName/$path$filename";
        }

        return $missing;
    }

    public function getNextIndexEntry(IndexingSession $session): ?AssetIndexEntry
    {
        $result = DB::table(Table::ASSETINDEXDATA)
            ->select([
                'id', 'volumeId', 'sessionId', 'uri', 'size',
                'timestamp', 'isDir', 'recordId', 'isSkipped',
                'completed', 'inProgress',
            ])
            ->where('sessionId', $session->id)
            ->where('completed', false)
            ->where('inProgress', false)
            ->orderBy('id')
            ->first();

        return $result ? new AssetIndexEntry((array) $result) : null;
    }

    public function updateIndexEntry(int $entryId, array $data): void
    {
        $data = array_intersect_key(
            $data,
            array_flip(['inProgress', 'completed', 'recordId', 'isSkipped', 'processedEntries']),
        );

        DB::table(Table::ASSETINDEXDATA)
            ->where('id', $entryId)
            ->update(array_merge([
                'dateUpdated' => now(),
            ], $data));
    }

    /**
     * @throws AssetDisallowedExtensionException
     * @throws MissingAssetException
     * @throws VolumeException
     */
    public function indexFile(
        Volume $volume,
        string $path,
        int $sessionId,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        $dirname = dirname($path);
        if (in_array($dirname, ['.', '/', '\\'])) {
            $dirname = '';
        }

        $listing = new FsListing([
            'dirname' => $dirname,
            'basename' => pathinfo($path, PATHINFO_BASENAME),
            'type' => 'file',
            'dateModified' => $volume->sourceDisk()->lastModified($path),
            'fileSize' => $volume->sourceDisk()->size($path),
        ]);

        return $this->indexFileByListing($volume, $listing, $sessionId, $cacheImages, $createIfMissing);
    }

    /**
     * @throws AssetDisallowedExtensionException
     * @throws VolumeException
     * @throws MissingAssetException
     * @throws MissingVolumeFolderException
     */
    public function indexFileByListing(
        Volume $volume,
        FsListing $listing,
        int $sessionId,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        $indexEntry = new AssetIndexEntry([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getAdjustedUri($volume->getSubpath()),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
            'inProgress' => true,
        ]);

        $asset = $this->indexFileByEntry($indexEntry, $cacheImages, $createIfMissing);
        $indexEntry->recordId = $asset->id;
        $this->storeIndexEntry($indexEntry);

        return $asset;
    }

    /**
     * @throws AssetNotIndexableException
     * @throws VolumeException
     * @throws MissingVolumeFolderException
     */
    public function indexFolderByListing(
        Volume $volume,
        FsListing $listing,
        int $sessionId,
        bool $createIfMissing = true,
    ): VolumeFolder {
        $indexEntry = new AssetIndexEntry([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getAdjustedUri($volume->getSubpath()),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
            'inProgress' => true,
        ]);

        $folder = $this->indexFolderByEntry($indexEntry, $createIfMissing);
        $indexEntry->recordId = $folder->id;
        $this->storeIndexEntry($indexEntry);

        return $folder;
    }

    /**
     * @throws AssetDisallowedExtensionException
     * @throws AssetNotIndexableException
     * @throws MissingAssetException
     * @throws VolumeException
     */
    public function indexFileByEntry(
        AssetIndexEntry $indexEntry,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        $uriPath = $indexEntry->uri;
        $dirname = dirname((string) $uriPath);

        foreach (preg_split('/\\\\|\//', $dirname) as $part) {
            if ($part[0] === '_') {
                throw new AssetNotIndexableException("File \"{$indexEntry->uri}\" is in a directory that cannot be indexed.");
            }
        }

        $extension = pathinfo((string) $indexEntry->uri, PATHINFO_EXTENSION);
        $filename = basename((string) $indexEntry->uri);

        if (preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $filename)) {
            throw new AssetNotIndexableException("File \"{$indexEntry->uri}\" will not be indexed.");
        }

        if (! in_array(strtolower($extension), Cms::config()->allowedFileExtensions, true)) {
            throw new AssetDisallowedExtensionException("File \"{$indexEntry->uri}\" was not indexed because extension \"{$extension}\" is not allowed.");
        }

        if ($dirname === '.') {
            $parentId = ':empty:';
            $path = '';
        } else {
            $parentId = false;
            $path = "$dirname/";
        }

        $folder = $this->folders->findFolder([
            'volumeId' => $indexEntry->volumeId,
            'path' => $path,
            'parentId' => $parentId,
        ]);

        if (! $folder) {
            /** @var Volume $volume */
            $volume = $this->volumes->getVolumeById($indexEntry->volumeId);
            $folder = $this->folders->ensureFolderByFullPathAndVolume($path, $volume);
        } else {
            $volume = $folder->getVolume();
        }

        $isLocalFs = $volume->sourceDisk() instanceof LocalFilesystemAdapter;

        /** @var Asset|null $asset */
        $asset = Asset::find()
            ->filename(Query::escapeParam($filename))
            ->folderId($folder->id)
            ->one();

        if (! $asset) {
            if (! $createIfMissing) {
                throw new MissingAssetException($indexEntry, $volume, $folder, $filename);
            }

            $asset = new Asset;
            $asset->setVolumeId((int) $volume->id);
            $asset->folderId = $folder->id;
            $asset->folderPath = $folder->path;
            $asset->setFilename($filename);
            $asset->kind = AssetsHelper::getFileKindByExtension($filename);
        }

        $asset->size = $indexEntry->size;
        $timeModified = $indexEntry->timestamp;

        $asset->setScenario(Asset::SCENARIO_INDEX);

        try {
            if ($isLocalFs) {
                $asset->setMimeType($asset->getMimeType());
            }

            if ($asset->kind === Asset::KIND_IMAGE) {
                $dimensions = null;
                $tempPath = null;

                if ($isLocalFs) {
                    $transformSourcePath = $asset->getImageTransformSourcePath();
                    $dimensions = ImageHelper::imageSize($transformSourcePath);
                } else {
                    if (! $cacheImages) {
                        try {
                            $stream = $asset->getStream();

                            if (is_resource($stream)) {
                                $dimensions = ImageHelper::imageSizeByStream($stream);
                                fclose($stream);
                            }
                        } catch (VolumeException $e) {
                            Log::info($e->getMessage());
                        }
                    }

                    if (! is_array($dimensions)) {
                        $tempPath = AssetsHelper::tempFilePath(pathinfo($filename, PATHINFO_EXTENSION));
                        AssetsHelper::downloadFile($volume->sourceDisk(), $indexEntry->uri, $tempPath);
                        $dimensions = ImageHelper::imageSize($tempPath);

                        $asset->setMimeType(File::getMimeType($tempPath));
                    }
                }

                [$w, $h] = $dimensions;
                $asset->setWidth($w);
                $asset->setHeight($h);
                $asset->dateModified = $timeModified;

                $this->elements->saveElement($asset);

                $shouldCache = ! $isLocalFs && $cacheImages && Cms::config()->maxCachedCloudImageSize > 0;

                if ($shouldCache && $tempPath) {
                    $targetPath = $asset->getImageTransformSourcePath();
                    ImageTransformHelper::storeLocalSource($tempPath, $targetPath);
                    File::delete($tempPath);
                }
            } else {
                $asset->dateModified = $timeModified;
                $this->elements->saveElement($asset);
            }
        } catch (Throwable $exception) {
            Log::info($exception->getMessage());
        }

        return $asset;
    }

    /**
     * @throws VolumeException
     * @throws AssetNotIndexableException
     * @throws MissingVolumeFolderException
     */
    public function indexFolderByEntry(AssetIndexEntry $indexEntry, bool $createIfMissing = true): VolumeFolder
    {
        if ($indexEntry->uri !== null) {
            foreach (preg_split('/\\\\|\//', $indexEntry->uri) as $part) {
                if ($part[0] === '_') {
                    throw new AssetNotIndexableException("The directory \"{$indexEntry->uri}\" cannot be indexed.");
                }
            }
        }

        $folder = $this->folders->findFolder([
            'path' => "$indexEntry->uri/",
            'volumeId' => $indexEntry->volumeId,
        ]);

        /** @var Volume $volume */
        $volume = $this->volumes->getVolumeById($indexEntry->volumeId);

        if (! $folder && ! $createIfMissing) {
            throw new MissingVolumeFolderException($indexEntry, $volume, $indexEntry->uri);
        }

        return $this->folders->ensureFolderByFullPathAndVolume($indexEntry->uri ?? '', $volume);
    }

    private function storeIndexingSession(IndexingSession $session): void
    {
        if ($session->id !== null) {
            $model = AssetIndexingSessionModel::find($session->id);
        }

        $model ??= new AssetIndexingSessionModel;

        $model->indexedVolumes = $session->indexedVolumes;
        $model->totalEntries = $session->totalEntries;
        $model->processedEntries = $session->processedEntries;
        $model->cacheRemoteImages = $session->cacheRemoteImages;
        $model->listEmptyFolders = $session->listEmptyFolders;
        $model->actionRequired = $session->actionRequired;
        $model->isCli = $session->isCli;
        $model->processIfRootEmpty = $session->processIfRootEmpty;
        $model->save();

        $session->id = $model->id;
        $session->dateUpdated = $model->dateUpdated;
        $session->dateCreated = $model->dateCreated;
    }

    private function storeIndexEntry(AssetIndexEntry $indexEntry): void
    {
        $now = now();

        $data = [
            'sessionId' => $indexEntry->sessionId,
            'volumeId' => $indexEntry->volumeId,
            'uri' => $indexEntry->uri,
            'size' => $indexEntry->size,
            'timestamp' => $indexEntry->timestamp,
            'isDir' => $indexEntry->isDir,
            'recordId' => $indexEntry->recordId,
            'isSkipped' => $indexEntry->isSkipped,
            'inProgress' => $indexEntry->inProgress,
            'completed' => $indexEntry->completed,
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => Str::uuid(),
        ];

        if ($indexEntry->id) {
            $data['id'] = $indexEntry->id;
        }

        DB::table(Table::ASSETINDEXDATA)->insert($data);
    }

    private function incrementProcessedEntryCount(IndexingSession $session): IndexingSession
    {
        $lockName = "idx--update-{$session->id}--";

        Cache::lock($lockName, 5)->block(5, function () use ($session) {
            $model = AssetIndexingSessionModel::findOrFail($session->id);
            $model->increment('processedEntries');

            $session->processedEntries = (int) $model->processedEntries;
        });

        return $session;
    }
}
