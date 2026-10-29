<?php

namespace craft\mcp\events;

use yii\base\Event;

/**
 * Lets plugins add to the instructions the authenticated and stdio MCP servers send every client when it connects.
 *
 * Keep additions short. Every user receives them, including users without control panel access.
 *
 * @since 5.12.0
 */
class CollectingServerInstructions extends Event
{
    /** @var string[] */
    public array $instructions = [];
}
