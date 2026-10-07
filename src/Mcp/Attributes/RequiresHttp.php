<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Attributes;

use Attribute;

/**
 * Marks an MCP capability that only works over HTTP, such as one that returns URLs authenticated by the MCP access token.
 *
 * The stdio server started by `php craft mcp:serve` does not offer these capabilities.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class RequiresHttp {}
