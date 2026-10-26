<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Tasks;

use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Task;
use CraftCms\Cms\Support\Facades\Ai;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * The control panel’s AI assistant: a chat that can look up content and settings with Craft’s MCP tools.
 *
 * @since 6.0.0
 */
class Assistant extends Task
{
    /**
     * The user permission that lets someone use the assistant.
     */
    public const string PERMISSION = 'useAiAssistant';

    /**
     * Whether the current user can use the assistant, and it can run.
     */
    public static function isAvailable(): bool
    {
        return currentUser()?->can(self::PERMISSION) === true && Ai::available(self::handle());
    }

    public static function handle(): string
    {
        return 'craft:assistant';
    }

    public static function label(): string
    {
        return t('AI assistant');
    }

    public static function description(): ?string
    {
        return t('Answers questions in the control panel’s AI chat, looking up content and settings with Craft’s MCP tools.');
    }

    public static function requirements(): array
    {
        return [Capability::ToolCalling];
    }
}
