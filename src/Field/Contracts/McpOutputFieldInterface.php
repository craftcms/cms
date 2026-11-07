<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\McpOutputContext;

/**
 * McpOutputFieldInterface defines the common interface to be implemented by field classes
 * that control how their values are returned by MCP tools.
 *
 * @since 6.0.0
 */
interface McpOutputFieldInterface extends FieldInterface
{
    /**
     * Returns the field’s value as MCP tools should return it.
     *
     * Elements, element queries, and element collections are expanded as nested elements,
     * subject to the user’s permissions and the nested element limit.
     */
    public function getMcpValue(mixed $value, ElementInterface $element, McpOutputContext $context): mixed;
}
