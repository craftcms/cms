<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Events;

/**
 * Append plugin guidance to $instructions when info.get returns the full admin MCP instructions.
 * Core and general config instructions are retained separately.
 *
 * @since 6.0.0
 */
class CollectingAdminInstructions
{
    /** @param list<string> $instructions */
    public function __construct(public array $instructions = []) {}
}
