<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Attributes\Importable;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\ImportHelper;

class ElementTransformer extends BaseTransformer
{
    public ?array $props = null;

    /**
     * This method is called automatically by Fractal when transforming an item.
     * It's responsible for converting the incoming data (e.g. from the json file)
     * into an array that's understood and can be imported into an Element.
     *
     * This is where you can manipulate the data before it's imported.
     * For example, you can choose to uppercase the text, concatenate it or do whatever you want.
     *
     * If you use field handles as keys in the incoming data, the mapping will happen automatically.
     * But if you don't, this is where you can also set that up.
     * For example, if you have a 'myPlainTextField' custom field that you know is available on the element you're importing into,
     * and the incoming data has a 'myContent' key, you can map it to that field.
     *
     * Note: with automatic handle matching, if the handle is overwritten in the field layout, that's what's used;
     * it *doesn't* fall back to the original handle.
     *
     * @throws \ReflectionException
     */
    public function transform(mixed $item): array
    {
        $element = $this->getCurrentScope()->getResource()->getMeta()['element'] ?? null;

        // automatically include all Importable properties (e.g. sectionId, typeId for Entry);
        if ($this->props === null) {
            $config = $this->getCurrentScope()->getResource()->getMeta()['config'];
            $this->props = ImportHelper::getImportableProperties($config);
        }

        $array = [];

        foreach ($this->props as $prop) {
            if (array_key_exists((string) $prop['name'], $item)) {
                $array[$prop['name']] = $this->normalizePropertyValue($item, $prop, $element);
            }
        }

        // include all custom fields
        $fieldLayout = $element->getFieldLayout();
        // if we don't have a field layout here, it means we're creating a new element and the field layout isn't set yet
        if (! $fieldLayout) {
            // if we have a typeId, try to get the field layout from the entry type - this is the case for Entry element
            if (isset($array['typeId'])) {
                $entryType = EntryTypes::getEntryTypeById($array['typeId']);
                if ($entryType) {
                    $fieldLayout = $entryType->getFieldLayout();
                }
            }
        }

        // now, we should have a layout, so we can get all the field handles
        if ($fieldLayout) {
            $fieldHandles = array_filter(
                array_map(
                    fn ($fieldLayoutElement) => $fieldLayoutElement->attribute(),
                    $fieldLayout->getAllElements()
                )
            );

            foreach ($fieldHandles as $fieldHandle) {
                if (array_key_exists((string) $fieldHandle, $item)) {
                    $array[$fieldHandle] = $item[$fieldHandle];
                }
            }
        }

        return $array;
    }

    private function normalizePropertyValue(mixed $item, array $prop, ElementInterface $element): mixed
    {
        $rawValue = $item[$prop['name']] ?? null;

        if ($rawValue !== null) {
            if (method_exists($this, 'normalize'.ucfirst((string) $prop['name']))) {
                return $this::{'normalize'.ucfirst((string) $prop['name'])}($rawValue, $element);
            }

            return $rawValue;
        }

        return $prop['defaultValue'] ?? null;
    }
}
