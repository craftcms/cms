<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Tasks;

use CraftCms\Cms\Ai\Task;

use function CraftCms\Cms\t;

/**
 * Writes, edits, and reviews field values from a prompt in the element editor (“Ask AI”).
 *
 * @since 6.0.0
 */
class AskAi extends Task
{
    public static function handle(): string
    {
        return 'craft:askAi';
    }

    public static function label(): string
    {
        return t('Ask AI');
    }

    public static function description(): ?string
    {
        return t('Writes, edits, and reviews field values from a prompt in the element editor. Needs a model with structured output.');
    }
}
