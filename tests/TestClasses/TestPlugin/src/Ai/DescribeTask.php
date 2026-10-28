<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Ai;

use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Ai\Task;

class DescribeTask extends Task
{
    public static function handle(): string
    {
        return 'test-plugin:describe';
    }

    public static function label(): string
    {
        return 'Describe';
    }

    public static function description(): ?string
    {
        return 'Writes <descriptions> of things.';
    }

    public static function modality(): Modality
    {
        return Modality::Text;
    }
}
