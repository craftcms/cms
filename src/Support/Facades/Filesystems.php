<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array createFilesystemConfig(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs)
 * @method static \Illuminate\Support\Collection getAllFilesystemTypes()
 * @method static \Illuminate\Support\Collection getAllFilesystems()
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface|null getFilesystemByHandle(string $handle)
 * @method static string toDiskName(string $handle)
 * @method static \Illuminate\Contracts\Filesystem\Filesystem disk(string $handle)
 * @method static bool saveFilesystem(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs, bool $runValidation = true)
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface createFilesystem(mixed $config)
 * @method static bool removeFilesystem(\CraftCms\Cms\Filesystem\Contracts\FsInterface $fs)
 * @method static void handleChangedFilesystem(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void handleDeletedFilesystem(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
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
