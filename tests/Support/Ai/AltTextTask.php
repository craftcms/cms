<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Task;

class AltTextTask extends Task
{
    public static function handle(): string
    {
        return 'test:altText';
    }

    public static function label(): string
    {
        return 'Alt text';
    }

    public static function requirements(): array
    {
        return [Capability::Vision];
    }
}
