<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Schema;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Json;

/**
 * @since 6.0.0
 */
readonly class FieldLayoutConfig
{
    private const string Description = 'Native Craft field layout config: a `tabs` list, each with a `name`, an optional `icon` (a Font Awesome icon name, such as `newspaper`, shown before the tab’s name), an optional `type` (`content`, the default, or `sidebar`; sidebar tabs always come after content tabs), and its `elements`. component_types_list with kind: field-layout-element and the layout’s element type as scope lists the elements a layout accepts; component_types_get describes their settings. Create fields before referencing their UIDs. The fieldLayout object configuration_get returns is accepted too. New tabs and elements can omit uid; keep an existing element’s uid when moving it. Tabs and layout elements take userCondition and elementCondition visibility conditions, which are null when unset; condition_schema with context field-layout lists their rules.';

    public const array Schema = [
        'type' => 'object',
        'description' => self::Description,
        'additionalProperties' => true,
    ];

    public const array NullableSchema = [
        ...self::Schema,
        'type' => ['object', 'null'],
        'description' => self::Description.' Pass null to clear the layout.',
    ];

    public function __construct(private Fields $fields, private ConditionSchema $conditions) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  class-string<ElementInterface>  $elementType
     */
    public function make(array $config, string $elementType, ?FieldLayout $existing = null): FieldLayout
    {
        // Accept the shape reads return, with the layout config under `config`
        if (is_array($config['config'] ?? null)) {
            $config = $config['config'];
        }

        $config['type'] = $elementType;
        $config = $this->normalizeConditions($config, $elementType);

        if (! $existing) {
            return $this->fields->createLayout($config);
        }

        $config['id'] = $existing->id;
        $config['uid'] = $existing->uid;
        $layout = $this->fields->createLayout($config);
        $this->keepCustomFieldUids($layout, $existing);

        return $layout;
    }

    /**
     * Normalizes the visibility conditions of tabs and layout elements, rejecting rules Craft would drop.
     *
     * @param  array<string, mixed>  $config
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, mixed>
     */
    private function normalizeConditions(array $config, string $elementType): array
    {
        if (! is_array($config['tabs'] ?? null)) {
            return $config;
        }

        // Element rules can use the fields of the layout being saved
        $layout = $this->fields->createLayout($config);

        foreach ($config['tabs'] as $tabIndex => $tab) {
            if (! is_array($tab)) {
                continue;
            }

            $path = "fieldLayout.tabs[$tabIndex]";
            $tab = $this->normalizeComponentConditions($tab, $elementType, $layout, $path);
            $elements = is_string($tab['elements'] ?? null) ? Json::decode($tab['elements']) : ($tab['elements'] ?? null);

            if (is_array($elements)) {
                foreach ($elements as $elementIndex => $element) {
                    if (is_array($element)) {
                        $elements[$elementIndex] = $this->normalizeComponentConditions($element, $elementType, $layout, "$path.elements[$elementIndex]");
                    }
                }

                $tab['elements'] = $elements;
            }

            $config['tabs'][$tabIndex] = $tab;
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $component
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, mixed>
     */
    private function normalizeComponentConditions(array $component, string $elementType, FieldLayout $layout, string $path): array
    {
        foreach (['userCondition' => 'user', 'elementCondition' => 'element'] as $key => $kind) {
            if (isset($component[$key])) {
                $condition = $this->conditions->normalize($this->conditions->forFieldLayout($elementType, $layout, $kind), $component[$key], "$path.$key");
                $component[$key] = $this->conditions->hasRules($condition) ? $condition : null;
            }
        }

        return $component;
    }

    /**
     * Gives custom fields sent without a UID the UID they have in the existing layout, since element content is
     * stored under it. Multiple instances of a field take the existing UIDs in order.
     */
    private function keepCustomFieldUids(FieldLayout $layout, FieldLayout $existing): void
    {
        $takenUids = array_flip(array_filter(array_map(
            fn (FieldLayoutElement $element) => $element->uid,
            $layout->getAllElements(),
        )));
        $existingUids = [];

        foreach ($existing->getElementsByType(CustomField::class) as $element) {
            if (! isset($takenUids[$element->uid])) {
                $existingUids[$element->getFieldUid()][] = $element->uid;
            }
        }

        foreach ($layout->getElementsByType(CustomField::class) as $element) {
            if ($element->uid === null && ! empty($existingUids[$element->getFieldUid()])) {
                $element->uid = array_shift($existingUids[$element->getFieldUid()]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function serialize(FieldLayout $layout): array
    {
        return [
            'id' => $layout->id,
            'uid' => $layout->uid,
            'type' => $layout->type,
            'config' => $layout->getConfig() ?? ['tabs' => []],
        ];
    }
}
