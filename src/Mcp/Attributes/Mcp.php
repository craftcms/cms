<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Attributes;

use Attribute;
use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;

/**
 * Controls how the `elements.*` MCP tools expose an element property or query param.
 *
 * Place it alongside an {@see ElementProperty} or {@see ElementQueryParam} attribute. Writable properties that an element
 * type declares are accepted by `elements.create` and `elements.update` unless this attribute says otherwise. Query
 * params only use its hint.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class Mcp
{
    /**
     * @param  bool  $create  Whether `elements.create` accepts the property.
     * @param  bool  $update  Whether `elements.update` and `elements.validate` accept the property.
     * @param  string|null  $hint  Guidance for MCP clients, appended to the property’s or param’s description.
     */
    public function __construct(
        public readonly bool $create = true,
        public readonly bool $update = true,
        public readonly ?string $hint = null,
    ) {}
}
