<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

class FieldElementEvent
{
    use ValidatableEvent;

    public function __construct(
        public FieldInterface $field,
        public ElementInterface $element,
        public readonly bool $isNew = false,
    ) {}
}
