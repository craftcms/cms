<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Data\Site;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! Schema::hasTable(Table::ELEMENTS)) {
            Context::forgetHidden('craft.info');
            Context::forgetHidden('craft.isInstalled');

            File::cleanDirectory(config_path('craft/project'));
            File::cleanDirectory(storage_path('runtime/compiled_classes'));

            Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();

            $site = new Site(
                name: 'Craft test site',
                handle: 'defaultSite',
                language: 'en-US',
                baseUrl: config('app.url'),
                primary: true,
                hasUrls: true,
            );

            $migration = new Install(
                username: 'craftcms',
                password: 'craftcms2018!!',
                email: 'support@craftcms.com',
                site: $site,
            );

            $migration->up();
        }
    }
}
