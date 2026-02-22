<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use Craft;
use craft\helpers\Component as ComponentHelper;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Filesystem\Events\RegisterFilesystemTypes;
use CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Filesystem\Filesystem as LaravelFilesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use yii\base\InvalidConfigException;

#[Singleton]
final class Filesystems
{
    /**
     * @var Collection<string,FsInterface>|null
     */
    private ?Collection $filesystems = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly DiskRegistry $diskRegistry,
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
        return $this->diskRegistry->toDiskName($handle);
    }

    public function disk(string $handle): LaravelFilesystem
    {
        return Storage::disk($this->toDiskName($handle));
    }

    public function saveFilesystem(FsInterface $fs, bool $runValidation = true): bool
    {
        $configPath = sprintf('%s.%s', ProjectConfig::PATH_FS, $fs->handle);
        $isNewFs = $this->projectConfig->get($configPath) !== null;

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
                $volumesService = Craft::$app->getVolumes();
                $volumes = $volumesService->getAllVolumes();
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
                        $volumesService->saveVolume($volume);
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

    public function handleChangedFilesystem(): void
    {
        $this->reset();
    }

    public function handleDeletedFilesystem(): void
    {
        $this->reset();
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

    private function reset(): void
    {
        $this->filesystems = null;
        $this->diskRegistry->sync();
    }
}
