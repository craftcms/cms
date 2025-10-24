<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

final class DefineFieldKeywords extends FieldEvent
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
