<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

class ImportHelper
{
    public static function getDestinationColsForFieldLayout(
        ?FieldLayout $fieldLayout,
        ?FieldInterface $ownerField = null,
        mixed $provider = null,
        ?string $prefix = null
    ): array {
        $cols = [];
        if ($fieldLayout) {
            $allElements = $fieldLayout->getAllElements();

            foreach ($allElements as $element) {
                if ($element instanceof ImportableFieldLayoutElementInterface) {
                    // get element's fields for mapping; for example,
                    // lat/long has two fields;
                    // addresses field has (by default) label, country code and address field which then contains a bunch of other fields;
                    // and custom fields have yet another way of getting this
                    $cols[] = $element->getFieldsForMapping($fieldLayout, $ownerField, $provider, $prefix);
                }
            }
        }

        return $cols;
    }

    public static function getPrefixedHandlesForMapping($attribute, $ownerField, $field, $fieldLayout, $provider, $prefix): array
    {
        if ($ownerField instanceof ImportableElementContainerFieldInterface) {
            $namePrefix = $ownerField->getMappingUiPrefix($fieldLayout, $provider, $prefix);
            if ($field instanceof FieldInterface) {
                $prefixedHandle = $namePrefix."[fields][$attribute]";
            } else {
                $prefixedHandle = $namePrefix."[$attribute]";
            }
            $prefixedHandleWithoutMap = preg_replace('/^map\[([^\]]+)\]/', '$1', $prefixedHandle);
        } else {
            $prefixedHandleWithoutMap = $attribute;
            $prefixedHandle = 'map['.$attribute.']';
        }

        return [$prefixedHandle, $prefixedHandleWithoutMap];
    }
}
