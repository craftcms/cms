<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Events;

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use Illuminate\Support\Collection;

/**
 * @event FilesystemTypesResolving The event that is triggered when registering filesystem types.
 */
class FilesystemTypesResolving
{
    public function __construct(
        /** @var Collection<class-string<FsInterface>> */
        public Collection $types,
    ) {}
}
