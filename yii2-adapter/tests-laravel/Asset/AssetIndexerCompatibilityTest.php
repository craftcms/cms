<?php

declare(strict_types=1);

use craft\models\AssetIndexData;
use craft\services\AssetIndexer;
use CraftCms\Cms\Asset\Exceptions\MissingAssetException;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('ignores legacy cache flags without shifting session options', function() {
    $indexer = new AssetIndexer();
    $created = $indexer->createIndexingSession([], true, false, true);
    $started = $indexer->startIndexingSession([], true, false);

    expect($created->fresh()->isCli)->toBeFalse()
        ->and($created->fresh()->listEmptyFolders)->toBeTrue()
        ->and($started->fresh()->listEmptyFolders)->toBeFalse();
});

it('preserves createIfMissing when legacy image caching is requested', function(string $method) {
    config()->set('filesystems.disks.legacy-index-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/legacy-index-test'),
    ]);
    $volume = Volume::factory()->create(['disk' => 'legacy-index-test']);
    VolumeFolder::factory()->create(['volumeId' => $volume->id]);
    $volume = app(Volumes::class)->getVolumeById($volume->id);
    $volume->sourceDisk()->put('missing.txt', 'text');
    $indexer = new AssetIndexer();
    $session = $indexer->createIndexingSession([$volume]);
    $entry = new AssetIndexData([
        'volumeId' => $volume->id,
        'sessionId' => $session->id,
        'uri' => 'missing.txt',
    ]);

    try {
        expect(fn() => match ($method) {
            'indexFile' => $indexer->indexFile($volume, 'missing.txt', $session->id, true, false),
            'indexFileByListing' => $indexer->indexFileByListing($volume, new FsListing([
                'dirname' => '',
                'basename' => 'missing.txt',
                'type' => 'file',
            ]), $session->id, true, false),
            'indexFileByEntry' => $indexer->indexFileByEntry($entry, true, false),
        })->toThrow(MissingAssetException::class);
    } finally {
        $volume->sourceDisk()->delete('missing.txt');
    }
})->with(['indexFile', 'indexFileByListing', 'indexFileByEntry']);
