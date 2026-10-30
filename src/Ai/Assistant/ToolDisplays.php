<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Support\Json;

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
            'LoadSkill' => self::loadedSkill($result),
            default => $result,
        };
    }

    /**
     * Returns the title of the skill a `LoadSkill` call loaded, as `{"skill": "…"}`, rather than its instructions,
     * which the chat doesn’t show. Its name ends with its ID ({@see AgentSkill::name()}). Anything else, such as the
     * error for a skill that doesn’t exist, is returned as it is.
     */
    private static function loadedSkill(string $result): string
    {
        if (! preg_match('/^<skill_content name="[^"]*-(\d+)">/', $result, $match)) {
            return $result;
        }

        return Json::encode(['skill' => AgentSkill::query()->find((int) $match[1])?->title]);
    }
}
