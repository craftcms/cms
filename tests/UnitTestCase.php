<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;
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

        Edition::set(Edition::Pro);
        TemplateMode::set(TemplateMode::Cp);

        Sites::setCurrentSite(new Site);

        app()->setLocale('en-US');

        Cms::config()->timezone('America/Los_Angeles');
        date_default_timezone_set('America/Los_Angeles');

        File::cleanDirectory(config_path('craft/project'));
        File::cleanDirectory(storage_path('runtime/compiled_classes'));
    }
}
