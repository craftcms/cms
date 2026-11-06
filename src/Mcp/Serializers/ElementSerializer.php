<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Serializers;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\McpOutputFieldInterface;
use CraftCms\Cms\Mcp\Events\ElementSerializing;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\McpOutputContext;
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
        'description' => 'Custom-field handles. [] returns metadata; null returns all non-nested fields.',
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

        $layout = $element->getFieldLayout();
        // The fields the user can see in the element’s form, as its layout’s conditions allow.
        $customFields = $public ? $layout?->getCustomFields() : $layout?->getVisibleCustomFields($element);

        foreach ($customFields ?? [] as $field) {
            if ($fields !== null && ! in_array($field->handle, $fields, true)) {
                continue;
            }

            $value = $element->getFieldValue($field->handle);

            if ($field instanceof McpOutputFieldInterface) {
                $value = $field->getMcpValue($value, $element, new McpOutputContext(public: $public, requested: $fields !== null));
                $isNested = $this->isNestedValue($value);
            } else {
                // Container fields like CKEditor can hold nested elements without their value being elements
                $isNested = $field instanceof ElementContainerFieldInterface && $this->isNestedValue($value);

                if (! $isNested) {
                    $value = $field->serializeValue($value, $element);
                }
            }

            if ($fields === null && $isNested) {
                continue;
            }

            if ($isNested) {
                $value = $this->nestedValue($value, $remaining, $serializeNested);
            }

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
     * Returns whether a field value holds nested elements.
     */
    private function isNestedValue(mixed $value): bool
    {
        return $value instanceof ElementInterface
            || $value instanceof ElementQueryInterface
            || $value instanceof ElementCollection;
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
