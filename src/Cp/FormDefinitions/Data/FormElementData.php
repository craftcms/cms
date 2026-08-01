<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\Optional;

readonly class FormElementData implements JsonSerializable
{
    /**
     * @param  string  $type  Stable Form Element Type.
     * @param  ?string  $key  Stable sibling reconciliation key.
     * @param  ?string  $name  Local Input Name.
     * @param  ?int  $width  Percentage width.
     * @param  array<string, mixed>|null  $props  Type-specific renderer configuration.
     * @param  array<string, mixed>|null  $attributes  Trusted renderer attributes.
     * @param  list<FormElementData>|null  $children  Ordered child elements.
     * @param  ?VisibilityConditionData  $visibleWhen  Presentation-only visibility predicate.
     * @param  ?PluginData  $plugin  Derived plugin ownership for diagnostics.
     */
    public function __construct(
        public string $type,
        #[Optional]
        public ?string $key = null,
        #[Optional]
        public ?string $name = null,
        #[Optional]
        public ?int $width = null,
        #[Optional]
        #[LiteralTypeScriptType(
            'Record<string, %JsonValue%>',
            references: ['JsonValue' => JsonValue::class],
        )]
        public ?array $props = null,
        #[Optional]
        #[LiteralTypeScriptType(
            'Record<string, %JsonValue%>',
            references: ['JsonValue' => JsonValue::class],
        )]
        public ?array $attributes = null,
        #[Optional]
        public ?array $children = null,
        #[Optional]
        public ?VisibilityConditionData $visibleWhen = null,
        #[Optional]
        public ?PluginData $plugin = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type,
            'key' => $this->key,
            'name' => $this->name,
            'width' => $this->width,
            'props' => $this->props,
            'attributes' => $this->attributes,
            'children' => $this->children === null
                ? null
                : array_map(
                    fn (FormElementData $element): array => $element->jsonSerialize(),
                    $this->children,
                ),
            'visibleWhen' => $this->visibleWhen?->jsonSerialize(),
            'plugin' => $this->plugin?->jsonSerialize(),
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @param callable(string): ?PluginData $ownership */
    public function withPluginOwnership(callable $ownership): self
    {
        return new self(
            type: $this->type,
            key: $this->key,
            name: $this->name,
            width: $this->width,
            props: $this->props,
            attributes: $this->attributes,
            children: $this->children === null
                ? null
                : array_map(
                    fn (self $child): self => $child->withPluginOwnership($ownership),
                    $this->children,
                ),
            visibleWhen: $this->visibleWhen,
            plugin: $ownership($this->type),
        );
    }

    public function withInputNamePrefix(string $prefix): self
    {
        return new self(
            type: $this->type,
            key: $this->key,
            name: $this->name === null ? null : "{$prefix}.{$this->name}",
            width: $this->width,
            props: $this->props,
            attributes: $this->attributes,
            children: $this->children === null
                ? null
                : array_map(
                    fn (self $child): self => $child->withInputNamePrefix($prefix),
                    $this->children,
                ),
            visibleWhen: $this->visibleWhen?->withInputNamePrefix($prefix),
            plugin: $this->plugin,
        );
    }
}
