<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Mcp\Server\Transport\StdioTransport as BaseStdioTransport;

/**
 * The transport the `craft:mcp:serve` command serves over.
 *
 * {@see StdioEventDispatcher} handles each of its requests in a fresh request scope, as the HTTP servers do.
 *
 * @since 6.0.0
 */
class StdioTransport extends BaseStdioTransport
{
    /** The hidden context key that marks messages handled by the stdio server. */
    public const string ContextStdio = 'craft.mcp.stdio';
}
