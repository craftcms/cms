<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Support\Attributes\Importable;
use CraftCms\Cms\Support\Facades\EntryTypes;
use League\Fractal\TransformerAbstract;

class ElementTransformer extends TransformerAbstract
{
    public ?array $props = null;

    /**
     * This method is called automatically by Fractal when transforming an item.
     * It's responsible for converting the feed data (e.g. from the json file)
     * into an array that's understood and can be imported into an Element.
     *
     * This is where you can manipulate the data before it's imported.
     * For example, you can choose to uppercase the text, concatenate it or do whatever you want.
     *
     * If you use field handles as keys in the feed data, the mapping will happen automatically.
     * But if you don't, this is where you can also set that up.
     * For example, if you have a 'myPlainTextField' custom field that you know is available on the element you're importing into,
     * and the feed data has a 'myContent' key, you can map it to that field.
     *
     * Note: with automatic handle matching, if the handle is overwritten in the field layout, that's what's used;
     * it doesn't fall back to the original handle.
     *
     * @throws \ReflectionException
     */
    public function transform(mixed $item): array
    {
        $element = $this->getCurrentScope()->getResource()->getMeta()['element'] ?? null;

        // automatically include all Importable properties (e.g. sectionId, typeId for Entry);
        if ($this->props === null) {
            $config = $this->getCurrentScope()->getResource()->getMeta()['config'];
            $class = new \ReflectionClass($config->className);
            $properties = $class->getProperties();
            $properties = array_values(array_filter($properties, fn ($property) => ! empty($property->getAttributes(Importable::class))));
            $this->props = array_map(fn ($property) => [
                'name' => $property->getAttributes(Importable::class)[0]->getArguments()[0],
                'defaultValue' => $property->getDefaultValue(),
            ], $properties);
        }

        $array = [];
        foreach ($this->props as $prop) {
            $array[$prop['name']] = $this->normalizePropertyValue($item, $prop);
        }

        //        // Get the serialized custom field values
        //        $fields = $element->getSerializedFieldValues();
        //
        //        // Get the element attributes that aren't custom fields
        //        /** @var Element $element */
        //        $attributes = array_diff($element->attributes(), array_keys($fields));
        //
        //        // Return the element as an array merged with its serialized custom field values
        //        return array_merge($element->toArray($attributes), $fields);

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
                $array[$fieldHandle] = $item[$fieldHandle] ?? null;
            }
        }

        return $array;
    }

    private function normalizePropertyValue(mixed $item, array $prop): mixed
    {
        $rawValue = $item[$prop['name']] ?? null;

        if ($rawValue !== null) {
            if (method_exists($this, 'normalize'.ucfirst((string) $prop['name']))) {
                return $this::{'normalize'.ucfirst((string) $prop['name'])}($rawValue);
            }

            return $rawValue;
        }

        return $prop['defaultValue'] ?? null;
    }
}
