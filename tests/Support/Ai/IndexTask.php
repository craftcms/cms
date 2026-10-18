<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Ai\Task;

class IndexTask extends Task
{
    public static function handle(): string
    {
        return 'test:index';
    }

    public static function label(): string
    {
        return 'Index';
    }

    public static function modality(): Modality
    {
        return Modality::Embeddings;
    }
}
