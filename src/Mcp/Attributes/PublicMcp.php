<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Attributes;

use Attribute;

/**
 * Marks an MCP capability as eligible for the public server.
 *
 * Public discovery still requires explicit approval in the MCP settings.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class PublicMcp {}
