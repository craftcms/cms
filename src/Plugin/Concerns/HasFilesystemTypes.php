<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\FilesystemTypes;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasFilesystemTypes
{
    /**
     * Array of filesystem types to register.
     *
     * @var class-string<FsInterface>[]
     */
    protected array $filesystemTypes = [];

    public function bootHasFilesystemTypes(): void
    {
        $this->app->make(FilesystemTypes::class)->register(...$this->filesystemTypes);
    }
}
