<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\ImageDuplicates;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('filesystems.disks.duplicates-test', ['driver' => 'local', 'root' => storage_path('framework/testing/duplicates-test')]);
    $volume = VolumeModel::factory()->create(['fs' => 'duplicates-test']);
    app(Volumes::class)->reset();
    $GLOBALS['duplicatesFolder'] = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
});

function hashedImage(?string $hash): Asset
{
    $folder = $GLOBALS['duplicatesFolder'];
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $folder->volumeId,
        'folderId' => $folder->id,
        'kind' => 'image',
        'filename' => uniqid('photo-').'.png',
    ]);
    $asset->analysis = new ImageAnalysis(hash: $hash);
    Elements::saveElement($asset);

    return $asset;
}

/** @return list<int> */
function duplicateIds(Asset $asset): array
{
    return array_map(fn (Asset $duplicate): int => $duplicate->id, app(ImageDuplicates::class)->find($asset));
}

it('finds images whose hashes differ in a few bits, closest first', function () {
    $original = hashedImage('fcfbf8e7ecdad4a3');
    $resized = hashedImage('fcfbf8e7ecdad4a2');
    $recompressed = hashedImage('fcfbf8e7ecdad0a0');
    $exact = hashedImage('fcfbf8e7ecdad4a3');
    $different = hashedImage('0304071813252b5c');
    $sharesOnlyABand = hashedImage('fcfb000000000000');

    expect(duplicateIds($original))->toBe([$exact->id, $resized->id, $recompressed->id])
        ->and(duplicateIds($different))->toBe([]);
});

it('finds copies whose hashes differ in up to three bits, wherever they are', function () {
    $original = hashedImage('fcfbf8e7ecdad4a3');
    $copy = hashedImage('fcfaf8e6ecdbd4a3');

    expect(duplicateIds($original))->toBe([$copy->id]);
});

it('forgets images’ hashes once they’re gone', function () {
    $original = hashedImage('fcfbf8e7ecdad4a3');
    $copy = hashedImage('fcfbf8e7ecdad4a3');

    $copy->analysis = new ImageAnalysis;
    Elements::saveElement($copy);
    expect(duplicateIds($original))->toBe([]);

    $copy->analysis = new ImageAnalysis(hash: 'fcfbf8e7ecdad4a3');
    Elements::saveElement($copy);
    expect(duplicateIds($original))->toBe([$copy->id]);

    Elements::deleteElement($copy, hardDelete: true);
    expect(duplicateIds($original))->toBe([])
        ->and(DB::table(Table::IMAGEHASHES)->where('assetId', $copy->id)->exists())->toBeFalse();
});

it('leaves out images in the trash', function () {
    $original = hashedImage('fcfbf8e7ecdad4a3');
    $copy = hashedImage('fcfbf8e7ecdad4a3');

    Elements::deleteElement($copy);

    expect(duplicateIds($original))->toBe([]);
});

it('finds nothing for images without a hash', function () {
    hashedImage('fcfbf8e7ecdad4a3');

    expect(duplicateIds(hashedImage(null)))->toBe([])
        ->and(app(ImageDuplicates::class)->findByHash('not a hash'))->toBe([]);
});
