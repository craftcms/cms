<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Events;

use CraftCms\Cms\Filesystem\Contracts\FsInterface;

final class FilesystemRenamed
{
    public function __construct(
        public FsInterface $filesystem,
    ) {}
}
