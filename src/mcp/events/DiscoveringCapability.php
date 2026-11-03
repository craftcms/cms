<?php

namespace craft\mcp\events;

use craft\elements\User;
use Mcp\Capability\Registry\ElementReference;
use yii\base\Event;

/**
 * Lets plugins hide a tool, resource, or prompt from the user the authenticated or stdio MCP server acts as.
 *
 * The event only fires for capabilities that already passed Craft’s own permission and token scope checks.
 *
 * @see \craft\services\Mcp::EVENT_DISCOVERING_CAPABILITY
 * @since 5.12.0
 */
class DiscoveringCapability extends Event
{
    public ElementReference $reference;
    public ?User $user = null;
    public bool $allowed = true;
}
