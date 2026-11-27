<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\CmsAssets;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Once;
use Orchestra\Testbench\Foundation\Application;
use Override;

use function Orchestra\Testbench\default_skeleton_path;
use function Orchestra\Testbench\package_path;

/**
 * Base class for Pest browser tests.
 *
 * Pest serves the application from inside the test process, so tests share the
 * database transaction with the pages they visit. The control panel needs its
 * built assets, so they're linked into Testbench's public directory, and Vite is
 * enabled again after the parent class disables it.
 *
 * Testbench's skeleton gets the package's vendor directory, as `testbench serve` gives it, so the app finds the
 * Composer plugin manifest and the workbench content can install the test plugin.
 *
 * Users get the “Underline links” preference by default, so axe checks link underlines for the people who need them.
 */
class BrowserTestCase extends TestCase
{
    private static bool $vendorSymlinkCreated = false;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$vendorSymlinkCreated = Application::createVendorSymlink(default_skeleton_path(), package_path('vendor'))->make('TESTBENCH_VENDOR_SYMLINK');
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        if (self::$vendorSymlinkCreated) {
            Application::deleteVendorSymlink(default_skeleton_path());
        }

        parent::tearDownAfterClass();
    }

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

        Cms::config()->cp->accessibilityDefaults['underlineLinks'] = true;

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
