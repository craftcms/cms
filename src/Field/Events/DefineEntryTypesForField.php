<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Matrix;

/**
 * @see Matrix::getEntryTypesForField()
 */
class DefineEntryTypesForField
{
    public function __construct(
        /** @var FieldInterface The current field */
        public FieldInterface $field,

        /** @var array The available entry types */
        public array $entryTypes,

        /** @var ElementInterface|null The element that the field is generating an input for. */
        public ?ElementInterface $element,

        /** @var Entry[] The current value of the field. */
        public array $value,
    ) {}
}
