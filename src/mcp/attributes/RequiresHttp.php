<?php

declare(strict_types=1);

namespace craft\mcp\attributes;

use Attribute;

/**
 * Marks an MCP capability that only works over HTTP, such as one that returns URLs authenticated by the MCP access token.
 *
 * The stdio server started by `php craft mcp:serve` does not offer these capabilities.
 *
 * @since 5.12.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class RequiresHttp
{
}
