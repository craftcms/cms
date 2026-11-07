<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('removes the Anchors plugin install unless its package is still installed, and leaves other plugins alone', function (bool $packageInstalled) {
    $projectConfig = app(ProjectConfig::class);
    $plugins = app(Plugins::class);
    new ReflectionProperty($plugins, 'composerPluginInfo')->setValue($plugins, $packageInstalled ? [
        'anchors' => ['class' => 'craft\\anchors\\Plugin', 'handle' => 'anchors', 'name' => 'Anchors', 'packageName' => 'craftcms/anchors', 'version' => '3.5.0'],
    ] : []);

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

    expect(DB::table(Table::PLUGINS)->orderBy('handle')->pluck('handle')->all())->toBe($packageInstalled ? ['anchors', 'other'] : ['other'])
        ->and($projectConfig->get(ProjectConfig::PATH_PLUGINS.'.anchors') !== null)->toBe($packageInstalled)
        ->and($projectConfig->get(ProjectConfig::PATH_PLUGINS.'.other'))->not->toBeNull();
})->with([
    'package removed' => false,
    'package still installed' => true,
]);
