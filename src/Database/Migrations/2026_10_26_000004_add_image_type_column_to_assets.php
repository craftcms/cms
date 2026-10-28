<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Expressions\JsonExtract;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::ASSETS, 'imageType')) {
            return;
        }

        // Generated from analysis, so images can be filtered by it. Postgres can only add stored generated columns.
        Schema::table(Table::ASSETS, function (Blueprint $table): void {
            $imageType = $table->string('imageType', 20)->nullable();
            DB::getDriverName() === 'pgsql'
                ? $imageType->storedAs(new JsonExtract('analysis', 'type'))
                : $imageType->virtualAs(new JsonExtract('analysis', 'type'));
        });
        Schema::createIndex(Table::ASSETS, ['imageType']);
    }

    public function down(): void {}
};
