<?php

declare(strict_types=1);

namespace craft\mcp\attributes;

use Attribute;

/**
 * Marks an MCP capability as eligible for the public server.
 *
 * Public discovery still requires explicit approval in the MCP settings.
 *
 * @since 5.12.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class PublicMcp
{
}
