<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Lightweight test case for unit tests that only need the Laravel
 * service container (no database, no Yii2 bootstrap, no migrations).
 *
 * Use this for tests that don't touch the database or legacy Yii2 code.
 */
class UnitTestCase extends Orchestra
{
    use WithWorkbench;
}
