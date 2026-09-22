<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Cms;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('filesystems.disks.volume-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/volume-test'),
    ]);
    Storage::disk('volume-test')->deleteDirectory('');
});

it('uses configured Laravel disk names and performs scoped operations', function () {
    $volume = new Volume([
        'name' => 'Volume',
        'handle' => 'volume',
        'fsHandle' => 'volume-test',
        'subpath' => 'nested',
    ]);

    expect($volume->getFsHandle(false))->toBe('volume-test')
        ->and($volume->getResolvedFsTarget())->toBe('volume-test')
        ->and($volume->sourceDisk()->put('asset.txt', 'contents'))->toBeTrue()
        ->and(Storage::disk('volume-test')->get('nested/asset.txt'))->toBe('contents');
});

it('rejects missing and reserved Laravel disks', function (string $reference) {
    $volume = new Volume([
        'name' => 'Invalid',
        'handle' => 'invalid',
        'fsHandle' => $reference,
    ]);

    expect($volume->validate(['fsHandle']))->toBeFalse()
        ->and($volume->errors()->has('fsHandle'))->toBeTrue();
})->with(['missing-disk', 'craft-tmp']);

it('allows unresolved environment-backed disk references', function () {
    $volume = new Volume([
        'name' => 'Environment',
        'handle' => 'environment',
        'fsHandle' => '$UNRESOLVED_VOLUME_DISK',
    ]);

    expect($volume->validate(['fsHandle']))->toBeTrue()
        ->and($volume->getFsHandle(false))->toBe('$UNRESOLVED_VOLUME_DISK');
});

it('uses the volume setting for URL capability rather than disk configuration', function () {
    $privateVolume = new Volume([
        'fsHandle' => 'volume-test',
        'hasUrls' => false,
    ]);
    $publicVolume = new Volume([
        'fsHandle' => 'volume-test',
        'hasUrls' => true,
    ]);

    expect($privateVolume->sourceDisk()->url('asset.jpg'))->not->toBeEmpty()
        ->and(config('filesystems.disks.volume-test.url'))->toBeNull()
        ->and($privateVolume->sourceHasUrls())->toBeFalse()
        ->and($publicVolume->sourceHasUrls())->toBeTrue();
});

it('serializes the disk reference and URL capability in project config', function () {
    $volume = new Volume([
        'name' => 'Config Volume',
        'handle' => 'configVolume',
        'fsHandle' => 'volume-test',
        'hasUrls' => true,
        'subpath' => 'uploads',
        'sortOrder' => 3,
    ]);

    expect($volume->getConfig())
        ->toMatchArray([
            'name' => 'Config Volume',
            'handle' => 'configVolume',
            'fs' => 'volume-test',
            'hasUrls' => true,
            'subpath' => 'uploads',
            'sortOrder' => 3,
        ]);
});

it('uses a dedicated Laravel disk for temporary assets', function () {
    Cms::config()->tempAssetUploadDisk = 'volume-test';

    expect(app(Assets::class)->getTempAssetUploadDisk())
        ->toBe(Storage::disk('volume-test'));
});
