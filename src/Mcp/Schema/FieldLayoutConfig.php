<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Schema;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * @since 6.0.0
 */
readonly class FieldLayoutConfig
{
    public const array Schema = [
        'type' => 'object',
        'description' => 'Native Craft field layout config. Create fields before referencing their UIDs.',
        'additionalProperties' => true,
    ];

    public const array NullableSchema = [
        ...self::Schema,
        'type' => ['object', 'null'],
        'description' => 'Native Craft field layout config. Pass null to clear the layout.',
    ];

    public function __construct(private Fields $fields) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  class-string<ElementInterface>  $elementType
     */
    public function make(array $config, string $elementType, ?FieldLayout $existing = null): FieldLayout
    {
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
}
