<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('removes the Anchors plugin install and leaves other plugins alone', function () {
    $projectConfig = app(ProjectConfig::class);

    foreach (['anchors', 'other'] as $handle) {
        DB::table(Table::PLUGINS)->insert([
            'handle' => $handle,
            'version' => '3.3.0',
            'schemaVersion' => '1.0.0',
            'installDate' => now(),
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);
        $projectConfig->set(ProjectConfig::PATH_PLUGINS.".$handle", ['edition' => 'standard', 'enabled' => true, 'schemaVersion' => '1.0.0']);
    }

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_07_000000_remove_anchors_plugin.php';
    $migration->up();

    expect(DB::table(Table::PLUGINS)->pluck('handle')->all())->toBe(['other'])
        ->and($projectConfig->get(ProjectConfig::PATH_PLUGINS.'.anchors'))->toBeNull()
        ->and($projectConfig->get(ProjectConfig::PATH_PLUGINS.'.other'))->not->toBeNull();
});
