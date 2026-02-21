<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array{name:string,type:class-string<\craft\base\FsInterface>,settings:array<string,mixed>,hasUrls?:bool,url?:string} createFilesystemConfig(\craft\base\FsInterface $fs)
 * @method static \Illuminate\Support\Collection<int,class-string<\craft\base\FsInterface>> getAllFilesystemTypes()
 * @method static \Illuminate\Support\Collection<int,\craft\base\FsInterface> getAllFilesystems()
 * @method static \craft\base\FsInterface|null getFilesystemByHandle(string $handle)
 * @method static string toDiskName(string $handle)
 * @method static \Illuminate\Contracts\Filesystem\Filesystem disk(string $handle)
 * @method static bool saveFilesystem(\craft\base\FsInterface $fs, bool $runValidation = true)
 * @method static \craft\base\FsInterface createFilesystem(mixed $config)
 * @method static bool removeFilesystem(\craft\base\FsInterface $fs)
 * @method static void handleChangedFilesystem(?\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event = null)
 * @method static void handleDeletedFilesystem(?\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event = null)
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
