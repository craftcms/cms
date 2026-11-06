<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

/**
 * Describes the context a field value is being returned in by MCP tools.
 *
 * @since 6.0.0
 */
final readonly class McpOutputContext
{
    /**
     * @param  bool  $public  Whether the value is being returned by the public MCP server.
     * @param  bool  $requested  Whether the field was explicitly requested via the `fields` argument, rather than included because all fields were requested.
     */
    public function __construct(
        public bool $public = false,
        public bool $requested = false,
    ) {}
}
