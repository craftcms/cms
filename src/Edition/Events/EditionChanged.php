<?php

declare(strict_types=1);

namespace CraftCms\Cms\Edition\Events;

use CraftCms\Cms\Edition;

/**
 * @since 6.0.0
 */
readonly class EditionChanged
{
    public function __construct(
        public Edition $oldEdition,
        public Edition $newEdition,
    ) {}
}
