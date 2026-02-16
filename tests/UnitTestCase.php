<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

/**
 * Lightweight test case for unit tests that only need the Laravel
 * service container (no database, no Yii2 bootstrap, no migrations).
 *
 * Use this for tests that don't touch the database or legacy Yii2 code.
 */
class UnitTestCase extends Orchestra
{
    use WithWorkbench;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en-US');

        Config::set('app.timezone', 'America/Los_Angeles');
        date_default_timezone_set('America/Los_Angeles');
    }
}
