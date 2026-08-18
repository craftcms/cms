<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIndexStatus;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\AssetNotIndexableException;
use CraftCms\Cms\Asset\Exceptions\MissingAssetException;
use CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Asset\Models\AssetIndexData;
use CraftCms\Cms\Asset\Models\AssetIndexingSession;
use CraftCms\Cms\Asset\Validation\AssetRules;
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
use Generator;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Flysystem\StorageAttributes;
use RuntimeException;
use Throwable;
use Tpetry\QueryExpressions\Function\String\Concat;
use Tpetry\QueryExpressions\Language\Alias;
use Tpetry\QueryExpressions\Value\Value;

#[Singleton]
class AssetIndexer
{
    /** @var Collection<int, AssetIndexingSession> */
    public Collection $existingIndexingSessions {
        get => $this->getExistingIndexingSessions();
    }

    public function __construct(
        private readonly Elements $elements,
        private readonly Folders $folders,
        private readonly Volumes $volumes,
    ) {}

    /** @return Generator<int, FsListing> */
    public function getIndexListOnVolume(Volume $volume, string $directory = ''): Generator
    {
        try {
            $fileList = $volume->sourceDisk()->listContents(trim($directory, '/'), true);
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

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

                $path = $listing->getUri();
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

    /** @return Collection<int, AssetIndexingSession> */
    public function getExistingIndexingSessions(): Collection
    {
        return AssetIndexingSession::query()
            ->where('isCli', false)
            ->get();
    }

    public function removeCliIndexingSessions(): int
    {
        return AssetIndexingSession::query()
            ->where('isCli', true)
            ->delete();
    }

    public function getIndexingSessionById(int $sessionId): ?AssetIndexingSession
    {
        return AssetIndexingSession::find($sessionId);
    }

    /** @param array<int|string> $volumes */
    public function startIndexingSession(
        array $volumes,
        bool $cacheRemoteImages = true,
        bool $listEmptyFolders = false,
    ): AssetIndexingSession {
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
        $session->save();

        return $session;
    }

    public function stopIndexingSession(AssetIndexingSession $session): void
    {
        $session->delete();
    }

    /** @param Volume[] $volumeList */
    public function createIndexingSession(
        array $volumeList,
        bool $cacheRemoteImages = true,
        bool $isCli = false,
        bool $listEmptyFolders = false,
    ): AssetIndexingSession {
        $indexedVolumes = [];

        foreach ($volumeList as $volume) {
            $indexedVolumes[$volume->id] = $volume->name;
        }

        $session = new AssetIndexingSession([
            'totalEntries' => 0,
            'indexedVolumes' => Json::encode($indexedVolumes),
            'processedEntries' => 0,
            'cacheRemoteImages' => $cacheRemoteImages,
            'listEmptyFolders' => $listEmptyFolders,
            'actionRequired' => false,
            'isCli' => $isCli,
            'processIfRootEmpty' => false,
        ]);

        $session->save();

        return $session;
    }

    /**
     * @param  Generator  $indexList  Index list generated by `AssetIndexer::getIndexListOnVolume()`
     * @return int Number of entries inserted
     */
    public function storeIndexList(Generator $indexList, int $sessionId, Volume $volume): int
    {
        $values = [];
        $now = now();

        /** @var FsListing $volumeListing */
        foreach ($indexList as $volumeListing) {
            if ($volumeListing->getIsDir()) {
                $timestamp = null;
            } else {
                $dateModified = $volumeListing->getDateModified();
                $timestamp = $dateModified !== null ? Date::createFromTimestampUTC($dateModified) : $now;
            }

            $values[] = [
                'volumeId' => $volume->id,
                'sessionId' => $sessionId,
                'uri' => $volumeListing->getUri(),
                'size' => $volumeListing->getFileSize(),
                'timestamp' => $timestamp,
                'isDir' => $volumeListing->getIsDir(),
                'status' => AssetIndexStatus::Pending,
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
    public function processIndexSession(AssetIndexingSession $indexingSession): AssetIndexingSession
    {
        $lockName = "idx--{$indexingSession->id}--";
        $indexEntry = null;

        Cache::lock($lockName, 3)->block(3, function () use ($indexingSession, &$indexEntry) {
            $indexEntry = $this->getNextIndexEntry($indexingSession);

            if (! $indexEntry && ! $indexingSession->processIfRootEmpty) {
                if ($indexingSession->processedEntries < $indexingSession->totalEntries) {
                    $count = AssetIndexData::query()
                        ->where('sessionId', $indexingSession->id)
                        ->whereIn('status', [AssetIndexStatus::Pending, AssetIndexStatus::Processing])
                        ->count();

                    if ($count === 0) {
                        Log::info("The assetindexdata table is empty; Can't proceed with indexing.");
                        $indexingSession->forceStop = true;
                    }
                }

                return;
            }

            if ($indexEntry) {
                $indexEntry->transitionTo(AssetIndexStatus::Processing);
            }
        });

        if ($indexEntry) {
            try {
                if ($indexEntry->isDir) {
                    $recordId = $this->indexFolderByEntry($indexEntry)->id;
                } else {
                    $recordId = $this->indexFileByEntry($indexEntry, $indexingSession->cacheRemoteImages)->id;
                }

                $indexEntry->transitionTo(AssetIndexStatus::Indexed, $recordId);
            } catch (AssetDisallowedExtensionException|AssetNotIndexableException) {
                $indexEntry->transitionTo(AssetIndexStatus::Skipped);
            } catch (Throwable $exception) {
                report($exception);
                $indexEntry->transitionTo(AssetIndexStatus::Failed);
            }

            $this->incrementProcessedEntryCount($indexingSession);
        }

        if ($indexingSession->processedEntries == $indexingSession->totalEntries) {
            $indexingSession->actionRequired = true;

            if ($indexingSession->processIfRootEmpty) {
                $indexingSession->processIfRootEmpty = false;
            }

            $indexingSession->save();
        }

        return $indexingSession;
    }

    /** @return string[] */
    public function getSkippedItemsForSession(AssetIndexingSession $session): array
    {
        $skippedItems = DB::table(Table::ASSETINDEXDATA)
            ->select(['volumeId', 'uri'])
            ->where('sessionId', $session->id)
            ->whereIn('status', [
                AssetIndexStatus::Skipped,
                AssetIndexStatus::Missing,
                AssetIndexStatus::Failed,
            ])
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
    public function getMissingEntriesForSession(AssetIndexingSession $session, string $path = ''): array
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
                'folders.id as folderId',
            ])
            ->selectSub($this->folderAssetCountQuery($session), 'assetCount')
            ->when(
                $session->listEmptyFolders,
                fn (Builder $query) => $query->selectSub($this->folderAssetCountQuery($session, true), 'missingAssetCount'),
            )
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

        foreach ($missingFolders as $folder) {
            $hasAssets = (int) $folder['assetCount'];

            if ($hasAssets === 0 || ($session->listEmptyFolders && $hasAssets === (int) $folder['missingAssetCount'])) {
                $missing['folders'][$folder['folderId']] = "{$folder['volumeName']}/{$folder['path']}";
            }
        }

        foreach ($missingFiles as ['assetId' => $assetId, 'path' => $path, 'volumeName' => $volumeName, 'filename' => $filename]) {
            $missing['files'][$assetId] = "$volumeName/$path$filename";
        }

        return $missing;
    }

    private function folderAssetCountQuery(AssetIndexingSession $session, bool $missing = false): Builder
    {
        $query = DB::table(Table::ASSETS, 'countedAssets')
            ->selectRaw('count(*)')
            ->join(new Alias(Table::VOLUMEFOLDERS, 'countedFolders'), 'countedFolders.id', 'countedAssets.folderId')
            ->leftJoin(new Alias(Table::ELEMENTS, 'countedElements'), 'countedElements.id', 'countedAssets.id')
            ->whereColumn('countedAssets.volumeId', 'folders.volumeId')
            ->where('countedFolders.path', 'like', new Concat(['folders.path', new Value('%')]));

        if (! $missing) {
            return $query->where(fn (Builder $query) => $query
                ->whereNull('countedElements.dateDeleted')
                ->orWhere('countedAssets.keptFile', 1));
        }

        return $query
            ->leftJoin(new Alias(Table::ASSETINDEXDATA, 'countedIndexData'), fn (JoinClause $join) => $join
                ->whereColumn('countedAssets.id', 'countedIndexData.recordId')
                ->where('countedIndexData.isDir', false))
            ->where('countedAssets.dateCreated', '<', $session->dateCreated)
            ->whereNull('countedElements.dateDeleted')
            ->whereNull('countedIndexData.id');
    }

    public function getNextIndexEntry(AssetIndexingSession $session): ?AssetIndexData
    {
        return AssetIndexData::query()
            ->where('sessionId', $session->id)
            ->where('status', AssetIndexStatus::Pending)
            ->orderBy('id')
            ->first();
    }

    public function transitionIndexEntry(
        int $entryId,
        AssetIndexStatus $status,
        ?int $recordId = null,
    ): void {
        $entry = AssetIndexData::find($entryId);

        if (! $entry) {
            throw new RuntimeException("Asset index entry $entryId does not exist.");
        }

        $entry->transitionTo($status, $recordId);
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
        $indexEntry = new AssetIndexData([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getUri(),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
        ]);

        return $this->indexByListing(
            $indexEntry,
            fn () => $this->indexFileByEntry($indexEntry, $cacheImages, $createIfMissing),
        );
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
        $indexEntry = new AssetIndexData([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getUri(),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
        ]);

        return $this->indexByListing(
            $indexEntry,
            fn () => $this->indexFolderByEntry($indexEntry, $createIfMissing),
        );
    }

    /**
     * @template T of Asset|VolumeFolder
     *
     * @param  Closure():T  $index
     * @return T
     */
    private function indexByListing(AssetIndexData $indexEntry, Closure $index): Asset|VolumeFolder
    {
        $indexEntry->save();
        $indexEntry->transitionTo(AssetIndexStatus::Processing);

        try {
            $record = $index();
        } catch (Throwable $exception) {
            $status = match (true) {
                $exception instanceof MissingAssetException,
                $exception instanceof MissingVolumeFolderException => AssetIndexStatus::Missing,
                $exception instanceof AssetDisallowedExtensionException,
                $exception instanceof AssetNotIndexableException => AssetIndexStatus::Skipped,
                default => AssetIndexStatus::Failed,
            };

            $indexEntry->transitionTo($status);

            throw $exception;
        }

        $indexEntry->transitionTo(AssetIndexStatus::Indexed, $record->id);

        return $record;
    }

    /**
     * @throws AssetDisallowedExtensionException
     * @throws AssetNotIndexableException
     * @throws MissingAssetException
     * @throws VolumeException
     */
    public function indexFileByEntry(
        AssetIndexData $indexEntry,
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

        $asset->ruleset->useScenario(AssetRules::SCENARIO_INDEX);

        try {
            if ($isLocalFs) {
                $asset->setMimeType($asset->getMimeType());
            }

            if ($asset->kind === FileKind::Image->value) {
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
    public function indexFolderByEntry(AssetIndexData $indexEntry, bool $createIfMissing = true): VolumeFolder
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

    private function incrementProcessedEntryCount(AssetIndexingSession $session): void
    {
        $lockName = "idx--update-{$session->id}--";

        Cache::lock($lockName, 5)->block(5, function () use ($session) {
            $session->refresh();
            $session->increment('processedEntries');
        });
    }
}
