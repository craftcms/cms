<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Attributes;

use Attribute;
use CraftCms\Cms\Edition;

/**
 * Marks an MCP capability, or a single tool, that requires a Craft edition or better.
 *
 * Neither MCP server offers it on lesser editions.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
readonly class RequiresEdition
{
    public function __construct(
        public Edition $edition,
    ) {}
}
