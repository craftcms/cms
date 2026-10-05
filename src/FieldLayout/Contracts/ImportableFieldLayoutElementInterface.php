<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Contracts;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\FieldMappingSetting;
use CraftCms\Cms\Import\Data\MappingColumn;

interface ImportableFieldLayoutElementInterface
{
    /**
     * Returns the destination column(s) this element offers for mapping, or null to offer none.
     *
     * Most native fields are single input text fields, so their column mapping is a single field.
     * Other fields, such as AddressField are more complex and they have their own implementation of this method.
     * And custom field instances have their own implementation of this method.
     *
     * Additionally, for the CustomField instances,
     * the underlying Field can implement the getFieldsForImportMapping() method to further customise this.
     */
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): MappingColumn|CompoundMappingColumn|null;

    /**
     * Returns whether the element can be used as a match criteria.
     * For the CustomField instances,
     * the underlying Field can implement the canBeImportMatchCriteria() method to further customise this.
     *
     * It's false by default.
     */
    public function canBeMatchCriteria(): bool;

    /**
     * Returns whether the element's value can be cleared on import when no data is provided or the provided value is empty.
     * For the CustomField instances,
     * the underlying Field can implement the canBeImportCleared() method to further customise this.
     *
     * It's false by default.
     */
    public function canBeCleared(): bool;

    /**
     * Returns the settings that can be set for this field when it’s mapped in an import.
     *
     * @return list<FieldMappingSetting>
     */
    public function getImportMappingExtraSettings(): array;
}
