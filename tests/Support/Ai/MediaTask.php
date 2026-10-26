<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Task;

class MediaTask extends Task
{
    public static function handle(): string
    {
        return 'test:media';
    }

    public static function label(): string
    {
        return 'Media';
    }

    public static function requirements(): array
    {
        return [Capability::DocumentInput, Capability::AudioInput, Capability::VideoInput];
    }
}
