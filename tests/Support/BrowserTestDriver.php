<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

final readonly class BrowserTestDriver
{
    /**
     * Create the appropriate test driver based on the TEST_DRIVER env var.
     */
    public static function detect(): TestDriver
    {
        return match ((string) env('TEST_DRIVER', 'laravel')) {
            'ddev' => new DdevTestDriver,
            default => new LaravelTestDriver,
        };
    }
}
