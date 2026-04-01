<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader;

class TestElementEagerLoaderDraftsState
{
    public static int $calls = 0;

    public static function reset(): void
    {
        self::$calls = 0;
    }
}
