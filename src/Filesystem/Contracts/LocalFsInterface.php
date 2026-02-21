<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

/**
 * LocalFsInterface is the interface that must be implemented by all filesystems that operate locally.
 */
interface LocalFsInterface
{
    /**
     * Return the root path of the FS.
     */
    public function getRootPath(): string;
}
