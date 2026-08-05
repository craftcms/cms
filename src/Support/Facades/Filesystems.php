<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array<array-key, mixed> createFilesystemConfig(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAllFilesystemTypes()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAllFilesystems()
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface|null getFilesystemByHandle(string $handle)
 * @method static string toDiskName(string $handle)
 * @method static \Illuminate\Filesystem\FilesystemAdapter disk(string $reference, string|null $prefix = null)
 * @method static void syncDisks()
 * @method static void registerDisk(string $handle, array<array-key, mixed>|null $filesystemConfig = null)
 * @method static void purgeDisk(string $handle)
 * @method static bool saveFilesystem(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs, bool $runValidation = true)
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface createFilesystem(string|array<array-key, mixed> $config)
 * @method static bool removeFilesystem(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs)
 * @method static void handleChangedFilesystem(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent|null $event = null)
 * @method static void handleDeletedFilesystem(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent|null $event = null)
 * @method static bool diskExists(string $diskName)
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface|null resolve(string $handle)
 * @method static string|null resolveDiskName(string $handle)
 * @method static void reset()
 *
 * @see \CraftCms\Cms\Filesystem\Filesystems
 */
class Filesystems extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Filesystem\Filesystems::class;
    }
}
