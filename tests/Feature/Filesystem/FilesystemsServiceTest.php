<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\DiskRegistry;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Filesystem\Events\RegisterFilesystemTypes;
use CraftCms\Cms\Filesystem\Filesystems;
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

it('can register extra filesystem types through an event', function () {
    expect($this->service->getAllFilesystemTypes())
        ->toBeInstanceOf(Collection::class)
        ->not()->toContain(\craft\fs\Temp::class);

    Event::listen(RegisterFilesystemTypes::class, function (RegisterFilesystemTypes $event) {
        $event->types->add(\craft\fs\Temp::class);
    });

    expect($this->service->getAllFilesystemTypes())->toContain(\craft\fs\Temp::class);
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

    expect($filesystem)->toBeInstanceOf(\craft\fs\MissingFs::class)
        ->and($filesystem->expectedType)->toBe('some\\missing\\FilesystemType');
});

it('can save and fetch filesystems by handle through the new service', function () {
    $filesystem = createServiceLocalFilesystem($this->service, 'service-save');

    $fetched = $this->service->getFilesystemByHandle('service-save');

    expect($fetched)->toBeInstanceOf(FsInterface::class)
        ->and($fetched?->handle)->toBe('service-save')
        ->and($this->service->toDiskName('service-save'))->toBe('craft-fs-service-save')
        ->and(config('filesystems.disks.craft-fs-service-save.fsHandle'))->toBe('service-save');

    expect($this->service->removeFilesystem($filesystem))->toBeTrue();
});

it('dispatches a filesystem renamed event when renaming', function () {
    Event::fake([FilesystemRenamed::class]);

    $filesystem = createServiceLocalFilesystem($this->service, 'service-rename-old');
    $filesystem->oldHandle = 'service-rename-old';
    $filesystem->handle = 'service-rename-new';
    $filesystem->name = 'Service Rename New';

    expect($this->service->saveFilesystem($filesystem, false))->toBeTrue();

    Event::assertDispatched(fn (\CraftCms\Cms\Filesystem\Events\FilesystemRenamed $event) => $event->filesystem->handle === 'service-rename-new');
});

it('syncs stale craft disk registrations when handling delete config changes', function () {
    config()->set('filesystems.disks', [
        'manual-disk' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/fs-service/manual-disk'),
        ],
        DiskRegistry::PREFIX.'stale-handle' => [
            'driver' => 'craft-fs-bridge',
            'fsHandle' => 'stale-handle',
        ],
    ]);

    app(ProjectConfig::class)->set(ProjectConfig::PATH_FS, []);

    $this->service->handleDeletedFilesystem(new ItemRemoved(ProjectConfig::PATH_FS));

    expect(config('filesystems.disks.manual-disk'))->not()->toBeNull()
        ->and(config('filesystems.disks.'.DiskRegistry::PREFIX.'stale-handle'))->toBeNull();
});

function createServiceLocalFilesystem(Filesystems $service, string $handle): FsInterface
{
    $filesystem = $service->createFilesystem([
        'type' => 'craft\\fs\\Local',
        'name' => $handle,
        'handle' => $handle,
        'settings' => [
            'path' => sys_get_temp_dir()."/filesystems-service/$handle",
        ],
    ]);

    expect($service->saveFilesystem($filesystem, false))->toBeTrue();

    return $filesystem;
}
