<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;

use function CraftCms\Cms\t;

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
                $field = null;
                if ($element instanceof CustomField) {
                    try {
                        // getField() needs to be called before label() or we won't always get the label.
                        $field = $element->getField();
                    } catch (FieldNotFoundException) {
                        continue;
                    }
                }

                [$handle, $prefixedHandle, $prefixedHandleWithoutMap] = self::getPrefixesAndHandles($element, $ownerField, $field, $fieldLayout, $provider, $prefix);

                $content = [
                    'handle' => $handle,
                    'label' => $element->label(),
                    'prefixedHandle' => $prefixedHandle,
                    'prefixedHandleWithoutMap' => $prefixedHandleWithoutMap,
                    'isContainer' => $field instanceof ImportableElementContainerFieldInterface,
                ];

                if ($content['isContainer']) {
                    $content['fieldUid'] = $field->uid;
                }

                $cols[] = $content;
            }
        }

        return $cols;
    }

    protected static function getPrefixesAndHandles($element, $ownerField, $field, $fieldLayout, $provider, $prefix): array
    {
        $attr = $element->attribute();
        if ($ownerField instanceof ImportableElementContainerFieldInterface) {
            $namePrefix = $ownerField->getMappingUiPrefix($fieldLayout, $provider, $prefix);
            if ($field instanceof FieldInterface) {
                $prefixedHandle = $namePrefix."[fields][$attr]";
            } else {
                $prefixedHandle = $namePrefix."[$attr]";
            }
            $prefixedHandleWithoutMap = preg_replace('/^map\[([^\]]+)\]/', '$1', $prefixedHandle);
        } else {
            $prefixedHandleWithoutMap = $attr;
            $prefixedHandle = 'map['.$attr.']';
        }

        return [$attr, $prefixedHandle, $prefixedHandleWithoutMap];
    }
}
