<?php

declare(strict_types=1);

namespace craft\mcp\elements;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use Mcp\Exception\ToolCallException;

/**
 * Exposes an element type through the `elements.*` MCP tools.
 *
 * Register adapters with {@see ElementAdapterRegistry}. Extending {@see BaseElementAdapter} is recommended.
 *
 * @since 5.12.0
 */
interface ElementAdapter
{
    /**
     * Returns the `type` value MCP clients pass to select this element type, such as `entries`.
     */
    public static function handle(): string;

    /** @return class-string<ElementInterface> */
    public static function elementType(): string;

    /**
     * Describes the criteria, attributes, and field-schema context the `elements.*` tools accept for this type.
     *
     * @return array{
     *     type: string,
     *     name: string,
     *     elementType: class-string<ElementInterface>,
     *     criteria: array<string, mixed>,
     *     createAttributes?: array<string, mixed>,
     *     updateAttributes: array<string, mixed>,
     *     fieldSchemaContext?: array<string, mixed>,
     *     duplicateModes?: list<string>,
     *     notes?: list<string>,
     * }
     */
    public function schema(): array;

    /**
     * Returns the base query for `elements.list`.
     *
     * @throws ToolCallException if the actor may not list elements of this type.
     */
    public function listQuery(User $actor): ElementQueryInterface;

    public function find(?int $id, ?string $uid, ?int $siteId): ?ElementInterface;

    public function canView(User $actor, ElementInterface $element): bool;

    public function canDelete(User $actor, ElementInterface $element): bool;

    /**
     * @param  list<string>|null  $fields  Custom-field handles to include, or `null` for all non-nested custom fields.
     * @param  bool  $summary  Whether the element is serialized as part of a list.
     * @return array<string, mixed>
     */
    public function serialize(ElementInterface $element, ?array $fields = null, bool $summary = false): array;

    /**
     * Returns the element whose custom-field schema `elements.field-schema` describes.
     *
     * @param  ElementInterface|null  $element  The existing element, or `null` for a new element described by the context.
     * @param  array<string, mixed>  $context  Values matching the `fieldSchemaContext` schema.
     */
    public function fieldSchemaElement(?ElementInterface $element, array $context, User $actor): ElementInterface;

    /**
     * @param  array<string, mixed>  $attributes  Values matching the `createAttributes` schema.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{element: array<string, mixed>}&array<string, mixed>
     */
    public function create(array $attributes, array $fields, User $actor): array;

    /**
     * @param  array<string, mixed>  $attributes  Values matching the `updateAttributes` schema.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{element: array<string, mixed>}&array<string, mixed>
     */
    public function update(ElementInterface $element, array $attributes, array $fields, User $actor): array;

    /**
     * Applies proposed changes in memory before `elements.validate` validates the element.
     *
     * @param  array<string, mixed>  $attributes  Values matching the `updateAttributes` schema.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     */
    public function prepareValidation(ElementInterface $element, array $attributes, array $fields, User $actor): void;

    /**
     * @param  string|null  $mode  One of the modes listed in `duplicateModes`, or `null` for the default.
     */
    public function duplicate(ElementInterface $element, ?string $mode): ElementInterface;
}
