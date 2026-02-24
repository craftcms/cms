<?php

declare(strict_types=1);

use craft\base\Fs;
use craft\fs\Local;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Filesystem\Events\RegisterFilesystemTypes;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Filesystems as FilesystemsFacade;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(Filesystems::class);
});

it('is a singleton and is available via the facade', function () {
    expect($this->service)->toBe(app(Filesystems::class))
        ->and($this->service)->toBe(FilesystemsFacade::getFacadeRoot());
});

it('keeps the legacy base Fs class as a subclass of the new filesystem base', function () {
    expect(is_subclass_of(Fs::class, Filesystem::class))->toBeTrue();
});

it('can register extra filesystem types through an event', function () {
    expect($this->service->getAllFilesystemTypes())
        ->toBeInstanceOf(Collection::class)
        ->not()->toContain(Temp::class);

    Event::listen(RegisterFilesystemTypes::class, function (RegisterFilesystemTypes $event) {
        $event->types->add(Temp::class);
    });

    expect($this->service->getAllFilesystemTypes())->toContain(Temp::class);
});

it('returns filesystems as a collection', function () {
    createServiceLocalFilesystem($this->service, 'service-collection');

    expect($this->service->getAllFilesystems())
        ->toBeInstanceOf(Collection::class)
        ->contains(fn (FsInterface $fs) => $fs->handle === 'service-collection')
        ->toBeTrue();
});

it('creates a missing filesystem if type is not recognized', function () {
    $filesystem = $this->service->createFilesystem([
        'type' => 'some\\missing\\FilesystemType',
    ]);

    expect($filesystem)->toBeInstanceOf(MissingFs::class)
        ->and($filesystem->expectedType)->toBe('some\\missing\\FilesystemType');
});

it('can save and fetch filesystems by handle through the new service', function () {
    $filesystem = createServiceLocalFilesystem($this->service, 'service-save');

    $fetched = $this->service->getFilesystemByHandle('service-save');

    expect($fetched)->toBeInstanceOf(FsInterface::class)
        ->and($fetched?->handle)->toBe('service-save')
        ->and($this->service->toDiskName('service-save'))->toBe('craft-fs-service-save')
        ->and(config('filesystems.disks.craft-fs-service-save.driver'))->toBe('local');

    expect($this->service->removeFilesystem($filesystem))->toBeTrue();
});

it('dispatches a filesystem renamed event when renaming', function () {
    Event::fake([FilesystemRenamed::class]);

    $filesystem = createServiceLocalFilesystem($this->service, 'service-rename-old');
    $filesystem->oldHandle = 'service-rename-old';
    $filesystem->handle = 'service-rename-new';
    $filesystem->name = 'Service Rename New';

    expect($this->service->saveFilesystem($filesystem, false))->toBeTrue();

    Event::assertDispatched(fn (FilesystemRenamed $event) => $event->filesystem->handle === 'service-rename-new');
});

it('validates local filesystems with laravel path requirements', function () {
    $filesystem = FilesystemsFacade::createFilesystem([
        'type' => Local::class,
        'name' => 'Missing Path',
        'handle' => 'missingPath',
    ]);

    expect($filesystem->validate())->toBeFalse()
        ->and($filesystem->errors()->get('path'))->not()->toBeEmpty();
});

it('rejects local filesystems inside system directories', function () {
    $filesystem = FilesystemsFacade::createFilesystem([
        'type' => Local::class,
        'name' => 'System Path',
        'handle' => 'systemPath',
        'settings' => [
            'path' => '/',
        ],
    ]);

    expect($filesystem->validate())->toBeFalse()
        ->and($filesystem->errors()->get('path'))->not()->toBeEmpty();
});

it('applies configured default visibility modes during construction', function () {
    $generalConfig = Cms::config();
    $previousFileMode = $generalConfig->defaultFileMode;
    $previousDirMode = $generalConfig->defaultDirMode;
    $generalConfig->defaultFileMode = 0600;
    $generalConfig->defaultDirMode = 0700;

    try {
        $filesystem = new Local([
            'name' => 'Visibility Modes',
            'handle' => 'visibilityModes',
            'path' => sys_get_temp_dir().'/visibility-modes-fs',
        ]);

        $diskConfig = $filesystem->getDiskConfig();

        expect($diskConfig)
            ->toHaveKey('visibility', 'private')
            ->toHaveKey('directory_visibility', 'private')
            ->and($diskConfig['permissions']['file']['private'])->toBe(0600)
            ->and($diskConfig['permissions']['dir']['private'])->toBe(0700);
    } finally {
        $generalConfig->defaultFileMode = $previousFileMode;
        $generalConfig->defaultDirMode = $previousDirMode;
    }
});

it('resolves a disk: prefixed handle to a DiskFilesystem', function () {
    config()->set('filesystems.disks.resolve-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/fs-service/resolve-disk'),
    ]);

    $fs = $this->service->resolve('disk:resolve-disk');

    expect($fs)->toBeInstanceOf(\CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem::class)
        ->and($fs->disk)->toBe('resolve-disk');
});

it('returns null for a disk: prefix with an empty disk name', function () {
    expect($this->service->resolve('disk:'))->toBeNull();
});

it('returns null for a disk: prefix with a non-existent disk', function () {
    expect($this->service->resolve('disk:does-not-exist'))->toBeNull();
});

it('resolves a Craft filesystem handle through resolve()', function () {
    createServiceLocalFilesystem($this->service, 'resolve-craft-handle');

    $fs = $this->service->resolve('resolve-craft-handle');

    expect($fs)->toBeInstanceOf(FsInterface::class)
        ->and($fs)->not->toBeInstanceOf(\CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem::class)
        ->and($fs->handle)->toBe('resolve-craft-handle');
});

it('falls back to a plain Laravel disk name when no Craft filesystem matches', function () {
    config()->set('filesystems.disks.plain-laravel-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/fs-service/plain-laravel-disk'),
    ]);

    $fs = $this->service->resolve('plain-laravel-disk');

    expect($fs)->toBeInstanceOf(\CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem::class)
        ->and($fs->disk)->toBe('plain-laravel-disk');
});

it('returns null when nothing matches in resolve()', function () {
    expect($this->service->resolve('completely-unknown'))->toBeNull();
});

it('resolves disk: prefix to the raw disk name', function () {
    config()->set('filesystems.disks.rdisk-name', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/fs-service/rdisk-name'),
    ]);

    expect($this->service->resolveDiskName('disk:rdisk-name'))->toBe('rdisk-name');
});

it('returns null for disk: prefix with empty or missing disk', function () {
    expect($this->service->resolveDiskName('disk:'))->toBeNull()
        ->and($this->service->resolveDiskName('disk:nonexistent'))->toBeNull();
});

it('resolves a Craft filesystem handle to its craft-fs- prefixed disk name', function () {
    createServiceLocalFilesystem($this->service, 'resolve-disk-name-craft');

    expect($this->service->resolveDiskName('resolve-disk-name-craft'))->toBe('craft-fs-resolve-disk-name-craft');
});

it('falls back to plain disk name when no Craft filesystem matches in resolveDiskName', function () {
    config()->set('filesystems.disks.plain-disk-fallback', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/fs-service/plain-disk-fallback'),
    ]);

    expect($this->service->resolveDiskName('plain-disk-fallback'))->toBe('plain-disk-fallback');
});

it('returns null when nothing matches in resolveDiskName()', function () {
    expect($this->service->resolveDiskName('totally-unknown'))->toBeNull();
});

it('registers a single disk when handleChangedFilesystem receives a specific handle', function () {
    $path = sys_get_temp_dir().'/craft-single-register';

    $newValue = [
        'name' => 'Single Register',
        'type' => \CraftCms\Cms\Filesystem\Filesystems\Local::class,
        'hasUrls' => true,
        'url' => 'https://cdn.example.test/single/',
        'settings' => [
            'path' => $path,
        ],
    ];

    app(ProjectConfig::class)->set('fs.single-register', $newValue);

    $event = new \CraftCms\Cms\ProjectConfig\Events\ItemUpdated(
        path: 'fs.single-register',
        newValue: $newValue,
        tokenMatches: ['single-register'],
    );

    $this->service->handleChangedFilesystem($event);

    $diskConfig = config('filesystems.disks.craft-fs-single-register');

    expect($diskConfig)->not->toBeNull()
        ->and($diskConfig['driver'])->toBe('local')
        ->and($diskConfig['url'])->toBe('https://cdn.example.test/single');
});

it('propagates URL from filesystem config during registerDisk', function () {
    createServiceLocalFilesystem($this->service, 'url-prop', hasUrls: true, url: 'https://cdn.example.test/url-prop/');

    $this->service->registerDisk('url-prop');

    $diskConfig = config('filesystems.disks.craft-fs-url-prop');

    expect($diskConfig)->not->toBeNull()
        ->and($diskConfig['driver'])->toBe('local')
        ->and($diskConfig['url'])->toBe('https://cdn.example.test/url-prop');
});

it('omits URL when filesystem config has no hasUrls flag', function () {
    createServiceLocalFilesystem($this->service, 'no-url');

    $this->service->registerDisk('no-url');

    $diskConfig = config('filesystems.disks.craft-fs-no-url');

    expect($diskConfig)->not->toBeNull()
        ->and($diskConfig['driver'])->toBe('local')
        ->and($diskConfig)->not->toHaveKey('url');
});

it('omits URL when hasUrls is true but url is empty', function () {
    createServiceLocalFilesystem($this->service, 'empty-url', hasUrls: true, url: '');

    $this->service->registerDisk('empty-url');

    $diskConfig = config('filesystems.disks.craft-fs-empty-url');

    expect($diskConfig)->not->toBeNull()
        ->and($diskConfig['driver'])->toBe('local')
        ->and($diskConfig)->not->toHaveKey('url');
});

it('skips disk registration for missing filesystem types', function () {
    app(ProjectConfig::class)->set('fs.missing-type', [
        'name' => 'Missing Type',
        'type' => 'some\\nonexistent\\FsClass',
        'settings' => [],
    ]);

    $this->service->handleChangedFilesystem();

    expect(config('filesystems.disks.craft-fs-missing-type'))->toBeNull();
});

it('syncs stale craft disk registrations when handling delete config changes', function () {
    config()->set('filesystems.disks', [
        'manual-disk' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/fs-service/manual-disk'),
        ],
        Filesystems::DISK_PREFIX.'stale-handle' => [
            'driver' => 'craft-fs-bridge',
            'fsHandle' => 'stale-handle',
        ],
    ]);

    app(ProjectConfig::class)->set(ProjectConfig::PATH_FS, []);

    $this->service->handleDeletedFilesystem(new ItemRemoved(ProjectConfig::PATH_FS));

    expect(config('filesystems.disks.manual-disk'))->not()->toBeNull()
        ->and(config('filesystems.disks.'.Filesystems::DISK_PREFIX.'stale-handle'))->toBeNull();
});

function createServiceLocalFilesystem(
    Filesystems $service,
    string $handle,
    bool $hasUrls = false,
    string $url = '',
): FsInterface {
    $config = [
        'type' => 'craft\\fs\\Local',
        'name' => $handle,
        'handle' => $handle,
        'settings' => [
            'path' => sys_get_temp_dir()."/filesystems-service/$handle",
        ],
    ];

    if ($hasUrls) {
        $config['hasUrls'] = true;
    }

    if ($url !== '') {
        $config['url'] = $url;
    }

    $filesystem = $service->createFilesystem($config);

    expect($service->saveFilesystem($filesystem, false))->toBeTrue();

    return $filesystem;
}
