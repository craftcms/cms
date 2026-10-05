<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Concerns;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\FieldMappingSetting;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Support\ImportHelper;

trait ImportableFieldLayoutElement
{
    /**
     * @see ImportableFieldLayoutElementInterface::getFieldsForMapping()
     */
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): MappingColumn|CompoundMappingColumn|null
    {
        $attribute = $this->attribute();

        return MappingColumn::make(
            handle: $attribute,
            label: (string) $this->label(),
            prefixedHandle: ImportHelper::prefixedHandleForMapping($attribute, $ownerField, null, $fieldLayout, $provider, $prefix),
            canBeMatchCriteria: $this->canBeMatchCriteria(),
            canBeCleared: $this->canBeCleared(),
        );
    }

    /**
     * @see ImportableFieldLayoutElementInterface::canBeMatchCriteria()
     */
    public function canBeMatchCriteria(): bool
    {
        return false;
    }

    /**
     * @see ImportableFieldLayoutElementInterface::canBeCleared()
     */
    public function canBeCleared(): bool
    {
        return false;
    }

    /**
     * @see ImportableFieldLayoutElementInterface::getImportMappingExtraSettings()
     *
     * @return list<FieldMappingSetting>
     */
    public function getImportMappingExtraSettings(): array
    {
        return [];
    }
}
