<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Tasks;

use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Task;
use CraftCms\Cms\Support\Facades\Ai;
use CraftCms\Cms\User\Contracts\CraftUser;

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
     * Whether the current user can use the assistant, and it can run. Remembered for the request, as every field in
     * an element editor asks.
     */
    public static function isAvailable(): bool
    {
        return self::isAvailableFor(currentUser());
    }

    private static function isAvailableFor(?CraftUser $user): bool
    {
        return once(static fn (): bool => $user?->can(self::PERMISSION) === true && Ai::available(self::handle()));
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

    /**
     * With vision, the assistant sees the image of an asset open in the editor.
     */
    public static function optionalCapabilities(): array
    {
        return [Capability::Vision];
    }
}
