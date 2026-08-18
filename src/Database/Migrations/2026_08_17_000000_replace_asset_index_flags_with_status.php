<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::ASSETINDEXDATA, 'status')) {
            return;
        }

        Schema::table(Table::ASSETINDEXDATA, function (Blueprint $table) {
            $table->string('status')->default('pending');
        });

        DB::table(Table::ASSETINDEXDATA)
            ->where('inProgress', true)
            ->update(['status' => 'processing']);

        DB::table(Table::ASSETINDEXDATA)
            ->where('completed', true)
            ->update(['status' => 'indexed']);

        DB::table(Table::ASSETINDEXDATA)
            ->where('isSkipped', true)
            ->update(['status' => 'skipped']);

        Schema::createIndex(Table::ASSETINDEXDATA, ['sessionId', 'status', 'id']);

        Schema::table(Table::ASSETINDEXDATA, function (Blueprint $table) {
            $table->dropColumn(['isSkipped', 'inProgress', 'completed']);
        });
    }

    public function down(): void
    {
        Schema::table(Table::ASSETINDEXDATA, function (Blueprint $table) {
            $table->boolean('isSkipped')->default(false)->nullable();
            $table->boolean('inProgress')->default(false)->nullable();
            $table->boolean('completed')->default(false)->nullable();
        });

        DB::table(Table::ASSETINDEXDATA)
            ->where('status', 'processing')
            ->update(['inProgress' => true]);

        DB::table(Table::ASSETINDEXDATA)
            ->where('status', 'indexed')
            ->update(['completed' => true]);

        DB::table(Table::ASSETINDEXDATA)
            ->whereIn('status', ['skipped', 'missing', 'failed'])
            ->update([
                'completed' => true,
                'isSkipped' => true,
            ]);

        $indexName = Schema::indexName(Table::ASSETINDEXDATA, ['sessionId', 'status', 'id']);

        Schema::table(Table::ASSETINDEXDATA, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
            $table->dropColumn('status');
        });
    }
};
