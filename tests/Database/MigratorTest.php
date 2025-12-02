<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

it('has tracks', function () {
    $migrator = resolve(Migrator::class);

    $migrator->track('content')->getRepository()->log('track_content', 1);
    $migrator->track('craft')->getRepository()->log('track_craft', 1);
    $migrator->track('plugin:commerce')->getRepository()->log('track_plugin', 1);

    expect(DB::table(Table::MIGRATIONS)->whereNull('track')->latest('id')->value('migration'))->toBe('track_content');
    expect(DB::table(Table::MIGRATIONS)->where('track', 'craft')->latest('id')->value('migration'))->toBe('track_craft');
    expect(DB::table(Table::MIGRATIONS)->where('track', 'plugin:commerce')->latest('id')->value('migration'))->toBe('track_plugin');
});
