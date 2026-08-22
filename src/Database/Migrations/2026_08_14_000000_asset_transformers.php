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
        if (! Schema::hasColumn(Table::VOLUMES, 'assetTransformer')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->string('assetTransformer')->nullable()->after('subpath');
            });
        }

        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'operations')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table): void {
                $table->json('operations')->nullable()->after('upscale');
            });
        }

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
