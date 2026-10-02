<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

/**
 * @since 6.0.0
 */
class ElementActionPerformed
{
    public function __construct(
        public ElementActionInterface $action,
        public ElementQueryInterface $query,
        public ?string $message = null,
    ) {}
}
