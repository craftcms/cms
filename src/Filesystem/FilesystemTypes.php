<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers filesystem type classes available to Craft.
 *
 * ```php
 * public function boot(FilesystemTypes $filesystemTypes): void
 * {
 *     $filesystemTypes->register(MyFilesystem::class);
 * }
 * ```
 *
 * @extends TypeRegistry<FsInterface>
 */
#[Singleton]
class FilesystemTypes extends TypeRegistry
{
    protected const string CONTRACT = FsInterface::class;

    protected const array DEFAULT_TYPES = [Local::class];
}
