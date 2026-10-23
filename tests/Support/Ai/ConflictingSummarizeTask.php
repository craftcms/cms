<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Ai\Task;

class ConflictingSummarizeTask extends Task
{
    public static function handle(): string
    {
        return 'test:summarize';
    }

    public static function label(): string
    {
        return 'Summarize again';
    }

    public static function modality(): Modality
    {
        return Modality::Text;
    }
}
