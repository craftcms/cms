<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Validation\Validator;

/**
 * ImportableElementContainerFieldTrait provides a base implementation for {@see ImportableElementContainerFieldInterface}.
 */
trait ImportableElementContainerField
{
    /**
     * @see ImportableElementContainerFieldInterface::normalizeNestedEntryForImport()
     */
    public function normalizeNestedEntryForImport(array $dataItem, BaseImporter $importer, FieldLayout $fieldLayout, ?ElementInterface $owner = null): array
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

            $dataItem['fields'][$handle] = $field->normalizeValueForImport($value, $importer, $owner);
        }

        return $dataItem;
    }

    /**
     * Some element container fields only have one field layout provider.
     * In that case, the new prefix is "simply" based on whatever was passed in as a previous prefix.
     * For other fields, like Matrix, this is more complex, and those fields implement their own version of this method.
     *
     * @see ImportableElementContainerFieldInterface::getMappingUiPrefix()
     */
    public function getMappingUiPrefix(FieldLayout $fieldLayout, mixed $provider = null, ?string $prefix = null): string
    {
        return ! empty($prefix) ? $prefix : '';
    }

    /**
     * By default, let mapping validation pass.
     *
     * @see ImportableElementContainerFieldInterface::validateMapping()
     */
    public function validateMapping(mixed $value, string $attribute, Closure $fail, Validator $validator, array $params = []): bool
    {
        return true;
    }

    /**
     * By default, this concept doesn't apply (no list of nested elements to prune).
     *
     * @see ImportableElementContainerFieldInterface::canKeepMissingNestedElements()
     */
    public function canKeepMissingNestedElements(): bool
    {
        return false;
    }

    /**
     * By default, this is a no-op.
     *
     * @see ImportableElementContainerFieldInterface::setKeepMissingNestedElements()
     */
    public function setKeepMissingNestedElements(bool $keep): void
    {
        // by default, this doesn't do anything
    }
}
