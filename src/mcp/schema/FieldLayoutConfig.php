<?php

declare(strict_types=1);

namespace craft\mcp\schema;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\services\Fields;
use Mcp\Exception\ToolCallException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * @since 5.12.0
 */
class FieldLayoutConfig
{
    public const Schema = [
        'type' => 'object',
        'description' => 'Native Craft field layout config: {tabs: [{name, elements: [...]}], ...}, or the fieldLayout object configuration_get returns. Create fields before referencing their UIDs. New tabs and elements can omit uid; keep an existing element’s uid when moving it. Tabs and layout elements take userCondition and elementCondition visibility conditions, which are null when unset; condition_schema with context field-layout lists their rules.',
        'additionalProperties' => true,
    ];

    public const NullableSchema = [
        ...self::Schema,
        'type' => ['object', 'null'],
        'description' => 'Native Craft field layout config: {tabs: [{name, elements: [...]}], ...}, or the fieldLayout object configuration_get returns. Pass null to clear the layout. New tabs and elements can omit uid; keep an existing element’s uid when moving it. Tabs and layout elements take userCondition and elementCondition visibility conditions, which are null when unset; condition_schema with context field-layout lists their rules.',
    ];

    public function __construct(private Fields $fields, private ConditionSchema $conditions)
    {
    }

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

        $this->validateFieldReferences($config);
        $config['type'] = $elementType;

        if ($existing) {
            $config['id'] = $existing->id;
            $config['uid'] = $existing->uid;
        }

        try {
            return $this->fields->createLayout($this->normalizeConditions($config, $elementType));
        } catch (UnknownPropertyException|InvalidCallException|InvalidConfigException $e) {
            throw new ToolCallException("fieldLayout: {$e->getMessage()}");
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

    /**
     * Normalizes the visibility conditions of tabs and layout elements, rejecting rules Craft would drop.
     *
     * @param array<string, mixed> $config
     * @param class-string<ElementInterface> $elementType
     * @return array<string, mixed>
     */
    private function normalizeConditions(array $config, string $elementType): array
    {
        // Element rules can use the fields of the layout being saved
        $layout = $this->fields->createLayout($config);

        foreach ($config['tabs'] ?? [] as $tabIndex => $tab) {
            if (!is_array($tab)) {
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
     * @param array<string, mixed> $component
     * @param class-string<ElementInterface> $elementType
     * @return array<string, mixed>
     */
    private function normalizeComponentConditions(array $component, string $elementType, FieldLayout $layout, string $path): array
    {
        foreach (['userCondition' => 'user', 'elementCondition' => 'element'] as $key => $kind) {
            if (isset($component[$key])) {
                $condition = $this->conditions->normalize($this->conditions->forFieldLayout($elementType, $layout, $kind), $component[$key], "$path.$key");
                $component[$key] = $condition['conditionRules'] ? $condition : null;
            }
        }

        return $component;
    }

    /**
     * Rejects custom field elements whose field doesn’t exist.
     *
     * @param array<string, mixed> $config
     */
    private function validateFieldReferences(array $config): void
    {
        $errors = [];

        foreach ($config['tabs'] ?? [] as $tabIndex => $tab) {
            // Tabs accept their elements as JSON, too
            $elements = is_string($tab['elements'] ?? null) ? Json::decode($tab['elements']) : ($tab['elements'] ?? []);

            foreach ((array)$elements as $elementIndex => $element) {
                $fieldUid = $element['fieldUid'] ?? null;

                if (is_a($element['type'] ?? '', CustomField::class, true) && $fieldUid !== null && !$this->fields->getFieldByUid((string)$fieldUid)) {
                    $errors[] = "fieldLayout.tabs[$tabIndex].elements[$elementIndex].fieldUid: Field “{$fieldUid}” doesn’t exist.";
                }
            }
        }

        if ($errors) {
            throw new ToolCallException(implode("\n", $errors));
        }
    }
}
