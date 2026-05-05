<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\FsEvent;
use craft\events\RegisterComponentTypesEvent;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Filesystem\Events\FilesystemTypesResolving;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Contracts\Filesystem\Filesystem as LaravelFilesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event as EventFacade;
use Throwable;
use yii\base\Component;

/**
 * Filesystems service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getFs()|`Craft::$app->getFs()`]].
 *
 * @property-read FsInterface[] $allFilesystems All filesystems
 * @property-read string[] $allFilesystemTypes All registered filesystem types
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Filesystem\Filesystems} instead.
 */
class Fs extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering filesystem types.
     */
    public const EVENT_REGISTER_FILESYSTEM_TYPES = 'registerFilesystemTypes';

    /**
     * @event FsEvent The event that is triggered after a filesystem is renamed.
     */
    public const EVENT_RENAME_FILESYSTEM = 'renameFs';

    /**
     * Serializer
     */
    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * Returns the config for the given filesystem.
     */
    public function createFilesystemConfig(FsInterface $fs): array
    {
        return $this->service()->createFilesystemConfig($fs);
    }

    /**
     * Returns all registered filesystem types.
     *
     * @return string[]
     *
     * @phpstan-return class-string<FsInterface>[]
     */
    public function getAllFilesystemTypes(): array
    {
        return $this->service()->getAllFilesystemTypes()->values()->all();
    }

    /**
     * Returns all filesystems.
     *
     * @return FsInterface[]
     */
    public function getAllFilesystems(): array
    {
        return $this->service()->getAllFilesystems()->values()->all();
    }

    /**
     * Returns a filesystem by its handle.
     */
    public function getFilesystemByHandle(string $handle): ?FsInterface
    {
        return $this->service()->getFilesystemByHandle($handle);
    }

    /**
     * Returns the Laravel disk name for a Craft filesystem handle.
     */
    public function toDiskName(string $handle): string
    {
        return $this->service()->toDiskName($handle);
    }

    /**
     * Returns a Laravel disk for the given Craft filesystem handle.
     */
    public function disk(string $handle): LaravelFilesystem
    {
        return $this->service()->disk($handle);
    }

    /**
     * Creates or updates a filesystem.
     *
     * @param  FsInterface  $fs  the filesystem to be saved.
     * @param  bool  $runValidation  Whether the filesystem should be validated
     * @return bool Whether the filesystem was saved successfully
     *
     * @throws Throwable
     */
    public function saveFilesystem(FsInterface $fs, bool $runValidation = true): bool
    {
        return $this->service()->saveFilesystem($fs, $runValidation);
    }

    /**
     * Creates a filesystem from a given config.
     *
     * @template T as FsInterface
     *
     * @param  class-string<T>|array  $config  The filesystem’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     *
     * @return T The filesystem
     */
    public function createFilesystem(mixed $config): FsInterface
    {
        return $this->service()->createFilesystem($config);
    }

    /**
     * Removes a filesystem.
     *
     * @param  FsInterface  $fs  The filesystem to remove
     *
     * @throws Throwable
     */
    public function removeFilesystem(FsInterface $fs): bool
    {
        return $this->service()->removeFilesystem($fs);
    }

    /**
     * Handle filesystem config changes.
     */
    public function handleChangedFilesystem(ConfigEvent $event): void
    {
        $this->service()->handleChangedFilesystem($event);
    }

    /**
     * Handle filesystem config deletions.
     */
    public function handleDeletedFilesystem(ConfigEvent $event): void
    {
        $this->service()->handleDeletedFilesystem($event);
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(FilesystemTypesResolving::class, function(FilesystemTypesResolving $event) {
            if (!Craft::$app->getFs()->hasEventHandlers(self::EVENT_REGISTER_FILESYSTEM_TYPES)) {
                return;
            }

            $yiiEvent = new RegisterComponentTypesEvent(['types' => $event->types->all()]);
            Craft::$app->getFs()->trigger(self::EVENT_REGISTER_FILESYSTEM_TYPES, $yiiEvent);

            $event->types = Collection::make($yiiEvent->types);
        });

        EventFacade::listen(FilesystemRenamed::class, function(FilesystemRenamed $event) {
            if (!Craft::$app->getFs()->hasEventHandlers(self::EVENT_RENAME_FILESYSTEM)) {
                return;
            }

            Craft::$app->getFs()->trigger(self::EVENT_RENAME_FILESYSTEM, new FsEvent($event->filesystem));
        });
    }

    private function service(): Filesystems
    {
        return app(Filesystems::class);
    }
}
