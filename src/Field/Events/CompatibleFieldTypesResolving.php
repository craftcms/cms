<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use Illuminate\Support\Collection;

/**
 * @event DefineCompatibleFieldTypesEvent The event that is triggered when defining the compatible field types for a field.
 *
 * @see Fields::getCompatibleFieldTypes()
 */
class CompatibleFieldTypesResolving
{
    public function __construct(
        public FieldInterface $field,
        /** @var Collection<int, class-string<FieldInterface>> The field type classes that are considered compatible with {@see $field}. */
        public Collection $compatibleTypes,
    ) {}
}
