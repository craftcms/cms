<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @phpstan-import-type Option from BaseOptionsField
 * @phpstan-import-type Optgroup from BaseOptionsField
 */
class InputOptionsResolving
{
    public function __construct(
        /** @var FieldInterface the current field */
        public FieldInterface $field,

        /** @var list<Option|Optgroup> The options that will be available for the current field */
        public array $options,

        /** @var mixed The current value of the field. */
        public mixed $value,

        /** @var ElementInterface|null The element that the field is generating an input for. */
        public ?ElementInterface $element = null,
    ) {}
}
