<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use Illuminate\Validation\Validator;

/**
 * ImportableElementContainerFieldInterface defines the common interface to be implemented by field classes
 * that contain nested elements and wish to support importing content via the import mechanism.
 */
interface ImportableElementContainerFieldInterface extends ElementContainerFieldInterface
{
    /**
     * Normalize the nested entry data for import, applying the specified field layout configuration.
     *
     * @param  array  $dataItem  The data item to be normalized.
     * @param  FieldLayout  $fieldLayout  The field layout to apply to the data item.
     * @return array The normalized data item.
     */
    public function normalizeNestedEntryForImport(array $dataItem, FieldLayout $fieldLayout, ?ElementInterface $owner = null): array;

    /**
     * Returns a namespace prefix that is used on the mapping screen.
     */
    public function getMappingUiPrefix(FieldLayout $fieldLayout, mixed $provider = null, ?string $prefix = null): string;

    /**
     * Validates field's mapping.
     */
    public function validateMapping(mixed $value, string $attribute, Closure $fail, Validator $validator, array $params = []): bool;
}
