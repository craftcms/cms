<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Import\Transformers\BaseTransformer;
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
     * Returns the transformed data array, ready to be imported into an Element.
     *
     * @param  mixed  $item  The raw item data to transform.
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

        // at this stage the $item contains the keys that have been run through the remapData() helper,
        // so all the UI-based mapping is taken into consideration;
        // we now want to compose an array of key-value pairs where the keys are the properties that are available on the element
        // and the values are the raw values from the incoming data;
        $incomingKeys = array_combine(array_keys($item), array_keys($item));
        $incomingKeys = array_map(fn ($key) => ImportHelper::prepKeyForAutoMatching((string) $key), $incomingKeys);

        foreach ($this->props as $prop) {
            // if it exists, then it was either mapped like this or the "correct" key already existed in the incoming data
            // use it and remove it from a list of keys we can mess with in the next step
            if (array_key_exists((string) $prop['name'], $item)) {
                $array[$prop['name']] = $this->normalizePropertyValue($item, $prop, $element);
                unset($incomingKeys[$prop['name']]);
                // otherwise attempt to match auto-magically
            } elseif ($key = array_search(ImportHelper::prepKeyForAutoMatching((string) $prop['name']), $incomingKeys)) {
                $array[$prop['name']] = $this->normalizePropertyValue($item[$key], $prop, $element);
            }
        }

        // include all custom fields
        $fieldLayout = $element->getFieldLayout();
        // if we don't have a field layout here, it means we're creating a new element and the field layout isn't set yet
        if (! $fieldLayout) {
            // if we have a typeId, try to get the field layout from the entry type - this is the case for Entry element
            if (isset($array['typeId'])) {
                $entryType = EntryTypes::getEntryTypeById((int) $array['typeId']);
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

            // and this is where auto-magical mapping happens between what's in the incoming data and in the field layout element handles
            foreach ($fieldHandles as $fieldHandle) {
                // if it exists, then it was either mapped like this or the "correct" key already existed in the incoming data
                // use it and remove it from a list of keys we can mess with in the next step
                if (array_key_exists((string) $fieldHandle, $item)) {
                    $array[$fieldHandle] = $item[$fieldHandle];
                    unset($incomingKeys[$fieldHandle]);
                    // otherwise attempt to match auto-magically
                } elseif ($key = array_search(ImportHelper::prepKeyForAutoMatching((string) $fieldHandle), $incomingKeys)) {
                    $array[$fieldHandle] = $item[$key];
                }
            }
        }

        return $array;
    }

    /**
     * Looks up a raw property value from the item and calls a `normalize{PropName}()` method if defined on the transformer, else returns the raw or default value.
     */
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
