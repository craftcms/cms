<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\File;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;

class DatabaseSeeder extends Seeder
{
    /**
     * Installs Craft, then fills it with the workbench’s content.
     */
    public function run(): void
    {
        Context::forgetHidden('craft.info');
        Context::forgetHidden('craft.isInstalled');

        File::cleanDirectory(config_path('craft/project'));
        File::cleanDirectory(storage_path('runtime/compiled_classes'));

        Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();

        new Install(
            username: 'craftcms',
            password: 'craftcms2018!!',
            email: 'support@craftcms.com',
            site: new Site([
                'name' => 'Craft test site',
                'handle' => 'defaultSite',
                'language' => 'en-US',
                'baseUrl' => config('app.url'),
                'primary' => true,
                'hasUrls' => true,
            ]),
        )->up();

        app(LaravelMigrations::class)->ensureSessionsTable();

        $this->call(ContentSeeder::class);
    }
}
