<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array{name:string,type:class-string<\CraftCms\Cms\Filesystem\Contracts\FsInterface>,settings:array<string,mixed>,hasUrls?:bool,url?:string} createFilesystemConfig(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs)
 * @method static \Illuminate\Support\Collection<int,class-string<\CraftCms\Cms\Filesystem\Contracts\FsInterface>> getAllFilesystemTypes()
 * @method static \Illuminate\Support\Collection<int,\CraftCms\Cms\Filesystem\Contracts\FsInterface> getAllFilesystems()
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface|null getFilesystemByHandle(string $handle)
 * @method static string toDiskName(string $handle)
 * @method static \Illuminate\Contracts\Filesystem\Filesystem disk(string $handle)
 * @method static bool saveFilesystem(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs, bool $runValidation = true)
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface createFilesystem(mixed $config)
 * @method static bool removeFilesystem(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs)
 * @method static void handleChangedFilesystem(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent|null $event = null)
 * @method static void handleDeletedFilesystem(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent|null $event = null)
 * @method static bool diskExists(string $diskName)
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface|null resolve(string $handle)
 * @method static string|null resolveDiskName(string $handle)
 *
 * @see \CraftCms\Cms\Filesystem\Filesystems
 */
final class Filesystems extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Filesystem\Filesystems::class;
    }
}
