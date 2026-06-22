<?php

declare(strict_types=1);

use craft\base\BaseFsInterface;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Yii2Adapter\Filesystem\FilesystemCompatibility;
use Illuminate\Support\Facades\Storage;

it('resolves legacy bridge disks after Laravel rebinds the driver creator', function() {
    $filesystem = new LegacyFilesystemCompatibilityTestFs([
        'name' => 'Legacy Compatibility',
        'handle' => 'legacy-compatibility',
    ]);

    app()->instance(FilesystemsService::class, new class($filesystem) extends FilesystemsService {
        public function __construct(
            private readonly FsInterface $filesystem,
        ) {
        }

        public function getFilesystemByHandle(string $handle): ?FsInterface
        {
            return $handle === $this->filesystem->handle ? $this->filesystem : null;
        }
    });

    new FilesystemCompatibility()->register(app());

    config()->set('filesystems.disks.legacy-compatibility', [
        'driver' => 'craft-fs-bridge',
        'fsHandle' => 'legacy-compatibility',
    ]);

    $disk = Storage::disk('legacy-compatibility');

    expect($disk->put('legacy.txt', 'legacy'))->toBeTrue()
        ->and($disk->get('legacy.txt'))->toBe('legacy');
});

class LegacyFilesystemCompatibilityTestFs extends Filesystem implements BaseFsInterface
{
    private array $files = [];

    public function getDiskConfig(): array
    {
        return [
            'driver' => LegacyFsFlysystemAdapter::DISK_DRIVER,
            'fsHandle' => $this->handle,
        ];
    }

    public function getFileList(string $directory = '', bool $recursive = true): \Generator
    {
        foreach ($this->files as $path => $contents) {
            yield new FsListing([
                'dirname' => dirname($path) === '.' ? '' : dirname($path),
                'basename' => basename($path),
                'type' => 'file',
                'fileSize' => strlen($contents),
                'dateModified' => time(),
            ]);
        }
    }

    public function getFileSize(string $uri): int
    {
        return strlen($this->files[$uri]);
    }

    public function getDateModified(string $uri): int
    {
        return time();
    }

    public function write(string $path, string $contents, array $config = []): void
    {
        $this->files[$path] = $contents;
    }

    public function read(string $path): string
    {
        return $this->files[$path];
    }

    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->files[$path] = stream_get_contents($stream);
    }

    public function fileExists(string $path): bool
    {
        return array_key_exists($path, $this->files);
    }

    public function deleteFile(string $path): void
    {
        unset($this->files[$path]);
    }

    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $this->files[$newPath] = $this->files[$path];
        unset($this->files[$path]);
    }

    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $this->files[$newPath] = $this->files[$path];
    }

    public function getFileStream(string $uriPath)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $this->files[$uriPath]);
        rewind($stream);

        return $stream;
    }

    public function directoryExists(string $path): bool
    {
        return true;
    }

    public function createDirectory(string $path, array $config = []): void
    {
    }

    public function deleteDirectory(string $path): void
    {
    }

    public function renameDirectory(string $path, string $newName): void
    {
    }
}
