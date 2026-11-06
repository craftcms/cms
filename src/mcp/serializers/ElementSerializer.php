<?php

declare(strict_types=1);

namespace craft\mcp\serializers;

use Closure;
use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\ElementInterface;
use craft\base\McpOutputFieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\mcp\Authorization;
use craft\mcp\elements\ElementAdapterRegistry;
use craft\mcp\events\ElementSerializing;
use craft\mcp\McpActor;
use craft\mcp\McpOutputContext;
use Illuminate\Support\Arr;
use Mcp\Exception\ToolCallException;

/**
 * @since 5.12.0
 */
class ElementSerializer extends \yii\base\Component
{
    public const EVENT_SERIALIZING = 'serializing';

    public const FieldsSchema = [
        'type' => ['array', 'null'],
        'items' => ['type' => 'string'],
        'description' => 'Custom-field handles. [] returns metadata; null returns all non-nested fields. Dot paths, such as "blocks.innerBlocks", also include nested fields of nested elements as summaries ({type, id, status}).',
    ];

    private const MaxNestedElements = 100;

    private const NestedOmit = 'omit';
    private const NestedExpand = 'expand';
    private const NestedSummarize = 'summarize';

    /**
     * Nested elements that can still be serialized within the current top-level element.
     */
    private ?int $remaining = null;

    public function __construct(
        private readonly McpActor $actor,
        array $config = [],
        private ?ElementAdapterRegistry $adapters = null,
    ) {
        parent::__construct($config);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  list<string>|null  $fields
     * @param  Closure(ElementInterface, list<string>, bool): ?array<string, mixed>|null  $serializeNested
     * @param list<string> $nestedPaths
     * @return array<string, mixed>
     */
    public function serialize(
        ElementInterface $element,
        ?array $data = null,
        bool $public = false,
        bool $filterNulls = true,
        ?array $fields = null,
        ?Closure $serializeNested = null,
        array $nestedPaths = [],
    ): array {
        return $this->withBudget(fn() => $this->serializeInternal($element, $data, $public, $filterNulls, $fields, $serializeNested, $nestedPaths));
    }

    /**
     * Serializes the nested elements within a value, such as an element type’s native nested field.
     *
     * Elements are summarized as `{type, id, status}`, or fully serialized if their element type
     * can’t be fetched through MCP. Elements the user can’t view are omitted.
     *
     * @param mixed $value
     * @param list<string> $paths
     * @param bool $public
     * @param Closure(ElementInterface, list<string>, bool): ?array<string, mixed>|null $serializeNested
     * @return mixed
     */
    public function serializeNestedValue(
        mixed $value,
        array $paths = [],
        bool $public = false,
        ?Closure $serializeNested = null,
    ): mixed {
        $serializeNested ??= $this->defaultNestedSerializer($public);

        return $this->withBudget(fn() => $this->resolveNested($value, self::NestedSummarize, $paths, $serializeNested));
    }

    /**
     * Returns a summary of an element.
     *
     * @param ElementInterface $element
     * @return array{type: class-string<ElementInterface>, id: int|null, status: string|null}
     */
    public function summarize(ElementInterface $element): array
    {
        return [
            'type' => $element::class,
            'id' => $element->getId(),
            'status' => $element->getStatus(),
        ];
    }

    private function serializeInternal(
        ElementInterface $element,
        ?array $data,
        bool $public,
        bool $filterNulls,
        ?array $fields,
        ?Closure $serializeNested,
        array $nestedPaths,
    ): array {
        if ($data === null && $element instanceof \craft\elements\User) {
            $data = ['type' => $element::class, ...$element->toArray([
                'id', 'uid', 'username', 'email', 'firstName', 'lastName', 'fullName',
                'admin', 'active', 'pending', 'locked', 'suspended', 'status',
                'photoId', 'affiliatedSiteId', 'lastLoginDate', 'dateCreated', 'dateUpdated',
            ])];
        }

        $data ??= ['type' => $element::class, ...$element->toArray($element->attributes())];
        $serializeNested ??= $this->defaultNestedSerializer($public);
        $requested = $fields !== null ? $this->parsePaths($fields) : null;
        $nested = $this->parsePaths($nestedPaths);

        $layout = $element->getFieldLayout();
        // The fields the user can see in the element’s form, as its layout’s conditions allow.
        $customFields = $public ? $layout?->getCustomFields() : $layout?->getVisibleCustomFields($element);

        foreach ($customFields ?? [] as $field) {
            $handle = $field->handle;

            if ($requested !== null && !isset($requested[$handle])) {
                continue;
            }

            $value = $element->getFieldValue($handle);

            // Nested elements are expanded for requested fields, summarized for requested dot paths, and omitted otherwise
            $mode = match (true) {
                $requested !== null => self::NestedExpand,
                isset($nested[$handle]) => self::NestedSummarize,
                default => self::NestedOmit,
            };

            if ($field instanceof McpOutputFieldInterface) {
                $value = $field->getMcpValue($value, $element, new McpOutputContext(
                    public: $public,
                    requested: $mode !== self::NestedOmit,
                ));
            } elseif (!$field instanceof ElementContainerFieldInterface || !$this->isNestedValue($value)) {
                // Container fields like CKEditor can hold nested elements without their value being elements
                $data += [$handle => $field->serializeValue($value, $element)];
                continue;
            }

            if ($mode === self::NestedOmit && $this->isNestedValue($value)) {
                continue;
            }

            $hidden = false;
            $resolved = $this->resolveNested($value, $mode, $requested[$handle] ?? $nested[$handle] ?? [], $serializeNested, $hidden);
            $data += [$handle => $hidden ? null : $resolved];
        }

        $event = new ElementSerializing([
            'element' => $element,
            'serializedData' => $data,
            'public' => $public,
        ]);

        $this->trigger(self::EVENT_SERIALIZING, $event);

        return $filterNulls ? Arr::whereNotNull($event->serializedData) : $event->serializedData;
    }

    /**
     * Runs a callback with a single nested element budget shared by everything it serializes.
     *
     * Element adapters can use this to apply one budget to an element’s custom fields and its native nested fields.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function withBudget(callable $callback): mixed
    {
        if ($this->remaining !== null) {
            return $callback();
        }

        $this->remaining = self::MaxNestedElements;

        try {
            return $callback();
        } finally {
            $this->remaining = null;
        }
    }

    /**
     * Returns the default callback for serializing a nested element the user can view.
     */
    private function defaultNestedSerializer(bool $public): Closure
    {
        return fn(ElementInterface $nested, array $paths = [], bool $summary = false): ?array => !$public && Authorization::allows($this->actor->user(), 'view', $nested)
            ? ($summary ? $this->summarize($nested) : $this->serialize($nested, nestedPaths: $paths))
            : null;
    }

    /**
     * Groups dot paths by their first handle, mapping each handle to its child paths.
     */
    private function parsePaths(array $paths): array
    {
        $parsed = [];

        foreach ($paths as $path) {
            [$handle, $child] = array_pad(explode('.', $path, 2), 2, null);
            $parsed[$handle] ??= [];

            if ($child !== null && $child !== '') {
                $parsed[$handle][] = $child;
            }
        }

        return $parsed;
    }

    /**
     * Returns whether a field value holds nested elements.
     */
    private function isNestedValue(mixed $value): bool
    {
        return $value instanceof ElementInterface ||
            $value instanceof ElementQueryInterface ||
            $value instanceof ElementCollection;
    }

    /**
     * Resolves nested elements within a value, including within arrays.
     *
     * Elements that resolve to `null` (because they aren’t visible) are removed from lists,
     * along with any array within a list that contains one, so nothing reveals where they were.
     */
    private function resolveNested(mixed $value, string $mode, array $paths, Closure $serializeNested, bool &$hidden = false): mixed
    {
        if ($this->isNestedValue($value)) {
            $resolved = $this->nestedValue($value, $mode === self::NestedSummarize, $paths, $serializeNested);
            $hidden = $resolved === null && $value instanceof ElementInterface;

            return $resolved;
        }

        if (!is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        // A list of only elements keeps its visible ones; any other array is removed along with its hidden elements
        $isElementList = $isList && !array_filter($value, fn(mixed $item): bool => !$this->isNestedValue($item));
        $resolved = [];

        foreach ($value as $key => $item) {
            $itemHidden = false;
            $result = null;

            if ($mode === self::NestedOmit && $this->isNestedValue($item)) {
                // Omitted elements are still checked, so hidden ones remove the arrays that contain them too
                $itemHidden = $item instanceof ElementInterface && $serializeNested($item, [], true) === null;

                if (!$itemHidden) {
                    continue;
                }
            } else {
                $result = $this->resolveNested($item, $mode, $paths, $serializeNested, $itemHidden);
            }

            if ($itemHidden) {
                // Drop it from a list, or mark this array as hidden so its parent list drops it
                if (!$isList || ($this->isNestedValue($item) && !$isElementList)) {
                    $hidden = true;
                }

                continue;
            }

            $resolved[$key] = $result;
        }

        return $isList ? array_values($resolved) : $resolved;
    }

    /**
     * Serializes nested elements, summarizing those that can be fetched through MCP when requested.
     */
    private function nestedValue(mixed $value, bool $summarize, array $paths, Closure $serializeNested): ?array
    {
        $single = $value instanceof ElementInterface;
        $elements = match (true) {
            $single => [$value],
            $value instanceof ElementQueryInterface => (clone $value)->limit($this->remaining + 1)->all(),
            $value instanceof ElementCollection => $value->take($this->remaining + 1)->all(),
            default => [],
        };

        if (count($elements) > $this->remaining) {
            throw new ToolCallException('Too many nested elements to expand. Query the nested elements separately with pagination.');
        }

        $this->remaining -= count($elements);
        $data = [];

        foreach ($elements as $element) {
            $summary = $summarize && $element instanceof ElementInterface && $this->adapters()->supports($element::class);
            $serialized = $serializeNested($element, $paths, $summary);

            if ($serialized !== null) {
                $data[] = $serialized;
            }
        }

        return $single ? ($data[0] ?? null) : $data;
    }

    private function adapters(): ElementAdapterRegistry
    {
        return $this->adapters ??= Craft::$app->get('mcp')->getElementAdapterRegistry();
    }
}
