<?php

declare(strict_types=1);

use craft\base\BaseFsInterface as LegacyBaseFsInterface;
use craft\base\FsInterface;
use craft\events\FsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use craft\fs\Temp;
use craft\services\Fs as LegacyFsService;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use CraftCms\Cms\Filesystem\Contracts\FsInterface as NewFsInterface;
use CraftCms\Cms\Filesystem\Contracts\LocalFsInterface as NewLocalFsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use Illuminate\Support\Facades\Storage;
use yii\base\Event as YiiEvent;

it('registers Craft filesystems as Laravel disks and exposes helper accessors', function () {
    createBridgeLocalFilesystem('bridge-helper');

    expect(config('filesystems.disks.craft-fs-bridge-helper.driver'))->toBe(LegacyFsFlysystemAdapter::DISK_DRIVER)
        ->and(config('filesystems.disks.craft-fs-bridge-helper.fsHandle'))->toBe('bridge-helper')
        ->and(\Craft::$app->getFs()->toDiskName('bridge-helper'))->toBe('craft-fs-bridge-helper');

    $disk = \Craft::$app->getFs()->disk('bridge-helper');
    expect($disk->put('helper.txt', 'helper'))->toBeTrue();

    expect(Storage::disk('craft-fs-bridge-helper')->get('helper.txt'))->toBe('helper');
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

it('bridges disk operations through Craft handle disks', function () {
    createBridgeLocalFilesystem('bridge-ops');
    $disk = \Craft::$app->getFs()->disk('bridge-ops');
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

it('keeps legacy filesystem interfaces aliased while decoupling fs and operation contracts', function () {
    expect(is_a(\craft\base\FsInterface::class, NewFsInterface::class, true))->toBeTrue()
        ->and(is_a(\craft\base\LocalFsInterface::class, NewLocalFsInterface::class, true))->toBeTrue()
        ->and(interface_exists(\CraftCms\Cms\Filesystem\Contracts\BaseFsInterface::class))->toBeFalse()
        ->and(is_a(NewFsInterface::class, LegacyBaseFsInterface::class, true))->toBeFalse()
        ->and(is_a(\craft\base\Fs::class, LegacyBaseFsInterface::class, true))->toBeTrue();
});

it('provides Laravel disk configuration for built-in filesystem classes', function () {
    /** @var \craft\fs\Local $localFilesystem */
    $localFilesystem = createBridgeLocalFilesystem('bridge-config-local');

    expect($localFilesystem->getDiskConfig())
        ->toHaveKey('driver', 'local')
        ->toHaveKey('root', $localFilesystem->getRootPath());

    $laravelDiskFs = new \craft\fs\LaravelDiskFs([
        'disk' => \Craft::$app->getFs()->toDiskName('bridge-config-local'),
    ]);

    expect($laravelDiskFs->getDiskConfig())
        ->toHaveKey('driver', LegacyFsFlysystemAdapter::DISK_DRIVER)
        ->toHaveKey('fsHandle', 'bridge-config-local');
});

it('falls back to the legacy bridge adapter and records a deprecation when disk config is unavailable', function () {
    Deprecator::$logTarget = 'db';
    DeprecationError::query()->delete();

    $filesystem = \Craft::$app->getFs()->createFilesystem([
        'type' => BridgeFallbackLocalFs::class,
        'name' => 'Bridge Fallback',
        'handle' => 'bridge-fallback',
        'settings' => [
            'path' => sys_get_temp_dir().'/craft-disk-bridge/bridge-fallback',
        ],
    ]);

    expect(\Craft::$app->getFs()->saveFilesystem($filesystem, false))->toBeTrue();

    $disk = Storage::disk('craft-fs-bridge-fallback');
    expect($disk->put('fallback.txt', 'fallback'))->toBeTrue()
        ->and($disk->get('fallback.txt'))->toBe('fallback');

    app(Deprecator::class)->storeLogs();

    expect(DeprecationError::query()
        ->where('key', sprintf('filesystem-bridge-fallback:%s', BridgeFallbackLocalFs::class))
        ->exists())->toBeTrue();
});

it('bridges register filesystem types and rename events to legacy listeners', function () {
    $registerHandler = function (RegisterComponentTypesEvent $event): void {
        $event->types[] = Temp::class;
    };
    YiiEvent::on(LegacyFsService::class, LegacyFsService::EVENT_REGISTER_FILESYSTEM_TYPES, $registerHandler);

    $renameCalls = 0;
    $renameHandles = [];
    $renameHandler = function (FsEvent $event) use (&$renameCalls, &$renameHandles): void {
        $renameCalls++;
        $renameHandles[] = $event->fs->handle;
    };
    YiiEvent::on(LegacyFsService::class, LegacyFsService::EVENT_RENAME_FILESYSTEM, $renameHandler);

    try {
        expect(app(Filesystems::class)->getAllFilesystemTypes())->toContain(Temp::class);

        $filesystem = createBridgeLocalFilesystem('bridge-rename-old');
        $filesystem->oldHandle = 'bridge-rename-old';
        $filesystem->handle = 'bridge-rename-new';
        $filesystem->name = 'Bridge Rename New';

        expect(app(Filesystems::class)->saveFilesystem($filesystem, false))->toBeTrue()
            ->and($renameCalls)->toBe(1)
            ->and($renameHandles)->toContain('bridge-rename-new');
    } finally {
        YiiEvent::off(LegacyFsService::class, LegacyFsService::EVENT_REGISTER_FILESYSTEM_TYPES, $registerHandler);
        YiiEvent::off(LegacyFsService::class, LegacyFsService::EVENT_RENAME_FILESYSTEM, $renameHandler);
    }
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

class BridgeFallbackLocalFs extends \craft\fs\Local
{
    /**
     * @var array<string,string>
     */
    private static array $contents = [];

    #[\Override]
    public function getDiskConfig(): array
    {
        throw new \RuntimeException('No disk config available.');
    }

    #[\Override]
    public function write(string $path, string $contents, array $config = []): void
    {
        self::$contents[trim($path, '/')] = $contents;
    }

    #[\Override]
    public function read(string $path): string
    {
        $path = trim($path, '/');

        if (! array_key_exists($path, self::$contents)) {
            throw new \craft\errors\FsObjectNotFoundException("Unable to read file at path: $path");
        }

        return self::$contents[$path];
    }

    #[\Override]
    public function fileExists(string $path): bool
    {
        return array_key_exists(trim($path, '/'), self::$contents);
    }

    #[\Override]
    public function deleteFile(string $path): void
    {
        unset(self::$contents[trim($path, '/')]);
    }
}
