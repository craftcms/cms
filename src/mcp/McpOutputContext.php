<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mcp;

/**
 * McpOutputContext describes the context a field value is being returned in by MCP tools.
 *
 * - `public` is whether the value is being returned by the public MCP server.
 * - `requested` is whether the field was explicitly requested via the `fields` argument,
 *   rather than included because all fields were requested.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
final readonly class McpOutputContext
{
    /**
     * Constructor.
     *
     * @param bool $public
     * @param bool $requested
     */
    public function __construct(
        public bool $public = false,
        public bool $requested = false,
    ) {
    }
}
