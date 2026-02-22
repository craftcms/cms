<?php

declare(strict_types=1);

use craft\base\BaseFsInterface as LegacyBaseFsInterface;
use craft\base\Fs;
use craft\base\FsInterface;
use craft\base\LocalFsInterface;
use craft\errors\FsObjectNotFoundException;
use craft\events\FsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use craft\services\Fs as LegacyFsService;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Filesystem\Contracts\BaseFsInterface;
use CraftCms\Cms\Filesystem\Contracts\FsInterface as NewFsInterface;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\Support\Facades\Filesystems as FilesystemsFacade;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use yii\base\Event as YiiEvent;

uses(TestCase::class);

test('keeps legacy filesystem interfaces aliased while decoupling fs and operation contracts', function() {
    $localInterfaces = class_implements(Local::class);

    expect(is_a(FsInterface::class, NewFsInterface::class, true))->toBeTrue()
        ->and(interface_exists(BaseFsInterface::class))->toBeFalse()
        ->and(interface_exists(\CraftCms\Cms\Filesystem\Contracts\LocalFsInterface::class))->toBeFalse()
        ->and(is_a(NewFsInterface::class, LegacyBaseFsInterface::class, true))->toBeFalse()
        ->and(is_a(Fs::class, LegacyBaseFsInterface::class, true))->toBeTrue()
        ->and($localInterfaces)->not()->toHaveKey(FsInterface::class)
        ->and($localInterfaces)->not()->toHaveKey(LocalFsInterface::class)
        ->and(is_a(\craft\fs\Local::class, Local::class, true))->toBeTrue();
});

test('provides a default bridge disk config for plugin-style legacy filesystems', function() {
    $filesystem = FilesystemsFacade::createFilesystem([
        'type' => BridgePluginFs::class,
        'name' => 'Bridge Plugin',
        'handle' => 'bridge-plugin',
    ]);

    expect($filesystem->getDiskConfig())
        ->toHaveKey('driver', LegacyFsFlysystemAdapter::DISK_DRIVER)
        ->toHaveKey('fsHandle', 'bridge-plugin');

    registerBridgeFilesystem($filesystem);

    $disk = Storage::disk('craft-fs-bridge-plugin');
    expect($disk->put('plugin.txt', 'plugin'))->toBeTrue()
        ->and($disk->get('plugin.txt'))->toBe('plugin');
});

test('falls back to the legacy bridge adapter and records a deprecation when disk config is unavailable', function() {
    Deprecator::$logTarget = 'db';

    $filesystem = FilesystemsFacade::createFilesystem([
        'type' => BridgeFallbackLocalFs::class,
        'name' => 'Bridge Fallback',
        'handle' => 'bridge-fallback',
        'settings' => [
            'path' => sys_get_temp_dir() . '/craft-disk-bridge/bridge-fallback',
        ],
    ]);

    registerBridgeFilesystem($filesystem);

    $disk = Storage::disk('craft-fs-bridge-fallback');
    expect($disk->put('fallback.txt', 'fallback'))->toBeTrue()
        ->and($disk->get('fallback.txt'))->toBe('fallback');

    $expectedKey = sprintf('filesystem-bridge-fallback:%s', BridgeFallbackLocalFs::class);
    $logged = collect(app(Deprecator::class)->getRequestLogs())
        ->contains(fn($log) => $log->key === $expectedKey);

    expect($logged)->toBeTrue();
});

test('bridges register filesystem types and rename events to legacy listeners', function() {
    $registerHandler = function(RegisterComponentTypesEvent $event): void {
        $event->types[] = Temp::class;
    };
    YiiEvent::on(LegacyFsService::class, LegacyFsService::EVENT_REGISTER_FILESYSTEM_TYPES, $registerHandler);

    $renameCalls = 0;
    $renameHandles = [];
    $renameHandler = function(FsEvent $event) use (&$renameCalls, &$renameHandles): void {
        $renameCalls++;
        $renameHandles[] = $event->fs->handle;
    };
    YiiEvent::on(LegacyFsService::class, LegacyFsService::EVENT_RENAME_FILESYSTEM, $renameHandler);

    try {
        expect(app(Filesystems::class)->getAllFilesystemTypes())->toContain(Temp::class);

        $filesystem = FilesystemsFacade::createFilesystem([
            'type' => Local::class,
            'name' => 'Bridge Rename New',
            'handle' => 'bridge-rename-new',
            'settings' => [
                'path' => sys_get_temp_dir() . '/craft-disk-bridge/bridge-rename-new',
            ],
        ]);

        event(new FilesystemRenamed($filesystem));

        expect($renameCalls)->toBe(1)
            ->and($renameHandles)->toContain('bridge-rename-new');
    } finally {
        YiiEvent::off(LegacyFsService::class, LegacyFsService::EVENT_REGISTER_FILESYSTEM_TYPES, $registerHandler);
        YiiEvent::off(LegacyFsService::class, LegacyFsService::EVENT_RENAME_FILESYSTEM, $renameHandler);
    }
});

function registerBridgeFilesystem(NewFsInterface $filesystem): void
{
    $handle = $filesystem->handle;
    if (!is_string($handle) || $handle === '') {
        throw new RuntimeException('A filesystem handle is required.');
    }

    $service = app(Filesystems::class);
    $propertyName = property_exists($service, 'filesystems') ? 'filesystems' : '_filesystems';
    $property = new ReflectionProperty($service, $propertyName);
    $property->setAccessible(true);

    /** @var Collection<string,NewFsInterface>|null $filesystems */
    $filesystems = $property->getValue($service);
    $filesystems ??= collect();
    $filesystems->put($handle, $filesystem);
    $property->setValue($service, $filesystems);

    $diskName = FilesystemsFacade::toDiskName($handle);
    config()->set("filesystems.disks.$diskName", [
        'driver' => LegacyFsFlysystemAdapter::DISK_DRIVER,
        'fsHandle' => $handle,
    ]);
}

class BridgeFallbackLocalFs extends Local implements LegacyBaseFsInterface
{
    /**
     * @var array<string,string>
     */
    private static array $contents = [];

    #[Override]
    public function getDiskConfig(): array
    {
        throw new RuntimeException('No disk config available.');
    }

    public function write(string $path, string $contents, array $config = []): void
    {
        self::$contents[trim($path, '/')] = $contents;
    }

    public function read(string $path): string
    {
        $path = trim($path, '/');

        if (!array_key_exists($path, self::$contents)) {
            throw new FsObjectNotFoundException("Unable to read file at path: $path");
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

    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        $directory = self::normalizePath($directory);

        foreach (self::$contents as $path => $contents) {
            if ($directory !== '' && !str_starts_with("$path/", "$directory/") && $path !== $directory) {
                continue;
            }

            $dirname = pathinfo($path, PATHINFO_DIRNAME);
            if ($dirname === '.') {
                $dirname = '';
            }

            yield new FsListing([
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
        if (!is_resource($stream)) {
            throw new FilesystemException('Invalid stream.');
        }

        $contents = stream_get_contents($stream);
        if (!is_string($contents)) {
            throw new FilesystemException('Invalid stream contents.');
        }

        $this->write($path, $contents, $config);
    }

    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $oldPath = self::normalizePath($path);
        $newPath = self::normalizePath($newPath);
        if (!array_key_exists($oldPath, self::$contents)) {
            return;
        }

        self::$contents[$newPath] = self::$contents[$oldPath];
        unset(self::$contents[$oldPath]);
    }

    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $oldPath = self::normalizePath($path);
        $newPath = self::normalizePath($newPath);
        if (!array_key_exists($oldPath, self::$contents)) {
            return;
        }

        self::$contents[$newPath] = self::$contents[$oldPath];
    }

    public function getFileStream(string $uriPath)
    {
        $contents = $this->read($uriPath);
        $stream = fopen('php://temp', 'rb+');
        if (!is_resource($stream)) {
            throw new FilesystemException('Unable to open stream.');
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

        return array_any(array_keys(self::$contents), fn($filePath) => str_starts_with("$filePath/", "$path/"));
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
            if (!str_starts_with("$filePath/", "$path/")) {
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

class BridgePluginFs extends Fs implements LegacyBaseFsInterface
{
    /**
     * @var array<string,string>
     */
    private static array $contents = [];

    public function write(string $path, string $contents, array $config = []): void
    {
        self::$contents[self::normalizePath($path)] = $contents;
    }

    public function read(string $path): string
    {
        $path = self::normalizePath($path);
        if (!array_key_exists($path, self::$contents)) {
            throw new FsObjectNotFoundException("Unable to read file at path: $path");
        }

        return self::$contents[$path];
    }

    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        $directory = self::normalizePath($directory);

        foreach (self::$contents as $path => $contents) {
            if ($directory !== '' && !str_starts_with("$path/", "$directory/") && $path !== $directory) {
                continue;
            }

            $dirname = pathinfo($path, PATHINFO_DIRNAME);
            if ($dirname === '.') {
                $dirname = '';
            }

            yield new FsListing([
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
        if (!is_resource($stream)) {
            throw new FilesystemException('Invalid stream.');
        }

        $contents = stream_get_contents($stream);
        if (!is_string($contents)) {
            throw new FilesystemException('Invalid stream contents.');
        }

        $this->write($path, $contents, $config);
    }

    public function fileExists(string $path): bool
    {
        return array_key_exists(self::normalizePath($path), self::$contents);
    }

    public function deleteFile(string $path): void
    {
        unset(self::$contents[self::normalizePath($path)]);
    }

    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $oldPath = self::normalizePath($path);
        $newPath = self::normalizePath($newPath);
        if (!array_key_exists($oldPath, self::$contents)) {
            return;
        }

        self::$contents[$newPath] = self::$contents[$oldPath];
        unset(self::$contents[$oldPath]);
    }

    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $oldPath = self::normalizePath($path);
        $newPath = self::normalizePath($newPath);
        if (!array_key_exists($oldPath, self::$contents)) {
            return;
        }

        self::$contents[$newPath] = self::$contents[$oldPath];
    }

    public function getFileStream(string $uriPath)
    {
        $contents = $this->read($uriPath);
        $stream = fopen('php://temp', 'rb+');
        if (!is_resource($stream)) {
            throw new FilesystemException('Unable to open stream.');
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

        return array_any(array_keys(self::$contents), fn($filePath) => str_starts_with("$filePath/", "$path/"));
    }

    public function createDirectory(string $path, array $config = []): void
    {
        // Directory operations are virtual in this in-memory plugin filesystem.
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
            if (!str_starts_with("$filePath/", "$path/")) {
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
