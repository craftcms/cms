<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @see \CraftCms\Cms\Field\Matrix::getEntryTypesForField()
 */
final class DefineEntryTypesForField
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
