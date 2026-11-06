<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\mcp\McpOutputContext;

/**
 * McpOutputFieldInterface defines the common interface to be implemented by field classes
 * that control how their values are returned by MCP tools.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 * @mixin Field
 */
interface McpOutputFieldInterface extends FieldInterface
{
    /**
     * Returns the field’s value as MCP tools should return it.
     *
     * Elements, element queries, and element collections anywhere in the returned value,
     * including within arrays, are expanded or summarized as nested elements, subject to
     * the user’s permissions and the nested element limit.
     *
     * @param mixed $value
     * @param ElementInterface $element
     * @param McpOutputContext $context
     * @return mixed
     */
    public function getMcpValue(mixed $value, ElementInterface $element, McpOutputContext $context): mixed;
}
