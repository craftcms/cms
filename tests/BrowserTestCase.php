<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use CraftCms\Cms\Support\CmsAssets;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Once;
use Override;

/**
 * Base class for Pest browser tests.
 *
 * Pest serves the application from inside the test process, so tests share the
 * database transaction with the pages they visit. The control panel needs its
 * built assets, so they're linked into Testbench's public directory, and Vite is
 * enabled again after the parent class disables it.
 */
class BrowserTestCase extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $assetsPath = public_path('vendor/craft');

        if (! file_exists($assetsPath)) {
            File::ensureDirectoryExists(dirname($assetsPath));
            File::link(CmsAssets::resourcesPath(), $assetsPath);
        }

        $this->withVite();

        // Testbench reports a console app, which builds URLs from the primary site rather than the request. The
        // browser sends real requests, so they're handled as such once the setup above has run as usual.
        (fn () => $this->isRunningInConsole = false)->call($this->app);

        // Every request shares this app, so values memoized for one request mustn't carry over to the next. That
        // includes the request's context (such as its template mode) and the controller, which its route keeps
        // along with the request that was injected into it.
        $this->app->terminating(function () {
            Once::flush();
            Context::flush();
            $this->app->make(Router::class)->current()?->flushController();
        });
    }
}
