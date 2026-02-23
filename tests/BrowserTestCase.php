<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use CraftCms\Cms\Tests\Support\BrowserTestDriver;
use CraftCms\Cms\Tests\Support\TestDriver;
use Dotenv\Dotenv;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

/**
 * Lightweight test case for browser tests that run against an external server.
 *
 * Set BROWSER_TEST_URL in tests/.env to point at your running instance
 * (e.g. https://craft6-dev.ddev.site).
 *
 * Set TEST_DRIVER to "ddev" or "laravel" (default) to control how artisan
 * commands are executed during browser tests.
 */
class BrowserTestCase extends Orchestra
{
    use WithWorkbench;

    protected TestDriver $testDriver;

    /**
     * Build a full URL for a browser test by prepending BROWSER_TEST_URL.
     */
    public function browserUrl(string $path = '/'): string
    {
        $base = rtrim((string) env('BROWSER_TEST_URL'), '/');

        if ($base === '') {
            return $path;
        }

        return $base.'/'.ltrim($path, '/');
    }

    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        if (! file_exists(__DIR__.'/.env')) {
            return;
        }

        $dotenv = Dotenv::createMutable(__DIR__);
        $dotenv->load();
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->testDriver = BrowserTestDriver::detect();
    }
}
