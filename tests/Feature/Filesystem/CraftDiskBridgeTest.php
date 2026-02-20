<?php

declare(strict_types=1);

use craft\base\FsInterface;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use Illuminate\Support\Facades\Storage;

it('registers Craft filesystems as Laravel disks and exposes helper accessors', function () {
    $filesystem = createBridgeLocalFilesystem('bridge-helper');

    expect(config('filesystems.disks.craft-fs-bridge-helper.driver'))->toBe(LegacyFsFlysystemAdapter::DISK_DRIVER)
        ->and(config('filesystems.disks.craft-fs-bridge-helper.fsHandle'))->toBe('bridge-helper')
        ->and(\Craft::$app->getFs()->toDiskName('bridge-helper'))->toBe('craft-fs-bridge-helper');

    $disk = \Craft::$app->getFs()->disk('bridge-helper');
    $disk->put('helper.txt', 'helper');

    expect($filesystem->read('helper.txt'))->toBe('helper');
});

it('syncs and purges Craft disk registrations when filesystems are renamed or deleted', function () {
    $filesystem = createBridgeLocalFilesystem('bridge-old');

    Storage::disk('craft-fs-bridge-old')->put('before.txt', 'before');

    $filesystem->oldHandle = 'bridge-old';
    $filesystem->handle = 'bridge-new';
    $filesystem->name = 'Bridge New';

    expect(\Craft::$app->getFs()->saveFilesystem($filesystem, false))->toBeTrue();

    expect(config('filesystems.disks.craft-fs-bridge-old'))->toBeNull()
        ->and(config('filesystems.disks.craft-fs-bridge-new.fsHandle'))->toBe('bridge-new');

    expect(fn () => Storage::disk('craft-fs-bridge-old'))->toThrow(\InvalidArgumentException::class);

    expect(\Craft::$app->getFs()->removeFilesystem($filesystem))->toBeTrue();
    expect(config('filesystems.disks.craft-fs-bridge-new'))->toBeNull();
    expect(fn () => Storage::disk('craft-fs-bridge-new'))->toThrow(\InvalidArgumentException::class);
});

it('bridges disk operations through legacy filesystem implementations', function () {
    $filesystem = createBridgeLocalFilesystem('bridge-ops');
    $disk = Storage::disk('craft-fs-bridge-ops');

    $disk->put('from-disk.txt', 'from disk');

    expect($filesystem->read('from-disk.txt'))->toBe('from disk');

    $filesystem->write('from-fs.txt', 'from fs');

    expect($disk->get('from-fs.txt'))->toBe('from fs');

    $disk->copy('from-disk.txt', 'copy.txt');
    $disk->move('copy.txt', 'moved.txt');

    expect($filesystem->fileExists('moved.txt'))->toBeTrue();
    expect($disk->files())->toContain('from-disk.txt');
    expect($disk->files())->toContain('from-fs.txt');
    expect($disk->files())->toContain('moved.txt');

    $disk->delete('from-disk.txt');

    expect($filesystem->fileExists('from-disk.txt'))->toBeFalse();
});

function createBridgeLocalFilesystem(string $handle): FsInterface
{
    $filesystem = \Craft::$app->getFs()->createFilesystem([
        'type' => 'craft\\fs\\Local',
        'name' => $handle,
        'handle' => $handle,
        'settings' => [
            'path' => sys_get_temp_dir()."/craft-disk-bridge/$handle",
        ],
    ]);

    expect(\Craft::$app->getFs()->saveFilesystem($filesystem, false))->toBeTrue();

    return $filesystem;
}
