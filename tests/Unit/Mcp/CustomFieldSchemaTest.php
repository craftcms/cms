<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Mcp\Contracts\ProvidesInputSchema;
use CraftCms\Cms\Mcp\CustomFieldSchema;

it('uses field-provided MCP input schemas', function () {
    $field = new class(['name' => 'Variant', 'handle' => 'variant', 'instructions' => 'Choose a supported variant.', 'uid' => '86c62b1e-65a9-4835-896b-265cc7aa41d8']) extends PlainText implements ProvidesInputSchema
    {
        public function getMcpInputSchema(): array
        {
            return [
                'type' => 'string',
                'enum' => ['primary', 'secondary'],
            ];
        }
    };
    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($field)->required()));
    $entry = new class($layout) extends Entry
    {
        public function __construct(private readonly FieldLayout $layout)
        {
            parent::__construct();
        }

        public function getFieldLayout(): FieldLayout
        {
            return $this->layout;
        }
    };

    expect(app(CustomFieldSchema::class)->forElement($entry))->toMatchArray([
        'properties' => [
            'variant' => [
                'type' => 'string',
                'enum' => ['primary', 'secondary'],
                'title' => 'Variant',
                'description' => 'Choose a supported variant.',
            ],
        ],
        'required' => ['variant'],
        'additionalProperties' => false,
    ]);
});
