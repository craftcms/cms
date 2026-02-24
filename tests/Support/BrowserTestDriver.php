<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use Dotenv\Dotenv;

final readonly class BrowserTestDriver
{
    /**
     * Create the appropriate test driver based on the TEST_DRIVER env var.
     */
    public static function detect(): TestDriver
    {
        self::loadEnv();

        return match ((string) env('TEST_DRIVER', 'laravel')) {
            'ddev' => new DdevTestDriver,
            default => new LaravelTestDriver,
        };
    }

    /**
     * Ensure tests/.env is loaded (needed when called before setUp).
     */
    private static function loadEnv(): void
    {
        if (env('TEST_DRIVER') !== null) {
            return;
        }

        $envPath = dirname(__DIR__);

        if (file_exists($envPath.'/.env')) {
            Dotenv::createMutable($envPath)->load();
        }
    }
}
