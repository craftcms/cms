<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetIndexer;
use CraftCms\Cms\Asset\Data\AssetIndexEntry;
use CraftCms\Cms\Asset\Data\IndexingSession;
use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Models\AssetIndexData;
use CraftCms\Cms\Asset\Models\AssetIndexingSession;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Support\Facades\AssetIndexer as AssetIndexerFacade;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-indexer-test/test-disk'),
    ]);

    $this->indexer = app(AssetIndexer::class);
    $this->volumes = app(Volumes::class);
});

it('is a singleton', function () {
    expect(AssetIndexerFacade::getFacadeRoot())->toBe(app(AssetIndexer::class));
    expect($this->indexer)->toBe(app(AssetIndexer::class));
});

it('can create an indexing session', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession(
        volumeList: [$volumeData],
        cacheRemoteImages: false,
        isCli: true,
        listEmptyFolders: false,
    );

    expect($session)->toBeInstanceOf(IndexingSession::class);
    expect($session->id)->not()->toBeNull();
    expect($session->totalEntries)->toBe(0);
    expect($session->processedEntries)->toBe(0);
    expect($session->cacheRemoteImages)->toBeFalse();
    expect($session->isCli)->toBeTrue();
    expect($session->listEmptyFolders)->toBeFalse();
    expect($session->actionRequired)->toBeFalse();

    expect(AssetIndexingSession::find($session->id))->not()->toBeNull();
});

it('can get an indexing session by id', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);

    $found = $this->indexer->getIndexingSessionById($session->id);

    expect($found)->toBeInstanceOf(IndexingSession::class);
    expect($found->id)->toBe($session->id);
});

it('returns null for non-existent indexing session', function () {
    expect($this->indexer->getIndexingSessionById(999999))->toBeNull();
});

it('can get existing non-cli indexing sessions', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $this->indexer->createIndexingSession([$volumeData], isCli: false);
    $this->indexer->createIndexingSession([$volumeData], isCli: true);

    $sessions = $this->indexer->getExistingIndexingSessions();

    expect($sessions)->toHaveCount(1);
    expect($sessions[0]->isCli)->toBeFalse();
});

it('can remove cli indexing sessions', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $this->indexer->createIndexingSession([$volumeData], isCli: true);
    $this->indexer->createIndexingSession([$volumeData], isCli: true);
    $this->indexer->createIndexingSession([$volumeData], isCli: false);

    expect(AssetIndexingSession::count())->toBe(3);

    $removed = $this->indexer->removeCliIndexingSessions();

    expect($removed)->toBe(2);
    expect(AssetIndexingSession::count())->toBe(1);
});

it('can stop an indexing session', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);

    expect(AssetIndexingSession::find($session->id))->not()->toBeNull();

    $this->indexer->stopIndexingSession($session);

    expect(AssetIndexingSession::find($session->id))->toBeNull();
});

it('can store an index list from a generator', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);

    $listings = createIndexerTestListings();

    $count = $this->indexer->storeIndexList($listings, $session->id, $volumeData);

    expect($count)->toBe(3);
    expect(AssetIndexData::where('sessionId', $session->id)->count())->toBe(3);

    $entries = AssetIndexData::where('sessionId', $session->id)->orderBy('id')->get();

    expect($entries[0]->uri)->toBe('images');
    expect($entries[0]->isDir)->toBeTrue();

    expect($entries[1]->uri)->toBe('images/photo.jpg');
    expect($entries[1]->isDir)->toBeFalse();

    expect($entries[2]->uri)->toBe('document.pdf');
    expect($entries[2]->isDir)->toBeFalse();
});

it('can get the next index entry', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);
    $this->indexer->storeIndexList(createIndexerTestListings(), $session->id, $volumeData);

    $entry = $this->indexer->getNextIndexEntry($session);

    expect($entry)->toBeInstanceOf(AssetIndexEntry::class);
    expect($entry->uri)->toBe('images');
    expect($entry->isDir)->toBeTrue();
    expect($entry->completed)->toBeFalse();
    expect($entry->inProgress)->toBeFalse();
});

it('returns null when no more index entries', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);

    expect($this->indexer->getNextIndexEntry($session))->toBeNull();
});

it('can update an index entry', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);
    $this->indexer->storeIndexList(createIndexerTestListings(), $session->id, $volumeData);

    $entry = $this->indexer->getNextIndexEntry($session);

    $this->indexer->updateIndexEntry($entry->id, ['inProgress' => true]);

    $updated = DB::table(Table::ASSETINDEXDATA)->where('id', $entry->id)->first();
    expect((bool) $updated->inProgress)->toBeTrue();

    $this->indexer->updateIndexEntry($entry->id, ['completed' => true, 'inProgress' => false, 'recordId' => 42]);

    $updated = DB::table(Table::ASSETINDEXDATA)->where('id', $entry->id)->first();
    expect((bool) $updated->completed)->toBeTrue();
    expect((bool) $updated->inProgress)->toBeFalse();
    expect((int) $updated->recordId)->toBe(42);
});

it('only allows whitelisted fields in updateIndexEntry', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);
    $this->indexer->storeIndexList(createIndexerTestListings(), $session->id, $volumeData);

    $entry = $this->indexer->getNextIndexEntry($session);
    $originalUri = $entry->uri;

    $this->indexer->updateIndexEntry($entry->id, ['uri' => 'hacked/path.jpg', 'inProgress' => true]);

    $updated = DB::table(Table::ASSETINDEXDATA)->where('id', $entry->id)->first();
    expect($updated->uri)->toBe($originalUri);
    expect((bool) $updated->inProgress)->toBeTrue();
});

it('skips completed and in-progress entries when getting next entry', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);
    $this->indexer->storeIndexList(createIndexerTestListings(), $session->id, $volumeData);

    $first = $this->indexer->getNextIndexEntry($session);
    $this->indexer->updateIndexEntry($first->id, ['inProgress' => true]);

    $second = $this->indexer->getNextIndexEntry($session);
    expect($second->id)->not()->toBe($first->id);
    expect($second->uri)->toBe('images/photo.jpg');
});

it('can get skipped items for a session', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);
    $this->indexer->storeIndexList(createIndexerTestListings(), $session->id, $volumeData);

    $entry = $this->indexer->getNextIndexEntry($session);
    $this->indexer->updateIndexEntry($entry->id, ['completed' => true, 'isSkipped' => true]);

    $skipped = $this->indexer->getSkippedItemsForSession($session);

    expect($skipped)->toHaveCount(1);
    expect($skipped[0])->toContain($entry->uri);
});

it('throws when getting missing entries for unfinished session', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);

    $this->indexer->getMissingEntriesForSession($session);
})->throws(AssetException::class, 'A session must be finished before missing entries can be fetched');

it('returns empty missing entries for finished session with no actual content', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);
    $session->actionRequired = true;

    $missing = $this->indexer->getMissingEntriesForSession($session);

    expect($missing)->toHaveKey('folders');
    expect($missing)->toHaveKey('files');
    expect($missing['folders'])->toBeEmpty();
    expect($missing['files'])->toBeEmpty();
});

it('stores session options correctly', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession(
        volumeList: [$volumeData],
        cacheRemoteImages: true,
        isCli: false,
        listEmptyFolders: true,
    );

    $record = AssetIndexingSession::findOrFail($session->id);

    expect($record->cacheRemoteImages)->toBeTrue();
    expect($record->isCli)->toBeFalse();
    expect($record->listEmptyFolders)->toBeTrue();
});

it('encodes indexed volumes as json', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $session = $this->indexer->createIndexingSession([$volumeData]);

    $decoded = json_decode((string) $session->indexedVolumes, true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey((string) $volumeData->id);
    expect($decoded[(string) $volumeData->id])->toBe($volumeData->name);
});

it('can start an indexing session with files on disk', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $diskRoot = storage_path('framework/testing/asset-indexer-test/test-disk');
    @mkdir("$diskRoot/subfolder", 0777, true);
    file_put_contents("$diskRoot/test-file.txt", 'hello');
    file_put_contents("$diskRoot/subfolder/nested.txt", 'world');

    $session = $this->indexer->startIndexingSession(
        volumes: [$volumeData->id],
        cacheRemoteImages: false,
        listEmptyFolders: false,
    );

    expect($session)->toBeInstanceOf(IndexingSession::class);
    expect($session->totalEntries)->toBeGreaterThanOrEqual(2);

    $indexEntries = AssetIndexData::where('sessionId', $session->id)->count();
    expect($indexEntries)->toBe($session->totalEntries);

    @unlink("$diskRoot/test-file.txt");
    @unlink("$diskRoot/subfolder/nested.txt");
    @rmdir("$diskRoot/subfolder");
});

it('sets processIfRootEmpty when volume has no files', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $diskRoot = storage_path('framework/testing/asset-indexer-test/test-disk');
    @mkdir($diskRoot, 0777, true);

    // Remove any leftover files
    array_map(unlink(...), glob("$diskRoot/*") ?: []);

    $session = $this->indexer->startIndexingSession(
        volumes: [$volumeData->id],
        cacheRemoteImages: false,
        listEmptyFolders: false,
    );

    expect($session->totalEntries)->toBe(0);
    expect($session->processIfRootEmpty)->toBeTrue();
});

it('can get index list on volume', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $diskRoot = storage_path('framework/testing/asset-indexer-test/test-disk');
    @mkdir($diskRoot, 0777, true);
    file_put_contents("$diskRoot/sample.txt", 'content');

    $listings = iterator_to_array($this->indexer->getIndexListOnVolume($volumeData));

    expect($listings)->not()->toBeEmpty();

    $uris = array_map(fn (FsListing $l) => $l->uri, $listings);
    expect($uris)->toContain('sample.txt');

    @unlink("$diskRoot/sample.txt");
});

it('skips directories starting with underscore in index list', function () {
    $volume = createIndexerTestVolume();
    $volumeData = resolveIndexerVolumeData($volume);

    $diskRoot = storage_path('framework/testing/asset-indexer-test/test-disk');
    @mkdir("$diskRoot/_private", 0777, true);
    file_put_contents("$diskRoot/_private/secret.txt", 'hidden');
    file_put_contents("$diskRoot/visible.txt", 'shown');

    $listings = iterator_to_array($this->indexer->getIndexListOnVolume($volumeData));
    $uris = array_map(fn (FsListing $l) => $l->uri, $listings);

    expect($uris)->toContain('visible.txt');
    expect($uris)->not()->toContain('_private/secret.txt');
    expect($uris)->not()->toContain('_private');

    @unlink("$diskRoot/_private/secret.txt");
    @rmdir("$diskRoot/_private");
    @unlink("$diskRoot/visible.txt");
});

// --- Helper functions ---

function createIndexerTestVolume(): Volume
{
    return Volume::factory()->create(['fs' => 'disk:test-disk']);
}

function resolveIndexerVolumeData(Volume $volume): VolumeData
{
    app()->forgetInstance(Volumes::class);

    return app(Volumes::class)->getVolumeById($volume->id);
}

function createIndexerTestListings(): Generator
{
    $listings = [
        new FsListing([
            'dirname' => '',
            'basename' => 'images',
            'type' => 'dir',
            'fileSize' => null,
            'dateModified' => null,
        ]),
        new FsListing([
            'dirname' => 'images',
            'basename' => 'photo.jpg',
            'type' => 'file',
            'fileSize' => 204800,
            'dateModified' => time(),
        ]),
        new FsListing([
            'dirname' => '',
            'basename' => 'document.pdf',
            'type' => 'file',
            'fileSize' => 102400,
            'dateModified' => time(),
        ]),
    ];

    foreach ($listings as $listing) {
        yield $listing;
    }
}
