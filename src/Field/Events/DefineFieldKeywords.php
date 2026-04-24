<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

class DefineFieldKeywords extends FieldEvent
{
    use HandleableEvent;

    public function __construct(
        public FieldInterface $field,
        public ElementInterface $element,
        public mixed $value,
        public string $keywords = '',
    ) {
        parent::__construct($field);
    }
}
