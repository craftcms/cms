<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @since 6.0.0
 */
readonly class UserInitiatedElementSaveResult
{
    public function __construct(
        public ElementInterface $element,
        public bool $successful,
    ) {}
}
