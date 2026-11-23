<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::VOLUMES, 'fs')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->renameColumn('fs', 'disk');
            });
        }

        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            foreach ($projectConfig->get(ProjectConfig::PATH_VOLUMES) ?? [] as $uid => $config) {
                if (! is_array($config) || ! array_key_exists('fs', $config)) {
                    continue;
                }

                $config['disk'] ??= $config['fs'];
                unset($config['fs']);

                $projectConfig->set(
                    ProjectConfig::PATH_VOLUMES.".$uid",
                    $config,
                    'Rename the volume `fs` setting to `disk`',
                );
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        $projectConfig->saveModifiedConfigData();
    }

    public function down(): void {}
};
