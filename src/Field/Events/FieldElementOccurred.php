<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @since 6.0.0
 */
class FieldElementOccurred
{
    use ValidatableEvent;

    public function __construct(
        public FieldInterface $field,
        public ElementInterface $element,
        public readonly bool $isNew = false,
    ) {}
}
