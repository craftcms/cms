<?php

declare(strict_types=1);

namespace craft\mcp\schema;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\services\Fields;
use Mcp\Exception\ToolCallException;

/**
 * @since 5.12.0
 */
class FieldLayoutConfig
{
    public const Schema = [
        'type' => 'object',
        'description' => 'Native Craft field layout config. Create fields before referencing their UIDs.',
        'additionalProperties' => true,
    ];

    public const NullableSchema = [
        ...self::Schema,
        'type' => ['object', 'null'],
        'description' => 'Native Craft field layout config. Pass null to clear the layout.',
    ];

    public function __construct(private Fields $fields)
    {
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  class-string<ElementInterface>  $elementType
     */
    public function make(array $config, string $elementType, ?FieldLayout $existing = null): FieldLayout
    {
        $this->validateFieldReferences($config);
        $config['type'] = $elementType;

        if ($existing) {
            $config['id'] = $existing->id;
            $config['uid'] = $existing->uid;
        }

        return $this->fields->createLayout($config);
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
