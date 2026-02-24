<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Events;

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use Illuminate\Support\Collection;

/**
 * @event RegisterFilesystemTypes The event that is triggered when registering filesystem types.
 */
final class RegisterFilesystemTypes
{
    public function __construct(
        /** @var Collection<class-string<FsInterface>> */
        public Collection $types,
    ) {}
}
