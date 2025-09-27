<?php

namespace CraftCms\Cms\Edition\Events;

use CraftCms\Cms\Edition;

/** @since 6.0.0 */
final readonly class EditionChanged
{
    public function __construct(
        public Edition $oldEdition,
        public Edition $newEdition,
    ) {}
}
