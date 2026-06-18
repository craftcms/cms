<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use Illuminate\Validation\Validator;

/**
 * ImportableElementContainerFieldTrait provides a base implementation for {@see ImportableElementContainerFieldInterface}.
 */
trait ImportableElementContainerField
{
    /**
     * @see ImportableElementContainerFieldInterface::normalizeNestedEntryForImport()
     */
    public function normalizeNestedEntryForImport(array $dataItem, FieldLayout $fieldLayout, ?ElementInterface $owner = null): array
    {
        $fields = $dataItem['fields'] ?? [];

        foreach ($fields as $handle => $value) {
            if (! is_array($value)) {
                continue;
            }
            $field = $fieldLayout->getFieldByHandle($handle);

            // if we don't have a field, or it's not an importable nested elements type field,
            // we don't have to worry about extra normalization, so carry on
            if (! $field instanceof ImportableElementContainerFieldInterface) {
                continue;
            }

            $dataItem['fields'][$handle] = $field->normalizeValueForImport($value, $owner);
        }

        return $dataItem;
    }

    // todo: maybe remove me - maybe I'm temporary?
    /**
     * @see ImportableElementContainerFieldInterface::getMappingUiPrefix()
     */
    public function getMappingUiPrefix(FieldLayout $fieldLayout, mixed $provider = null, ?string $prefix = null): string
    {
        // by default return empty string?
        return '';
    }

    // todo: maybe remove me - maybe I'm temporary?
    public function validateMapping(mixed $value, string $attribute, Closure $fail, Validator $validator, array $params = []): bool
    {
        return true;
    }
}
