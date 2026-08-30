<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\Support\Facades\Filesystems;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('resolves explicit disk targets to Laravel disk wrappers', function () {
    config()->set('filesystems.disks.explicit-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/explicit-disk'),
    ]);

    createVolumeLocalFilesystem('explicit-disk');

    $volume = new Volume([
        'name' => 'Volume',
        'handle' => 'volume',
        'fsHandle' => 'disk:explicit-disk',
    ]);

    expect($volume->getFs())->toBeInstanceOf(DiskFilesystem::class)
        ->and($volume->getFs()->disk)->toBe('explicit-disk');
});

it('hydrates URL settings on Laravel disk wrappers resolved from volumes', function () {
    config()->set('filesystems.disks.url-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/url-disk'),
        'url' => 'https://cdn.example.test/assets/',
    ]);

    $volume = new Volume([
        'name' => 'URL Disk',
        'handle' => 'urlDisk',
        'fsHandle' => 'disk:url-disk',
    ]);

    $filesystem = $volume->getFs();

    expect($filesystem)->toBeInstanceOf(DiskFilesystem::class)
        ->and($filesystem->hasUrls)->toBeTrue()
        ->and($filesystem->url)->toBe('https://cdn.example.test/assets');
});

it('resolves plain values as Craft filesystems first, then Laravel disks', function () {
    config()->set('filesystems.disks.shared-target', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/shared-target'),
    ]);
    config()->set('filesystems.disks.manual-only', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/manual-only'),
    ]);

    createVolumeLocalFilesystem('shared-target');

    $craftVolume = new Volume([
        'name' => 'Craft-backed',
        'handle' => 'craftBacked',
        'fsHandle' => 'shared-target',
    ]);

    expect($craftVolume->getFs())->toBeInstanceOf(FsInterface::class)
        ->and($craftVolume->getFsHandle(false))->toBe('shared-target')
        ->and($craftVolume->getFs())->not->toBeInstanceOf(DiskFilesystem::class);

    $diskVolume = new Volume([
        'name' => 'Disk-backed',
        'handle' => 'diskBacked',
        'fsHandle' => 'manual-only',
    ]);

    expect($diskVolume->getFs())->toBeInstanceOf(DiskFilesystem::class)
        ->and($diskVolume->getFsHandle(false))->toBe('disk:manual-only')
        ->and($diskVolume->getFs()->disk)->toBe('manual-only');
});

it('validates literal filesystem references and permits unresolved env values', function () {
    $invalid = new Volume([
        'name' => 'Invalid',
        'handle' => 'invalidVolume',
        'fsHandle' => 'missing-filesystem',
    ]);

    expect($invalid->validate(['fsHandle']))->toBeFalse();
    expect($invalid->errors()->has('fsHandle'))->toBeTrue();

    $unresolved = new Volume([
        'name' => 'Unresolved',
        'handle' => 'unresolvedVolume',
        'fsHandle' => '$UNRESOLVED_FS',
    ]);

    expect($unresolved->validate(['fsHandle']))->toBeTrue();
    expect($unresolved->errors()->has('fsHandle'))->toBeFalse();
});

it('normalizes subpath uniqueness checks by resolved storage target', function () {
    config()->set('filesystems.disks.shared-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/shared-disk'),
    ]);

    DB::table(Table::VOLUMES)->insert([
        'name' => 'Existing',
        'handle' => 'existingVolume',
        'fs' => 'disk:shared-disk',
        'subpath' => 'foo/bar',
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    $volume = new Volume([
        'name' => 'New',
        'handle' => 'newVolume',
        'fsHandle' => 'shared-disk',
        'subpath' => 'foo',
    ]);

    expect($volume->validate(['subpath']))->toBeFalse();
    expect($volume->errors()->has('subpath'))->toBeTrue();
});

it('rejects internal disk targets', function () {
    config()->set('filesystems.disks.craft-tmp', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/craft-tmp'),
    ]);

    $prefixed = new Volume([
        'name' => 'Prefixed Internal',
        'handle' => 'prefixedInternal',
        'fsHandle' => 'disk:craft-tmp',
    ]);

    expect($prefixed->validate(['fsHandle']))->toBeFalse();
    expect($prefixed->errors()->has('fsHandle'))->toBeTrue();

    $plain = new Volume([
        'name' => 'Plain Internal',
        'handle' => 'plainInternal',
        'fsHandle' => 'craft-tmp',
    ]);

    expect($plain->validate(['fsHandle']))->toBeFalse();
    expect($plain->errors()->has('fsHandle'))->toBeTrue();
});

it('resolves tempAssetUploadFs for Craft handles and disk targets', function () {
    config()->set('filesystems.disks.temp-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/temp-disk'),
    ]);

    Cms::config()->tempAssetUploadFs = 'disk:temp-disk';

    $diskTarget = Assets::getTempAssetUploadFs();
    expect($diskTarget)->toBeInstanceOf(DiskFilesystem::class)
        ->and($diskTarget->disk)->toBe('temp-disk')
        ->and(AssetsHelper::isTempUploadFs($diskTarget))->toBeTrue();

    Cms::config()->tempAssetUploadFs = 'temp-disk';

    $plainFallbackTarget = Assets::getTempAssetUploadFs();
    expect($plainFallbackTarget)->toBeInstanceOf(DiskFilesystem::class)
        ->and($plainFallbackTarget->disk)->toBe('temp-disk')
        ->and(AssetsHelper::isTempUploadFs($plainFallbackTarget))->toBeTrue();

    createVolumeLocalFilesystem('temp-disk');

    $fsFirstTarget = Assets::getTempAssetUploadFs();
    expect($fsFirstTarget)->not->toBeInstanceOf(DiskFilesystem::class)
        ->and(AssetsHelper::isTempUploadFs($fsFirstTarget))->toBeTrue();
});

it('applies laravel filesystem operations via sourceDisk() with volume subpaths', function () {
    config()->set('filesystems.disks.disk-forwarding', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/disk-forwarding'),
    ]);

    createVolumeLocalFilesystem('disk-forwarding');

    $volume = new Volume([
        'name' => 'Forwarding',
        'handle' => 'forwarding',
        'fsHandle' => 'disk-forwarding',
        'subpath' => 'nested',
    ]);

    $disk = $volume->sourceDisk();

    expect($disk->put('alpha.txt', 'alpha'))->toBeTrue()
        ->and($disk->exists('alpha.txt'))->toBeTrue()
        ->and($disk->get('alpha.txt'))->toBe('alpha');

    expect($disk->copy('alpha.txt', 'copy.txt'))->toBeTrue()
        ->and($disk->move('copy.txt', 'moved.txt'))->toBeTrue();

    expect($disk->makeDirectory('docs'))->toBeTrue()
        ->and($disk->put('docs/readme.txt', 'readme'))->toBeTrue();

    expect($disk->files())->toContain('alpha.txt')
        ->and($disk->files())->toContain('moved.txt')
        ->and($disk->directories())->toContain('docs')
        ->and($disk->allFiles())->toContain('docs/readme.txt')
        ->and($disk->size('alpha.txt'))->toBe(5)
        ->and($disk->lastModified('alpha.txt'))->toBeInt();

    $storageDisk = Storage::disk('craft-fs-disk-forwarding');
    expect($storageDisk->exists('nested/alpha.txt'))->toBeTrue()
        ->and($storageDisk->exists('nested/moved.txt'))->toBeTrue()
        ->and($storageDisk->exists('nested/docs/readme.txt'))->toBeTrue();

    expect($disk->delete('moved.txt'))->toBeTrue();
    expect($storageDisk->exists('nested/moved.txt'))->toBeFalse();
});

it('throws when getFs() is called without a filesystem handle', function () {
    $volume = new Volume([
        'name' => 'No Handle',
        'handle' => 'noHandle',
    ]);

    $volume->getFs();
})->throws(RuntimeException::class, 'Volume is missing its filesystem handle.');

it('returns MissingFs when the filesystem handle cannot be resolved', function () {
    $volume = new Volume([
        'name' => 'Bad Handle',
        'handle' => 'badHandle',
        'fsHandle' => 'nonexistent-fs-or-disk',
    ]);

    $fs = $volume->getFs();

    expect($fs)->toBeInstanceOf(MissingFs::class);
});

it('serializes volume config correctly via getConfig()', function () {
    createVolumeLocalFilesystem('config-fs');

    $volume = new Volume([
        'name' => 'Config Volume',
        'handle' => 'configVolume',
        'fsHandle' => 'config-fs',
        'subpath' => 'uploads',
        'titleTranslationMethod' => 'site',
        'altTranslationMethod' => 'none',
        'sortOrder' => 3,
    ]);

    $config = $volume->getConfig();

    expect($config['name'])->toBe('Config Volume')
        ->and($config['handle'])->toBe('configVolume')
        ->and($config['fs'])->toBe('config-fs')
        ->and($config['subpath'])->toBe('uploads')
        ->and($config['titleTranslationMethod'])->toBe('site')
        ->and($config['altTranslationMethod'])->toBe('none')
        ->and($config['sortOrder'])->toBe(3);
});

it('serializes disk-prefixed fs handles in getConfig()', function () {
    config()->set('filesystems.disks.config-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/config-disk'),
    ]);

    $volume = new Volume([
        'name' => 'Disk Config',
        'handle' => 'diskConfig',
        'fsHandle' => 'config-disk',
    ]);

    $config = $volume->getConfig();

    expect($config['fs'])->toBe('disk:config-disk');
});

function createVolumeLocalFilesystem(string $handle): FsInterface
{
    $filesystem = Filesystems::createFilesystem([
        'type' => Local::class,
        'name' => $handle,
        'handle' => $handle,
        'settings' => [
            'path' => sys_get_temp_dir()."/volume-filesystems/$handle",
        ],
    ]);

    expect(Filesystems::saveFilesystem($filesystem, false))->toBeTrue();

    return $filesystem;
}
