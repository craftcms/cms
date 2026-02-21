<?php

declare(strict_types=1);

use craft\base\BaseFsInterface as LegacyBaseFsInterface;
use craft\events\FsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use craft\services\Fs as LegacyFsService;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use CraftCms\Cms\Filesystem\Contracts\FsInterface as NewFsInterface;
use CraftCms\Cms\Filesystem\Contracts\LocalFsInterface as NewLocalFsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Temp;
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
    $localInterfaces = class_implements(\CraftCms\Cms\Filesystem\Local::class);

    expect(is_a(\craft\base\FsInterface::class, NewFsInterface::class, true))->toBeTrue()
        ->and(is_a(\craft\base\LocalFsInterface::class, NewLocalFsInterface::class, true))->toBeTrue()
        ->and(interface_exists(\CraftCms\Cms\Filesystem\Contracts\BaseFsInterface::class))->toBeFalse()
        ->and(is_a(NewFsInterface::class, LegacyBaseFsInterface::class, true))->toBeFalse()
        ->and(is_a(\craft\base\Fs::class, LegacyBaseFsInterface::class, true))->toBeTrue()
        ->and($localInterfaces)->toHaveKey(NewLocalFsInterface::class)
        ->and($localInterfaces)->not()->toHaveKey(\craft\base\FsInterface::class)
        ->and($localInterfaces)->not()->toHaveKey(\craft\base\LocalFsInterface::class)
        ->and(is_a(\craft\fs\Local::class, \CraftCms\Cms\Filesystem\Local::class, true))->toBeTrue();
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

function createBridgeLocalFilesystem(string $handle): NewFsInterface
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

class BridgeFallbackLocalFs extends \craft\fs\Local implements LegacyBaseFsInterface
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

    public function write(string $path, string $contents, array $config = []): void
    {
        self::$contents[trim($path, '/')] = $contents;
    }

    public function read(string $path): string
    {
        $path = trim($path, '/');

        if (! array_key_exists($path, self::$contents)) {
            throw new \craft\errors\FsObjectNotFoundException("Unable to read file at path: $path");
        }

        return self::$contents[$path];
    }

    public function fileExists(string $path): bool
    {
        return array_key_exists(trim($path, '/'), self::$contents);
    }

    public function deleteFile(string $path): void
    {
        unset(self::$contents[self::normalizePath($path)]);
    }

    public function getFileList(string $directory = '', bool $recursive = true): \Generator
    {
        $directory = self::normalizePath($directory);

        foreach (self::$contents as $path => $contents) {
            if ($directory !== '' && ! str_starts_with("$path/", "$directory/") && $path !== $directory) {
                continue;
            }

            $dirname = pathinfo($path, PATHINFO_DIRNAME);
            if ($dirname === '.') {
                $dirname = '';
            }

            yield new \craft\models\FsListing([
                'dirname' => $dirname,
                'basename' => pathinfo($path, PATHINFO_BASENAME),
                'type' => 'file',
                'dateModified' => time(),
                'fileSize' => strlen($contents),
            ]);
        }
    }

    public function getFileSize(string $uri): int
    {
        return strlen($this->read($uri));
    }

    public function getDateModified(string $uri): int
    {
        $this->read($uri);

        return time();
    }

    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        if (! is_resource($stream)) {
            throw new \craft\errors\FsException('Invalid stream.');
        }

        $contents = stream_get_contents($stream);
        if (! is_string($contents)) {
            throw new \craft\errors\FsException('Invalid stream contents.');
        }

        $this->write($path, $contents, $config);
    }

    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $oldPath = self::normalizePath($path);
        $newPath = self::normalizePath($newPath);
        if (! array_key_exists($oldPath, self::$contents)) {
            return;
        }

        self::$contents[$newPath] = self::$contents[$oldPath];
        unset(self::$contents[$oldPath]);
    }

    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $oldPath = self::normalizePath($path);
        $newPath = self::normalizePath($newPath);
        if (! array_key_exists($oldPath, self::$contents)) {
            return;
        }

        self::$contents[$newPath] = self::$contents[$oldPath];
    }

    public function getFileStream(string $uriPath)
    {
        $contents = $this->read($uriPath);
        $stream = fopen('php://temp', 'rb+');
        if (! is_resource($stream)) {
            throw new \craft\errors\FsException('Unable to open stream.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    public function directoryExists(string $path): bool
    {
        $path = self::normalizePath($path);
        if ($path === '') {
            return true;
        }

        return array_any(array_keys(self::$contents), fn ($filePath) => str_starts_with("$filePath/", "$path/"));
    }

    public function createDirectory(string $path, array $config = []): void
    {
        // Directory operations are virtual in this in-memory fallback.
    }

    public function deleteDirectory(string $path): void
    {
        $path = self::normalizePath($path);
        foreach (array_keys(self::$contents) as $filePath) {
            if ($path === '' || str_starts_with("$filePath/", "$path/")) {
                unset(self::$contents[$filePath]);
            }
        }
    }

    public function renameDirectory(string $path, string $newName): void
    {
        $path = self::normalizePath($path);
        $newName = self::normalizePath($newName);
        if ($path === '' || $newName === '') {
            return;
        }

        foreach (array_keys(self::$contents) as $filePath) {
            if (! str_starts_with("$filePath/", "$path/")) {
                continue;
            }

            $suffix = substr($filePath, strlen($path));
            $renamedPath = trim("$newName/$suffix", '/');
            self::$contents[$renamedPath] = self::$contents[$filePath];
            unset(self::$contents[$filePath]);
        }
    }

    private static function normalizePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}
