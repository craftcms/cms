<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Concerns;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\ImportHelper;

trait ImportableFieldLayoutElement
{
    /**
     * Most native fields are single input text fields, so their column mapping is a single field.
     * Other fields, such as AddressField are more complex and they have their own implementation of this method.
     * And custom field instances have their own implementation of this method.
     *
     * @see ImportableFieldLayoutElementInterface::getFieldsForMapping()
     */
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): array
    {
        $attribute = $this->attribute();
        [$prefixedHandle, $prefixedHandleWithoutMap, $prefixedHandleWithoutMapAsArray] = ImportHelper::getPrefixedHandlesForMapping($attribute, $ownerField, null, $fieldLayout, $provider, $prefix);

        return [
            'handle' => $attribute,
            'label' => $this->label(),
            'prefixedHandle' => $prefixedHandle,
            'prefixedHandleWithoutMap' => $prefixedHandleWithoutMap,
            'prefixedHandleWithoutMapAsArray' => $prefixedHandleWithoutMapAsArray,
            'isContainer' => false,
        ];
    }
}
