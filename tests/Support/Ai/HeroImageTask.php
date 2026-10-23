<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Ai\Task;

class HeroImageTask extends Task
{
    public static function handle(): string
    {
        return 'test:hero';
    }

    public static function label(): string
    {
        return 'Hero image';
    }

    public static function modality(): Modality
    {
        return Modality::Image;
    }
}
