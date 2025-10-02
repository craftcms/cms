<?php

namespace CraftCms\Cms\Field\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/** @since 6.0.0 */
final class FieldElementEvent
{
    use ValidatableEvent;

    public function __construct(
        public FieldInterface $field,
        public ElementInterface $element,
        public readonly bool $isNew = false,
    ) {}
}
