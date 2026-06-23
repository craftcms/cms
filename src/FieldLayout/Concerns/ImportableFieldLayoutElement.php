<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Concerns;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\ImportHelper;

trait ImportableFieldLayoutElement
{
    /**
     * @see ImportableFieldLayoutElementInterface::getFieldsForMapping()
     */
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): array
    {
        $attribute = $this->attribute();
        [$prefixedHandleForMap, $prefixedHandleForMatchCriteria, $prefixedHandle, $prefixedHandleAsArray] = ImportHelper::getPrefixedHandlesForMapping($attribute, $ownerField, null, $fieldLayout, $provider, $prefix);

        return [
            'handle' => $attribute,
            'label' => $this->label(),
            'prefixedHandleForMap' => $prefixedHandleForMap,
            'prefixedHandleForMatchCriteria' => $prefixedHandleForMatchCriteria,
            'prefixedHandle' => $prefixedHandle,
            'prefixedHandleAsArray' => $prefixedHandleAsArray,
            'isContainer' => false,
            'canBeMatchCriteria' => $this->canBeMatchCriteria() ?? false,
        ];
    }

    /**
     * @see ImportableFieldLayoutElementInterface::canBeMatchCriteria()
     */
    public function canBeMatchCriteria(): bool
    {
        return false;
    }
}
