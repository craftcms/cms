<?php

declare(strict_types=1);

use craft\base\Fs;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\Filesystem\FilesystemCompatibility;
use CraftCms\Yii2Adapter\Form\Controls\LegacyHtmlControl;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToListContents;

it('resolves legacy bridge disks after Laravel rebinds the driver creator', function() {
    $filesystem = new LegacyFilesystemCompatibilityTestFs([
        'name' => 'Legacy Compatibility',
        'handle' => 'legacy-compatibility',
    ]);

    $filesystem->register();

    $disk = Storage::disk('legacy-compatibility');

    expect($disk->put('legacy.txt', 'legacy'))->toBeTrue()
        ->and($disk->get('legacy.txt'))->toBe('legacy');
});

it('generates permanent URLs with the scoped prefix once', function() {
    $filesystem = new LegacyFilesystemCompatibilityTestFs([
        'name' => 'Legacy Compatibility',
        'handle' => 'legacy-compatibility',
        'hasUrls' => true,
        'url' => 'https://assets.example.test/root/',
    ]);
    $filesystem->register();

    $disk = Storage::build([
        'driver' => 'scoped',
        'disk' => 'legacy-compatibility',
        'prefix' => 'volume',
    ]);

    expect($disk->url('images/photo.jpg'))
        ->toBe('https://assets.example.test/root/volume/images/photo.jpg');
});

it('fails when legacy listings contain invalid values', function() {
    $filesystem = new LegacyFilesystemCompatibilityTestFs([
        'name' => 'Legacy Compatibility',
        'handle' => 'legacy-compatibility',
    ]);
    $filesystem->listingValues = ['invalid'];
    $filesystem->register();

    expect(fn() => Storage::disk('legacy-compatibility')->listContents('', true)->toArray())
        ->toThrow(UnableToListContents::class);
});

it('logs one actionable deprecation per concrete legacy filesystem class', function() {
    $filesystem = new LegacyFilesystemCompatibilityTestFs([
        'name' => 'Legacy Compatibility',
        'handle' => 'legacy-compatibility',
    ]);
    $filesystem->register();
    config()->set('filesystems.disks.legacy-compatibility-copy', [
        'driver' => 'craft-fs-bridge',
        'fsHandle' => 'legacy-compatibility',
    ]);

    Storage::disk('legacy-compatibility');
    Storage::disk('legacy-compatibility-copy');

    $logs = collect(Deprecator::getRequestLogs())
        ->where('key', 'filesystem-bridge:' . LegacyFilesystemCompatibilityTestFs::class)
        ->values();

    expect($logs)->toHaveCount(1)
        ->and($logs[0]->message)
        ->toContain('getDiskConfig()');
});

it('combines base URL fields with legacy settings HTML', function() {
    $filesystem = new LegacyFilesystemCompatibilityTestFs([
        'hasUrls' => true,
        'url' => 'https://assets.example.test/root/',
    ]);
    $context = new FormContext(namespace: 'settings');

    $payload = app(FormResolver::class)->resolve($filesystem->settingsForm($context), $context);

    expect(array_map(
        fn($node): string => implode('.', array_slice($node->control->path, 1)),
        $payload->nodes,
    ))->toBe(['hasUrls', 'url', '__legacySettings'])
        ->and(array_map(fn($node): string => $node->control->type, $payload->nodes))->toBe([
            Lightswitch::class,
            Text::class,
            LegacyHtmlControl::class,
        ])
        ->and($filesystem->getSettings()['hasUrls'])->toBeTrue()
        ->and($filesystem->getSettings()['url'])->toBe('https://assets.example.test/root/');
});

it('preserves legacy URL setting flags', function() {
    $filesystem = new LegacyFilesystemWithoutUrlSettingsTestFs();
    $context = new FormContext(namespace: 'settings');
    $payload = app(FormResolver::class)->resolve($filesystem->settingsForm($context), $context);

    expect($filesystem->getShowHasUrlSetting())->toBeFalse()
        ->and($filesystem->getShowUrlSetting())->toBeFalse()
        ->and(array_map(
            fn($node): string => implode('.', array_slice($node->control->path, 1)),
            $payload->nodes,
        ))->toBe(['__legacySettings']);
});

class LegacyFilesystemCompatibilityTestFs extends Fs
{
    private array $files = [];

    public array $listingValues = [];

    public function getSettingsHtml(): ?string
    {
        return '<input name="legacySetting" value="legacy">';
    }

    public function register(): void
    {
        app()->instance(FilesystemsService::class, new class($this) extends FilesystemsService {
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

        config()->set('filesystems.disks.' . $this->handle, [
            'driver' => 'craft-fs-bridge',
            'fsHandle' => $this->handle,
        ]);
    }

    public function getDiskConfig(): array
    {
        return [
            'driver' => LegacyFsFlysystemAdapter::DISK_DRIVER,
            'fsHandle' => $this->handle,
        ];
    }

    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        foreach ($this->listingValues as $value) {
            yield $value;
        }

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

class LegacyFilesystemWithoutUrlSettingsTestFs extends LegacyFilesystemCompatibilityTestFs
{
    protected static bool $showHasUrlSetting = false;

    protected static bool $showUrlSetting = false;
}
