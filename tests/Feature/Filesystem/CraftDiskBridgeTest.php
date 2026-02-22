<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\DiskRegistry;
use CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Support\Facades\Filesystems as FilesystemsFacade;
use Illuminate\Support\Facades\Storage;

it('registers Craft filesystems as Laravel disks and exposes helper accessors', function () {
    createBridgeLocalFilesystem('bridge-helper');

    expect(config('filesystems.disks.craft-fs-bridge-helper.driver'))->toBe(DiskRegistry::BRIDGE_DRIVER)
        ->and(config('filesystems.disks.craft-fs-bridge-helper.fsHandle'))->toBe('bridge-helper')
        ->and(FilesystemsFacade::toDiskName('bridge-helper'))->toBe('craft-fs-bridge-helper');

    $disk = FilesystemsFacade::disk('bridge-helper');
    expect($disk->put('helper.txt', 'helper'))->toBeTrue();

    expect(Storage::disk('craft-fs-bridge-helper')->get('helper.txt'))->toBe('helper');
});

it('syncs and purges Craft disk registrations when filesystems are renamed or deleted', function () {
    $filesystem = createBridgeLocalFilesystem('bridge-old');

    Storage::disk('craft-fs-bridge-old')->put('before.txt', 'before');

    $filesystem->oldHandle = 'bridge-old';
    $filesystem->handle = 'bridge-new';
    $filesystem->name = 'Bridge New';

    expect(FilesystemsFacade::saveFilesystem($filesystem, false))->toBeTrue();

    expect(config('filesystems.disks.craft-fs-bridge-old'))->toBeNull()
        ->and(config('filesystems.disks.craft-fs-bridge-new.fsHandle'))->toBe('bridge-new');

    expect(fn () => Storage::disk('craft-fs-bridge-old'))->toThrow(InvalidArgumentException::class);

    expect(FilesystemsFacade::removeFilesystem($filesystem))->toBeTrue();
    expect(config('filesystems.disks.craft-fs-bridge-new'))->toBeNull();
    expect(fn () => Storage::disk('craft-fs-bridge-new'))->toThrow(InvalidArgumentException::class);
});

it('bridges disk operations through Craft handle disks', function () {
    createBridgeLocalFilesystem('bridge-ops');
    $disk = FilesystemsFacade::disk('bridge-ops');
    $bridgeDisk = Storage::disk('craft-fs-bridge-ops');

    $disk->put('from-disk.txt', 'from disk');

    expect($bridgeDisk->get('from-disk.txt'))->toBe('from disk');
    expect($bridgeDisk->put('from-bridge.txt', 'from bridge'))->toBeTrue();
    expect($disk->get('from-bridge.txt'))->toBe('from bridge');

    expect($bridgeDisk->copy('from-disk.txt', 'copy.txt'))->toBeTrue();
    expect($bridgeDisk->move('copy.txt', 'moved.txt'))->toBeTrue();

    expect($disk->files())->toContain('from-disk.txt');
    expect($disk->files())->toContain('from-bridge.txt');
    expect($disk->files())->toContain('moved.txt');

    expect($bridgeDisk->delete('from-disk.txt'))->toBeTrue();
    expect($disk->exists('from-disk.txt'))->toBeFalse();
});

it('provides Laravel disk configuration for built-in filesystem classes', function () {
    /** @var Local $localFilesystem */
    $localFilesystem = createBridgeLocalFilesystem('bridge-config-local');

    expect($localFilesystem->getDiskConfig())
        ->toHaveKey('driver', 'local')
        ->toHaveKey('root', $localFilesystem->getRootPath());

    $laravelDiskFs = new DiskFilesystem([
        'disk' => FilesystemsFacade::toDiskName('bridge-config-local'),
    ]);

    expect($laravelDiskFs->getDiskConfig())
        ->toHaveKey('driver', DiskRegistry::BRIDGE_DRIVER)
        ->toHaveKey('fsHandle', 'bridge-config-local');
});

function createBridgeLocalFilesystem(string $handle): FsInterface
{
    $filesystem = FilesystemsFacade::createFilesystem([
        'type' => Local::class,
        'name' => $handle,
        'handle' => $handle,
        'settings' => [
            'path' => sys_get_temp_dir()."/craft-disk-bridge/$handle",
        ],
    ]);

    expect(FilesystemsFacade::saveFilesystem($filesystem, false))->toBeTrue();

    return $filesystem;
}
