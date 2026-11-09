<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'parameters')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table): void {
                $table->json('parameters')->nullable()->after('upscale');
            });
        }

        // Craft 5's transformFs and transformSubpath stay in project config, where 2026_09_01_000000_remove_craft_filesystems
        // checks that each volume's disk has a matching asset_transformer.
        if (Schema::hasColumn(Table::VOLUMES, 'transformFs')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->dropColumn('transformFs');
            });
        }

        if (Schema::hasColumn(Table::VOLUMES, 'transformSubpath')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->dropColumn('transformSubpath');
            });
        }
    }
};
