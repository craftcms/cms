<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Task;

class ResearchTask extends Task
{
    public static function handle(): string
    {
        return 'test:research';
    }

    public static function label(): string
    {
        return 'Research';
    }

    public static function requirements(): array
    {
        return [Capability::StructuredOutput, Capability::ToolCalling];
    }
}
