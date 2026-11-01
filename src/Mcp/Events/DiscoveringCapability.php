<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Events;

use CraftCms\Cms\User\Contracts\CraftUser;
use Mcp\Capability\Registry\ElementReference;

/**
 * Set $allowed to false to hide a tool, resource, or prompt from the user the authenticated or stdio MCP server acts as.
 *
 * The event only fires for capabilities that already passed Craft’s own permission and token scope checks. It can’t
 * hide capabilities approved for the public server.
 *
 * @since 6.0.0
 */
class DiscoveringCapability
{
    public function __construct(
        public readonly ElementReference $reference,
        public readonly ?CraftUser $user,
        public bool $allowed = true,
    ) {}
}
