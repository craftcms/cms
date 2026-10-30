<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use Laravel\Ai\Streaming\Events\ToolResult;
use Laravel\Ai\Streaming\Protocols\AgentUserInteractionProtocol;

/**
 * Streams the AI assistant’s turns as AG-UI events, as `laravel/ai` does, with the results of the assistant’s own
 * display tools expanded for the chat: the model gets a compact result, and the chat what it shows.
 *
 * @internal
 *
 * @since 6.0.0
 */
class AssistantStreamProtocol extends AgentUserInteractionProtocol
{
    #[\Override]
    protected function toolResultContent(ToolResult $event): string
    {
        $content = parent::toolResultContent($event);

        return $event->successful ? ToolDisplays::forDisplay($event->toolResult->name, $content) : $content;
    }
}
