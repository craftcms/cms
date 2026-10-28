<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\InputSchemaFieldInterface;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Mcp\Schema\CustomFieldSchema;
use Mcp\Exception\ToolCallException;

it('uses field-provided MCP input schemas', function () {
    $field = new class(['name' => 'Variant', 'handle' => 'variant', 'instructions' => 'Choose a supported variant.', 'uid' => '86c62b1e-65a9-4835-896b-265cc7aa41d8']) extends PlainText implements InputSchemaFieldInterface
    {
        public function getInputSchema(): array
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

it('rejects a time it can’t parse, rather than clearing the field, but lets other fields be cleared', function () {
    $time = new Time(['name' => 'Opens at', 'handle' => 'opens', 'uid' => '5f0b6c1e-7c3d-4e8a-9a51-2d4b8c9e0f17']);
    $country = new Country(['name' => 'Country', 'handle' => 'country', 'uid' => '0c6f4a8e-2b1d-4f7a-8e3c-9d5b7a1f2e46']);
    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($time))->add(CustomField::make($country)));
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
    $schema = app(CustomFieldSchema::class);

    expect($schema->prepareValues($entry, ['opens' => '2:30 PM']))->toBe(['opens' => '2:30 PM'])
        ->and($schema->prepareValues($entry, ['country' => '__blank__']))->toBe(['country' => '__blank__'])
        ->and(fn () => $schema->prepareValues($entry, ['opens' => 'half past nine']))
        ->toThrow(ToolCallException::class, 'opens');
});

it('keeps a date or time format with the field’s instructions', function () {
    $field = new Time(['name' => 'Opens at', 'handle' => 'opens', 'instructions' => 'When the doors open.', 'uid' => '5f0b6c1e-7c3d-4e8a-9a51-2d4b8c9e0f17']);
    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(CustomField::make($field)));
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

    expect(app(CustomFieldSchema::class)->forElement($entry)['properties']['opens']['description'])
        ->toBe("A time of day, such as 14:30 or 2:30 PM.\n\nWhen the doors open.");
});
