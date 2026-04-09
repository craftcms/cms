<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Support\Attributes\Importable;
use CraftCms\Cms\Support\Facades\EntryTypes;
use League\Fractal\TransformerAbstract;

class ElementTransformer extends TransformerAbstract
{
    public ?array $props = null;

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

        $fieldLayout = $element->getFieldLayout();
        if (! $fieldLayout) {
            if (isset($array['typeId'])) {
                $entryType = EntryTypes::getEntryTypeById($array['typeId']);
                if ($entryType) {
                    $fieldLayout = $entryType->getFieldLayout();
                }
            }
        }

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
