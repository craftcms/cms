<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\McpOutputFieldInterface;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Mcp\McpOutputContext;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;

/**
 * @param  list<FieldInterface>  $fields
 * @param  array<string, mixed>  $values
 */
function mcpSerializerEntry(array $fields, array $values): Entry
{
    $layout = new class($fields) extends FieldLayout
    {
        /** @param list<FieldInterface> $layoutFields */
        public function __construct(private readonly array $layoutFields) {}

        public function getCustomFields(): array
        {
            return $this->layoutFields;
        }

        public function getVisibleCustomFields(ElementInterface $element): array
        {
            return $this->layoutFields;
        }
    };

    return new class($layout, $values) extends Entry
    {
        /** @param array<string, mixed> $values */
        public function __construct(private readonly FieldLayout $layout, private array $values) {}

        public function getFieldLayout(): FieldLayout
        {
            return $this->layout;
        }

        public function getFieldValue(string $fieldHandle): mixed
        {
            return $this->values[$fieldHandle] ?? null;
        }
    };
}

function mcpSerializerNestedEntry(int $id): Entry
{
    return new class($id) extends Entry
    {
        public function __construct(int $id)
        {
            $this->id = $id;
        }
    };
}

class McpOutputTestField extends PlainText implements McpOutputFieldInterface
{
    public function getMcpValue(mixed $value, ElementInterface $element, McpOutputContext $context): mixed
    {
        return $value instanceof ElementCollection ? $value : ['value' => $value, 'public' => $context->public, 'requested' => $context->requested];
    }
}

it('lets fields control their MCP output', function (): void {
    $element = mcpSerializerEntry(
        [new Lightswitch(['handle' => 'toggle']), new McpOutputTestField(['handle' => 'custom'])],
        ['toggle' => true, 'custom' => 'raw'],
    );
    $serializer = app(ElementSerializer::class);

    expect($serializer->serialize($element, data: []))->toBe([
        'toggle' => true,
        'custom' => ['value' => 'raw', 'public' => false, 'requested' => false],
    ])
        ->and($serializer->serialize($element, data: [], public: true, fields: ['custom']))
        ->toBe(['custom' => ['value' => 'raw', 'public' => true, 'requested' => true]]);
});

it('expands elements that fields return as nested elements', function (): void {
    $element = mcpSerializerEntry(
        [new McpOutputTestField(['handle' => 'related'])],
        ['related' => ElementCollection::make([mcpSerializerNestedEntry(1), mcpSerializerNestedEntry(2)])],
    );
    $serializeNested = fn (ElementInterface $nested): ?array => $nested->id === 1 ? ['id' => $nested->id] : null;
    $serializer = app(ElementSerializer::class);

    expect($serializer->serialize($element, data: [], fields: ['related'], serializeNested: $serializeNested))
        ->toBe(['related' => [['id' => 1]]])
        ->and($serializer->serialize($element, data: [], serializeNested: $serializeNested))->toBe([]);
});

it('serializes container field values that are not elements, such as CKEditor content', function (): void {
    // A container field whose value holds nested elements without being elements itself, as CKEditor fields do
    $field = new class(['handle' => 'body']) extends Matrix
    {
        public function serializeValue(mixed $value, ?ElementInterface $element): array
        {
            return ['html' => "<p>$value</p>"];
        }
    };
    $element = mcpSerializerEntry([$field], ['body' => 'Hello']);
    $serializer = app(ElementSerializer::class);

    expect($serializer->serialize($element, data: []))->toBe(['body' => ['html' => '<p>Hello</p>']])
        ->and($serializer->serialize($element, data: [], fields: ['body']))->toBe(['body' => ['html' => '<p>Hello</p>']]);
});
