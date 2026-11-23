<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Deprecator;
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
        'disk' => 'volume-test',
        'subpath' => 'nested',
    ]);

    expect($volume->getDisk(false))->toBe('volume-test')
        ->and($volume->getResolvedDisk())->toBe('volume-test')
        ->and($volume->sourceDisk()->put('asset.txt', 'contents'))->toBeTrue()
        ->and(Storage::disk('volume-test')->get('nested/asset.txt'))->toBe('contents');
});

it('keeps the deprecated fs names working', function () {
    $fromFs = new Volume(['fs' => 'volume-test']);
    $fromFsHandle = new Volume(['fsHandle' => 'volume-test']);
    $volume = new Volume;
    $volume->fsHandle = 'volume-test';
    $other = new Volume;
    $other->setFsHandle('volume-test');

    expect($fromFs->getDisk())->toBe('volume-test')
        ->and($fromFsHandle->getDisk())->toBe('volume-test')
        ->and($volume->getDisk())->toBe('volume-test')
        ->and($volume->fsHandle)->toBe('volume-test')
        ->and($other->getFsHandle())->toBe('volume-test')
        ->and($other->getResolvedFsTarget())->toBe('volume-test')
        ->and($fromFs->getConfig())->toHaveKey('disk', 'volume-test')->not->toHaveKey('fs')
        ->and(collect(Deprecator::getRequestLogs())->pluck('key')->unique()->sort()->values()->all())->toBe([
            'Volume::fs',
            'Volume::fsHandle',
            'Volume::getFsHandle()',
            'Volume::getResolvedFsTarget()',
            'Volume::setFsHandle()',
        ]);
});

it('rejects missing and reserved Laravel disks', function (string $reference) {
    $volume = new Volume([
        'name' => 'Invalid',
        'handle' => 'invalid',
        'disk' => $reference,
    ]);

    expect($volume->validate(['disk']))->toBeFalse()
        ->and($volume->errors()->has('disk'))->toBeTrue();
})->with(['missing-disk', 'craft-tmp']);

it('allows unresolved environment-backed disk references', function () {
    $volume = new Volume([
        'name' => 'Environment',
        'handle' => 'environment',
        'disk' => '$UNRESOLVED_VOLUME_DISK',
    ]);

    expect($volume->validate(['disk']))->toBeTrue()
        ->and($volume->getDisk(false))->toBe('$UNRESOLVED_VOLUME_DISK');
});

it('uses the volume setting for URL capability rather than disk configuration', function () {
    $privateVolume = new Volume([
        'disk' => 'volume-test',
        'hasUrls' => false,
    ]);
    $publicVolume = new Volume([
        'disk' => 'volume-test',
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
        'disk' => 'volume-test',
        'hasUrls' => true,
        'subpath' => 'uploads',
        'sortOrder' => 3,
    ]);

    expect($volume->getConfig())
        ->toMatchArray([
            'name' => 'Config Volume',
            'handle' => 'configVolume',
            'disk' => 'volume-test',
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
