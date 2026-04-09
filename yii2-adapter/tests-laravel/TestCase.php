<?php

namespace CraftCms\Yii2Adapter\Tests;

use CraftCms\Cms\Tests\Support\DatabaseLock;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

class TestCase extends Orchestra
{
    use WithWorkbench;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        DatabaseLock::acquire();
    }
}
