<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use Craft;
use craft\base\MemoizableArray;
use craft\errors\MissingComponentException;
use craft\fs\Local;
use craft\fs\MissingFs;
use craft\helpers\Component as ComponentHelper;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Filesystem\Events\RegisterFilesystemTypes;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Filesystem\Filesystem as LaravelFilesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use yii\base\InvalidConfigException;

#[Singleton]
final class Filesystems
{
    /**
     * @var MemoizableArray<FsInterface>|null
     */
    private ?MemoizableArray $_filesystems = null;

    /**
     * Serializer
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['_filesystems']);

        return $vars;
    }

    /**
     * Returns the config for the given filesystem.
     */
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
     * Returns a memoizable array of all filesystems.
     *
     * @return MemoizableArray<FsInterface>
     */
    private function _filesystems(): MemoizableArray
    {
        if (! isset($this->_filesystems)) {
            $configs = app(ProjectConfig::class)->get(ProjectConfig::PATH_FS) ?? [];
            $configs = array_map(function (string $handle, array $config) {
                $config['handle'] = $handle;
                $config['settings'] = ProjectConfigHelper::unpackAssociativeArrays($config['settings'] ?? []);

                return $config;
            }, array_keys($configs), $configs);
            $this->_filesystems = new MemoizableArray($configs, fn (array $config) => $this->createFilesystem($config));
        }

        return $this->_filesystems;
    }

    /**
     * Returns all filesystems.
     *
     * @return Collection<int,FsInterface>
     */
    public function getAllFilesystems(): Collection
    {
        return Collection::make($this->_filesystems()->all())->values();
    }

    /**
     * Returns a filesystem by its handle.
     */
    public function getFilesystemByHandle(string $handle): ?FsInterface
    {
        return $this->_filesystems()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns the Laravel disk name for a Craft filesystem handle.
     */
    public function toDiskName(string $handle): string
    {
        return app(DiskRegistry::class)->toDiskName($handle);
    }

    /**
     * Returns a Laravel disk for the given Craft filesystem handle.
     */
    public function disk(string $handle): LaravelFilesystem
    {
        return Storage::disk($this->toDiskName($handle));
    }

    /**
     * Creates or updates a filesystem.
     *
     * @throws Throwable
     */
    public function saveFilesystem(FsInterface $fs, bool $runValidation = true): bool
    {
        $projectConfig = app(ProjectConfig::class);
        $configPath = sprintf('%s.%s', ProjectConfig::PATH_FS, $fs->handle);
        $isNewFs = $projectConfig->get($configPath) !== null;

        if (! $fs->beforeSave($isNewFs)) {
            return false;
        }

        if ($runValidation && ! $fs->validate()) {
            Log::info('Filesystem not saved due to validation error.', [__METHOD__]);

            return false;
        }

        $configData = $this->createFilesystemConfig($fs);
        $projectConfig->set($configPath, $configData, "Save the “{$fs->handle}” filesystem");

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

        $this->_filesystems = null;
        $this->syncDiskRegistrations();

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

    /**
     * Removes a filesystem.
     *
     * @throws Throwable
     */
    public function removeFilesystem(FsInterface $fs): bool
    {
        if (! $fs->beforeDelete()) {
            return false;
        }

        app(ProjectConfig::class)->remove(sprintf('%s.%s', ProjectConfig::PATH_FS, $fs->handle), "Remove the “{$fs->handle}” filesystem");

        $this->_filesystems = null;
        $this->syncDiskRegistrations();

        $fs->afterDelete();

        return true;
    }

    /**
     * Handle filesystem config changes.
     */
    public function handleChangedFilesystem(): void
    {
        $this->_filesystems = null;
        $this->syncDiskRegistrations();
    }

    /**
     * Handle filesystem config deletions.
     */
    public function handleDeletedFilesystem(): void
    {
        $this->_filesystems = null;
        $this->syncDiskRegistrations();
    }

    private function syncDiskRegistrations(): void
    {
        app(DiskRegistry::class)->sync();
    }
}
