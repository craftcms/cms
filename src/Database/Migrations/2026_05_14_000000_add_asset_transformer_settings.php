<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->removeVolumeTransformerProjectConfig();

        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'transformer')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table) {
                $table->string('transformer')->nullable()->after('fill');
            });
        }

        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'settings')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table) {
                $table->json('settings')->nullable()->after('transformer');
            });
        }

        foreach (['transformSubpath', 'transformFs'] as $column) {
            if (Schema::hasColumn(Table::VOLUMES, $column)) {
                Schema::dropColumns(Table::VOLUMES, $column);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(Table::IMAGETRANSFORMS, 'settings')) {
            Schema::dropColumns(Table::IMAGETRANSFORMS, 'settings');
        }

        if (Schema::hasColumn(Table::IMAGETRANSFORMS, 'transformer')) {
            Schema::dropColumns(Table::IMAGETRANSFORMS, 'transformer');
        }

        if (! Schema::hasColumn(Table::VOLUMES, 'transformFs')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table) {
                $table->string('transformFs')->nullable()->after('subpath');
            });
        }

        if (! Schema::hasColumn(Table::VOLUMES, 'transformSubpath')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table) {
                $table->string('transformSubpath')->nullable()->after('transformFs');
            });
        }
    }

    private function removeVolumeTransformerProjectConfig(): void
    {
        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            $volumeConfigs = $projectConfig->get(ProjectConfig::PATH_VOLUMES) ?? [];

            if (! is_array($volumeConfigs)) {
                return;
            }

            foreach ($volumeConfigs as &$volumeConfig) {
                if (is_array($volumeConfig)) {
                    unset($volumeConfig['transformFs'], $volumeConfig['transformSubpath']);
                }
            }

            unset($volumeConfig);

            $projectConfig->set(ProjectConfig::PATH_VOLUMES, $volumeConfigs, 'Remove volume asset transformer settings');
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }
    }
};
