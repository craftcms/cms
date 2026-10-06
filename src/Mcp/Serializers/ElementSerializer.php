<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Serializers;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Mcp\Events\ElementSerializing;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
class ElementSerializer
{
    public const array FieldsSchema = [
        'type' => ['array', 'null'],
        'items' => ['type' => 'string'],
        'description' => 'Custom-field handles to return alongside element metadata. [] returns metadata only; null returns all non-nested custom fields. Explicitly selected nested fields return one level of element records, without further nested fields. At most 100 nested elements may be expanded per record; query larger collections separately. On the admin server, discover handles with entries.field-schema, assets.field-schema, addresses.field-schema or users.field-schema for the matching element type; these require permission to save the element. For drafts and revisions, use the canonical element ID. Admins can read craft://field-layouts/entry-types/{entryType} for an entry layout, using the entry type ID, UID or handle.',
    ];

    private const int MaxNestedElements = 100;

    public function __construct(private readonly McpActor $actor) {}

    /**
     * @param  array<string, mixed>|null  $data
     * @param  list<string>|null  $fields
     * @param  Closure(ElementInterface): ?array<string, mixed>|null  $serializeNested
     * @return array<string, mixed>
     */
    public function serialize(
        ElementInterface $element,
        ?array $data = null,
        bool $public = false,
        bool $filterNulls = true,
        ?array $fields = null,
        ?Closure $serializeNested = null,
    ): array {
        $data ??= ['type' => $element::class, ...$element->toArray($element->attributes())];
        $remaining = self::MaxNestedElements;
        $serializeNested ??= fn (ElementInterface $nested): ?array => ! $public && Gate::forUser($this->actor->user())->allows('view', $nested)
            ? $this->serialize($nested)
            : null;

        foreach ($element->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            if ($fields !== null && ! in_array($field->handle, $fields, true)) {
                continue;
            }

            if ($fields === null && $field instanceof ElementContainerFieldInterface) {
                continue;
            }

            $value = $field instanceof ElementContainerFieldInterface
                ? $this->nestedValue($element->getFieldValue($field->handle), $remaining, $serializeNested)
                : $field->serializeValue($element->getFieldValue($field->handle), $element);

            $data += [$field->handle => $value];
        }

        $event = new ElementSerializing(
            element: $element,
            data: $data,
            public: $public,
        );

        event($event);

        return $filterNulls ? Arr::whereNotNull($event->data) : $event->data;
    }

    /**
     * @param  Closure(ElementInterface): ?array<string, mixed>  $serializeNested
     * @return array<array-key, mixed>|null
     */
    private function nestedValue(mixed $value, int &$remaining, Closure $serializeNested): ?array
    {
        $single = $value instanceof ElementInterface;
        $elements = match (true) {
            $single => [$value],
            $value instanceof ElementQueryInterface => (clone $value)->limit($remaining + 1)->all(),
            $value instanceof ElementCollection => $value->take($remaining + 1)->all(),
            default => [],
        };

        if (count($elements) > $remaining) {
            throw new ToolCallException('Too many nested elements to expand. Query the nested elements separately with pagination.');
        }

        $remaining -= count($elements);
        $data = [];

        foreach ($elements as $element) {
            $serialized = $serializeNested($element);

            if ($serialized !== null) {
                $data[] = $serialized;
            }
        }

        return $single ? ($data[0] ?? null) : $data;
    }
}
