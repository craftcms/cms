<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\ImportHelper;
use Illuminate\Validation\Validator;

/**
 * ImportableElementContainerFieldTrait provides a base implementation for {@see ImportableElementContainerFieldInterface}.
 */
trait ImportableElementContainerField
{
    /**
     * @see ImportableElementContainerFieldInterface::normalizeNestedEntryForImport()
     *
     * @param  array<string, mixed>  $dataItem
     * @return array<string, mixed>
     */
    public function normalizeNestedEntryForImport(array $dataItem, BaseImporter $importer, FieldLayout $fieldLayout, ?ElementInterface $owner = null, array $importSettings = []): array
    {
        // custom field values may be given loosely rather than wrapped in a `fields` key, so move
        // the ones that match a custom field in the layout there; anything else (native attributes
        // like an address's countryCode, or reserved keys) stays where it is
        if (! isset($dataItem['fields'])) {
            $customFieldHandles = array_filter(
                array_map(
                    fn ($fieldLayoutElement) => $fieldLayoutElement instanceof CustomField ? $fieldLayoutElement->attribute() : null,
                    $fieldLayout->getAllElements()
                )
            );

            $customFields = [];
            foreach ($dataItem as $key => $value) {
                if (in_array($key, $customFieldHandles)) {
                    $customFields[$key] = $value;
                    unset($dataItem[$key]);
                }
            }

            $dataItem['fields'] = $customFields;
        }

        $fields = $dataItem['fields'] ?? [];

        foreach ($fields as $handle => $value) {
            $field = $fieldLayout->getFieldByHandle($handle);

            // if we don't have a field, we don't have to worry about extra normalization, so carry on
            if (! $field) {
                continue;
            }

            // nested elements type fields only need normalizing for nested data, and other fields
            // only for an actual value, so that nothing gets cleared that wasn't before
            if ($field instanceof ImportableElementContainerFieldInterface ? ! is_array($value) : $value === null) {
                continue;
            }

            $dataItem['fields'][$handle] = ImportHelper::normalizeFieldValueForImport($field, $value, $importer, $owner, $importSettings[$handle] ?? []);
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
     *
     * @param  array<string, mixed>  $params
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
