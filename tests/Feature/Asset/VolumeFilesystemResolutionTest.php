<?php

declare(strict_types=1);

use craft\base\FsInterface;
use craft\fs\LaravelDiskFs;
use craft\models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

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

    expect($volume->getFs())->toBeInstanceOf(LaravelDiskFs::class)
        ->and($volume->getFs()->disk)->toBe('explicit-disk');
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
        ->and($craftVolume->getFs())->not->toBeInstanceOf(LaravelDiskFs::class);

    $diskVolume = new Volume([
        'name' => 'Disk-backed',
        'handle' => 'diskBacked',
        'fsHandle' => 'manual-only',
    ]);

    expect($diskVolume->getFs())->toBeInstanceOf(LaravelDiskFs::class)
        ->and($diskVolume->getFsHandle(false))->toBe('disk:manual-only')
        ->and($diskVolume->getFs()->disk)->toBe('manual-only');
});

it('normalizes plain disk handles to disk-prefixed values on assignment', function () {
    config()->set('filesystems.disks.normalize-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/normalize-disk'),
    ]);

    $volume = new Volume([
        'name' => 'Normalize',
        'handle' => 'normalizeVolume',
        'fsHandle' => 'normalize-disk',
        'transformFsHandle' => 'normalize-disk',
    ]);

    expect($volume->getFsHandle(false))->toBe('disk:normalize-disk')
        ->and($volume->getTransformFsHandle(false))->toBe('disk:normalize-disk');
});

it('validates literal filesystem references and permits unresolved env values', function () {
    $invalid = new Volume([
        'name' => 'Invalid',
        'handle' => 'invalidVolume',
        'fsHandle' => 'missing-filesystem',
    ]);

    expect($invalid->validate(['fsHandle']))->toBeFalse();
    expect($invalid->hasErrors('fsHandle'))->toBeTrue();

    $unresolved = new Volume([
        'name' => 'Unresolved',
        'handle' => 'unresolvedVolume',
        'fsHandle' => '$UNRESOLVED_FS',
    ]);

    expect($unresolved->validate(['fsHandle']))->toBeTrue();
    expect($unresolved->hasErrors('fsHandle'))->toBeFalse();
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
    expect($volume->hasErrors('subpath'))->toBeTrue();
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
    expect($prefixed->hasErrors('fsHandle'))->toBeTrue();

    $plain = new Volume([
        'name' => 'Plain Internal',
        'handle' => 'plainInternal',
        'fsHandle' => 'craft-tmp',
    ]);

    expect($plain->validate(['fsHandle']))->toBeFalse();
    expect($plain->hasErrors('fsHandle'))->toBeTrue();
});

it('resolves tempAssetUploadFs for Craft handles and disk targets', function () {
    config()->set('filesystems.disks.temp-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-disks/temp-disk'),
    ]);

    Cms::config()->tempAssetUploadFs = 'disk:temp-disk';

    $diskTarget = \Craft::$app->getAssets()->getTempAssetUploadFs();
    expect($diskTarget)->toBeInstanceOf(LaravelDiskFs::class)
        ->and($diskTarget->disk)->toBe('temp-disk');

    Cms::config()->tempAssetUploadFs = 'temp-disk';

    $plainFallbackTarget = \Craft::$app->getAssets()->getTempAssetUploadFs();
    expect($plainFallbackTarget)->toBeInstanceOf(LaravelDiskFs::class)
        ->and($plainFallbackTarget->disk)->toBe('temp-disk');

    createVolumeLocalFilesystem('temp-disk');

    $fsFirstTarget = \Craft::$app->getAssets()->getTempAssetUploadFs();
    expect($fsFirstTarget)->not->toBeInstanceOf(LaravelDiskFs::class);
});

function createVolumeLocalFilesystem(string $handle): FsInterface
{
    $filesystem = \Craft::$app->getFs()->createFilesystem([
        'type' => 'craft\\fs\\Local',
        'name' => $handle,
        'handle' => $handle,
        'settings' => [
            'path' => sys_get_temp_dir()."/volume-filesystems/$handle",
        ],
    ]);

    expect(\Craft::$app->getFs()->saveFilesystem($filesystem, false))->toBeTrue();

    return $filesystem;
}
