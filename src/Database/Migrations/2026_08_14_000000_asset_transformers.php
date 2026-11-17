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

        // Craft 5's transformFs and transformSubpath stay in project config, where the Yii adapter's deprecated volume
        // transform filesystem methods read them.
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
