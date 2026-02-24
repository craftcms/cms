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

    private static bool $craftInstalled = false;

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

    /**
     * Build a full CP URL (e.g. /admin/{path}).
     */
    public function cpUrl(string $path = ''): string
    {
        $cpPath = 'admin'.($path !== '' ? '/'.ltrim($path, '/') : '');

        return $this->browserUrl($cpPath);
    }

    /**
     * Log in to the control panel via the browser and navigate to a CP page.
     */
    public function loginAndVisitCp(string $path = '', string $username = 'admin', string $password = 'craftcms2018!!'): \Pest\Browser\Api\Webpage|\Pest\Browser\Api\AwaitableWebpage
    {
        $page = $this->visit($this->cpUrl('login'))
            ->type('Username or Email', $username)
            ->type('Password', $password)
            ->press('Sign in')
            ->assertPathEndsWith('dashboard');

        if ($path !== '') {
            return $page->navigate($this->cpUrl($path));
        }

        return $page;
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

        if (! self::$craftInstalled) {
            $this->migrateDatabases();
            self::$craftInstalled = true;
        }
    }

    /**
     * Install Craft into the external server's database via the test driver
     * so the browser can log in and interact with the CP.
     */
    protected function migrateDatabases(): void
    {
        $driver = BrowserTestDriver::detect();

        // Wipe the database first
        $result = $driver->artisan('db:wipe --force');
        if ($result['exitCode'] !== 0) {
            throw new \RuntimeException('Failed to wipe database: '.implode("\n", $result['output']));
        }

        // Install Craft with a default admin user
        $browserUrl = env('BROWSER_TEST_URL', 'https://e2e.ddev.site');
        $result = $driver->artisan("craft:install --username=admin --password=craftcms2018!! --email=test@craftcms.com --siteName=\"E2E Tests\" --siteUrl={$browserUrl} --language=en_US");
        if ($result['exitCode'] !== 0) {
            throw new \RuntimeException('Failed to install Craft: '.implode("\n", $result['output']));
        }
    }
}
