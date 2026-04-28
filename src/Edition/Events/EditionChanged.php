<?php

declare(strict_types=1);

namespace CraftCms\Cms\Edition\Events;

use CraftCms\Cms\Edition;

readonly class EditionChanged
{
    public function __construct(
        public Edition $oldEdition,
        public Edition $newEdition,
    ) {}
}
