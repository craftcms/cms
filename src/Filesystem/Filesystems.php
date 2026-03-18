<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Filesystem\Events\RegisterFilesystemTypes;
use CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Filesystem as LaravelFilesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use yii\base\InvalidConfigException;

#[Singleton]
class Filesystems
{
    public const string DISK_PREFIX = 'craft-fs-';

    /**
     * Internal disk names reserved by Craft that should not be exposed as user-selectable filesystems.
     */
    public const array INTERNAL_DISK_NAMES = ['craft-tmp', 'rebrand'];

    /**
     * @var Collection<string,FsInterface>|null
     */
    private ?Collection $filesystems = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly ConfigRepository $config,
        private readonly FilesystemManager $filesystemManager,
    ) {}

    public function createFilesystemConfig(FsInterface $fs): array
    {
        $config = [
            'name' => $fs->name,
            'type' => $fs::class,
            'settings' => ProjectConfigHelper::packAssociativeArrays($fs->getSettings()),
        ];

        if ($fs->getShowHasUrlSetting()) {
            $config['hasUrls'] = $fs->hasUrls;
        }

        if ($fs->getShowUrlSetting()) {
            $config['url'] = $fs->url;
        }

        return $config;
    }

    /**
     * Returns all registered filesystem types.
     *
     * @return Collection<int,class-string<FsInterface>>
     */
    public function getAllFilesystemTypes(): Collection
    {
        $event = new RegisterFilesystemTypes(Collection::make([
            Local::class,
        ]));

        event($event);

        return $event->types->values();
    }

    /**
     * @return Collection<string,FsInterface>
     */
    private function filesystems(): Collection
    {
        if (isset($this->filesystems)) {
            return $this->filesystems;
        }

        $filesystems = collect($this->projectConfig->get(ProjectConfig::PATH_FS) ?? [])
            ->mapWithKeys(function (array $config, string $handle): array {
                $config['handle'] = $handle;
                $config['settings'] = ProjectConfigHelper::unpackAssociativeArrays($config['settings'] ?? []);

                return [$handle => $this->createFilesystem($config)];
            });

        return $this->filesystems = $filesystems;
    }

    /**
     * @return Collection<int,FsInterface>
     */
    public function getAllFilesystems(): Collection
    {
        return $this->filesystems()->values();
    }

    public function getFilesystemByHandle(string $handle): ?FsInterface
    {
        return $this->filesystems()->get($handle);
    }

    public function toDiskName(string $handle): string
    {
        return self::DISK_PREFIX.$handle;
    }

    public function disk(string $handle): LaravelFilesystem
    {
        return Storage::disk($this->toDiskName($handle));
    }

    /**
     * Synchronizes all Craft filesystems as Laravel disks from Project Config.
     */
    public function syncDisks(): void
    {
        $currentDisks = $this->currentDiskConfigs();
        $craftDisks = $this->craftDisksFromProjectConfig();

        $manualDisks = array_filter($currentDisks, fn (mixed $v, string $k) => ! str_starts_with($k, self::DISK_PREFIX), ARRAY_FILTER_USE_BOTH);

        $this->config->set('filesystems.disks', [
            ...$manualDisks,
            ...$craftDisks,
        ]);

        $staleDiskNames = array_keys(array_filter($currentDisks, fn (mixed $v, string $k) => str_starts_with($k, self::DISK_PREFIX), ARRAY_FILTER_USE_BOTH));

        $this->forgetDisks([
            ...$staleDiskNames,
            ...array_keys($craftDisks),
        ]);
    }

    /**
     * Registers a single Craft filesystem as a Laravel disk.
     *
     * @param  array<string, mixed>|null  $filesystemConfig  Optional filesystem config to use directly,
     *                                                       bypassing the ProjectConfig lookup. This is needed when
     *                                                       registering a disk during a ProjectConfig change event,
     *                                                       since the config value hasn't been committed yet.
     */
    public function registerDisk(string $handle, ?array $filesystemConfig = null): void
    {
        $diskName = $this->toDiskName($handle);
        $diskConfig = $filesystemConfig !== null
            ? $this->resolveDiskConfigFromArray($handle, $filesystemConfig)
            : $this->resolveDiskConfig($handle);

        if ($diskConfig === null) {
            return;
        }

        $diskConfigs = $this->currentDiskConfigs();
        $diskConfigs[$diskName] = $diskConfig;
        $this->config->set('filesystems.disks', $diskConfigs);
        $this->filesystemManager->forgetDisk($diskName);
    }

    /**
     * Removes a Craft disk from the Laravel disk configuration.
     */
    public function purgeDisk(string $handle): void
    {
        $diskName = $this->toDiskName($handle);
        $diskConfigs = $this->config->get('filesystems.disks', []);

        if (! is_array($diskConfigs) || ! array_key_exists($diskName, $diskConfigs)) {
            $this->filesystemManager->forgetDisk($diskName);

            return;
        }

        unset($diskConfigs[$diskName]);

        $this->config->set('filesystems.disks', $diskConfigs);
        $this->filesystemManager->forgetDisk($diskName);
    }

    public function saveFilesystem(FsInterface $fs, bool $runValidation = true): bool
    {
        $configPath = sprintf('%s.%s', ProjectConfig::PATH_FS, $fs->handle);
        $isNewFs = $this->projectConfig->get($configPath) === null;

        if (! $fs->beforeSave($isNewFs)) {
            return false;
        }

        if ($runValidation && ! $fs->validate()) {
            Log::info('Filesystem not saved due to validation error.', [__METHOD__]);

            return false;
        }

        $configData = $this->createFilesystemConfig($fs);
        $this->projectConfig->set($configPath, $configData, "Save the “{$fs->handle}” filesystem");

        if ($fs->oldHandle && $fs->oldHandle !== $fs->handle) {
            $existingFilesystem = $this->getFilesystemByHandle($fs->oldHandle);
            if ($existingFilesystem) {
                $this->removeFilesystem($existingFilesystem);

                // Update volumes that were pointing to the old handle if they were hard-coded.
                $volumes = Volumes::getAllVolumes();
                foreach ($volumes as $volume) {
                    $changed = false;
                    if ($volume->getFsHandle(false) === $fs->oldHandle) {
                        $volume->setFsHandle($fs->handle);
                        $changed = true;
                    }

                    if ($volume->getTransformFsHandle(false) === $fs->oldHandle) {
                        $volume->setTransformFsHandle($fs->handle);
                        $changed = true;
                    }

                    if ($changed) {
                        Volumes::saveVolume($volume);
                    }
                }

                event(new FilesystemRenamed($fs));
            }
        }

        $fs->afterSave($isNewFs);

        $this->reset();

        return true;
    }

    /**
     * Creates a filesystem from a given config.
     *
     * @template T of FsInterface
     *
     * @param  class-string<T>|array  $config
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     *
     * @return T
     */
    public function createFilesystem(mixed $config): FsInterface
    {
        try {
            return ComponentHelper::createComponent($config, FsInterface::class);
        } catch (MissingComponentException|InvalidConfigException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];

            /** @var array $config */
            /** @phpstan-var array{errorMessage:string,expectedType:string,type:string} $config */
            unset($config['type']);

            return new MissingFs($config);
        }
    }

    public function removeFilesystem(FsInterface $fs): bool
    {
        if (! $fs->beforeDelete()) {
            return false;
        }

        $this->projectConfig->remove(
            sprintf('%s.%s', ProjectConfig::PATH_FS, $fs->handle),
            "Remove the “{$fs->handle}” filesystem",
        );

        $this->reset();

        $fs->afterDelete();

        return true;
    }

    public function handleChangedFilesystem(?ConfigEvent $event = null): void
    {
        $this->filesystems = null;

        $handle = $event?->tokenMatches[0] ?? null;
        if (is_string($handle) && $handle !== '') {
            $filesystemConfig = is_array($event?->newValue) ? $event->newValue : null;
            $this->registerDisk($handle, $filesystemConfig);
        } else {
            $this->syncDisks();
        }
    }

    public function handleDeletedFilesystem(?ConfigEvent $event = null): void
    {
        $this->filesystems = null;

        $handle = $event?->tokenMatches[0] ?? null;
        if (is_string($handle) && $handle !== '') {
            $this->purgeDisk($handle);
        } else {
            $this->syncDisks();
        }
    }

    public function diskExists(string $diskName): bool
    {
        $diskConfigs = config('filesystems.disks', []);

        return is_array($diskConfigs) && array_key_exists($diskName, $diskConfigs);
    }

    /**
     * Resolves a handle to a filesystem instance.
     *
     * Supports: `disk:diskName`, Craft filesystem handles, plain Laravel disk names.
     */
    public function resolve(string $handle): ?FsInterface
    {
        if (str_starts_with($handle, 'disk:')) {
            $diskName = substr($handle, strlen('disk:'));
            if ($diskName !== '' && $this->diskExists($diskName)) {
                return new DiskFilesystem(['disk' => $diskName]);
            }

            return null;
        }

        $fs = $this->getFilesystemByHandle($handle);
        if ($fs) {
            return $fs;
        }

        if ($this->diskExists($handle)) {
            return new DiskFilesystem(['disk' => $handle]);
        }

        return null;
    }

    /**
     * Resolves a handle to a Laravel disk name for use with Storage::disk().
     */
    public function resolveDiskName(string $handle): ?string
    {
        if (str_starts_with($handle, 'disk:')) {
            $diskName = substr($handle, strlen('disk:'));

            return ($diskName !== '' && $this->diskExists($diskName)) ? $diskName : null;
        }

        if ($this->getFilesystemByHandle($handle)) {
            return $this->toDiskName($handle);
        }

        if ($this->diskExists($handle)) {
            return $handle;
        }

        return null;
    }

    public function reset(): void
    {
        $this->filesystems = null;
        $this->syncDisks();
    }

    private function currentDiskConfigs(): array
    {
        $diskConfigs = $this->config->get('filesystems.disks', []);

        if (! is_array($diskConfigs)) {
            return [];
        }

        return $diskConfigs;
    }

    /**
     * @param  array<int,string>  $diskNames
     */
    private function forgetDisks(array $diskNames): void
    {
        $diskNames = array_values(array_unique($diskNames));
        if ($diskNames === []) {
            return;
        }

        $this->filesystemManager->forgetDisk($diskNames);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function craftDisksFromProjectConfig(): array
    {
        $craftDisks = [];
        $projectConfig = $this->projectConfig->get(ProjectConfig::PATH_FS);

        if (! is_array($projectConfig)) {
            return $craftDisks;
        }

        foreach ($projectConfig as $handle => $filesystemConfig) {
            if (! is_string($handle)) {
                continue;
            }
            if ($handle === '') {
                continue;
            }
            $diskConfig = $this->resolveDiskConfig($handle);
            if ($diskConfig === null) {
                continue;
            }

            $craftDisks[$this->toDiskName($handle)] = $diskConfig;
        }

        return $craftDisks;
    }

    /**
     * Resolves the Laravel disk configuration for a Craft filesystem handle.
     *
     * @return array<string,mixed>|null
     */
    private function resolveDiskConfig(string $handle): ?array
    {
        $filesystem = $this->getFilesystemByHandle($handle);

        if ($filesystem === null) {
            return null;
        }

        try {
            return $filesystem->getDiskConfig();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolves the Laravel disk configuration from a raw filesystem config array.
     *
     * Used when the filesystem config is available from a ProjectConfig event
     * but hasn't been committed to ProjectConfig storage yet.
     *
     * @param  array<string, mixed>  $filesystemConfig
     * @return array<string,mixed>|null
     */
    private function resolveDiskConfigFromArray(string $handle, array $filesystemConfig): ?array
    {
        $filesystemConfig['handle'] = $handle;
        $filesystemConfig['settings'] = ProjectConfigHelper::unpackAssociativeArrays($filesystemConfig['settings'] ?? []);

        try {
            return $this->createFilesystem($filesystemConfig)->getDiskConfig();
        } catch (Throwable) {
            return null;
        }
    }
}
