<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

/**
 * Expands the results of the AI assistant’s display tools for the chat, as {@see AssistantStreamProtocol} streams them
 * and {@see Transcript} loads them: the model gets a compact result, and the chat what it shows.
 *
 * @internal
 *
 * @since 6.0.0
 */
class ToolDisplays
{
    /**
     * Returns a successful tool call’s result as the chat shows it.
     */
    public static function forDisplay(string $tool, string $result): string
    {
        return match ($tool) {
            ShowElements::NAME => app(ShowElements::class)->forDisplay($result),
            ShowComponents::NAME => app(ShowComponents::class)->forDisplay($result),
            default => $result,
        };
    }
}
