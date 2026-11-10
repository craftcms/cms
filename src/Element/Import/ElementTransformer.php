<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\Transformers\BaseTransformer;

/**
 * @since 6.0.0
 */
class ElementTransformer extends BaseTransformer
{
    /**
     * @var list<array<string, mixed>>|null The importable property descriptors, resolved on first transform.
     */
    public ?array $props = null;

    /**
     * @var array<int|string, list<string>> The handles of the field layouts’ elements, indexed by field layout UID.
     */
    private array $fieldHandles = [];

    /**
     * This method is called automatically for each item being imported.
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
     * @param  array<string, mixed>  $item  The raw item data to transform.
     * @param  BaseImporter  $importer  The importer configuration.
     * @param  mixed  $element  The element the item is being imported into.
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    public function transform(array $item, BaseImporter $importer, mixed $element): array
    {
        // automatically include all Importable properties (e.g. sectionId, typeId for Entry)
        // that apply to the importer's current settings
        $this->props ??= $importer instanceof ElementImporter
            ? $importer->getAvailableProperties()
            : ImportHelper::getImportableProperties($importer);

        $array = [];

        // at this stage the $item contains the keys that have been run through the remapData() helper,
        // so all the UI-based mapping is taken into consideration;
        // we now want to compose an array of key-value pairs where the keys are the properties that are available on the element
        // and the values are the raw values from the incoming data;
        $incomingKeys = ImportHelper::autoMatchingKeys($item);

        foreach ($this->props as $prop) {
            if (($key = ImportHelper::autoMatchedKey((string) $prop['name'], $item, $incomingKeys)) !== null) {
                $array[$prop['name']] = $this->normalizePropertyValue($item[$key], $prop, $element, $importer);
            }
        }

        // include all custom fields
        $fieldLayout = $this->fieldLayoutFor($element, $array);

        // now, we should have a layout, so we can get all the field handles
        if ($fieldLayout) {
            $fieldHandles = $this->fieldHandles[$fieldLayout->uid ?? spl_object_id($fieldLayout)] ??= array_filter(
                array_map(
                    fn (BaseField $fieldLayoutElement) => $fieldLayoutElement->attribute(),
                    $fieldLayout->getElementsByType(BaseField::class)
                )
            );

            // and this is where auto-magical mapping happens between what's in the incoming data and in the field layout element handles
            foreach ($fieldHandles as $fieldHandle) {
                if (($key = ImportHelper::autoMatchedKey((string) $fieldHandle, $item, $incomingKeys)) !== null) {
                    $array[$fieldHandle] = $item[$key];
                }
            }
        }

        return $array;
    }

    /**
     * Returns the field layout whose fields the item is mapped onto.
     *
     * @param  array<string, mixed>  $properties  The item's normalized property values.
     */
    protected function fieldLayoutFor(ElementInterface $element, array $properties): ?FieldLayout
    {
        return $element->getFieldLayout();
    }

    /**
     * Calls a `normalize{PropName}()` method on a raw property value if one is defined on the transformer, else returns the raw or default value.
     * A property that takes a list gets a list-holding string split into its values first.
     *
     * @param  array<string, mixed>  $prop
     */
    private function normalizePropertyValue(mixed $rawValue, array $prop, ElementInterface $element, BaseImporter $importer): mixed
    {
        if ($rawValue !== null) {
            if ($prop['isList'] ?? false) {
                $rawValue = $importer->listValue($rawValue);
            }

            if (method_exists($this, 'normalize'.ucfirst((string) $prop['name']))) {
                return $this->{'normalize'.ucfirst((string) $prop['name'])}($rawValue, $element);
            }

            return $rawValue;
        }

        return $prop['defaultValue'] ?? null;
    }
}
