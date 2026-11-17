<?php

declare(strict_types=1);

use craft\helpers\Assets as LegacyAssets;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('urlAppendix() follows the disk’s rev_urls key', function() {
    config()->set('filesystems.disks.rev-urls-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/rev-urls-test'),
        'url' => 'https://cdn.test/assets',
    ]);
    $volume = Volume::factory()->create(['fs' => 'rev-urls-test', 'hasUrls' => true]);
    $folder = VolumeFolder::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'photo.jpg',
        'dateModified' => now(),
    ]);

    expect(LegacyAssets::urlAppendix($asset))->toBe('');

    config()->set('filesystems.disks.rev-urls-test.rev_urls', true);

    expect(LegacyAssets::urlAppendix($asset))->toMatch('/^\?v=\d+$/');
});
