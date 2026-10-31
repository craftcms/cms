<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Events;

/**
 * Append plugin guidance to $instructions when the authenticated and stdio servers collect the instructions they
 * send every client when it connects.
 *
 * Keep additions short. Every user receives them, including users without control panel access.
 *
 * @since 6.0.0
 */
class CollectingServerInstructions
{
    /** @param list<string> $instructions */
    public function __construct(public array $instructions = []) {}
}
