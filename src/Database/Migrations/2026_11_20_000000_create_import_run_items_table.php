<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(Table::IMPORT_RUN_ITEMS)) {
            return;
        }

        Schema::create(Table::IMPORT_RUN_ITEMS, function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('runId');
            $table->char('stepUid', 36);
            $table->unsignedInteger('item');
            $table->string('outcome', 20);
            // not a foreign key, as a failed new item's element was never saved
            $table->integer('elementId')->nullable();
        });
        Schema::createIndex(Table::IMPORT_RUN_ITEMS, ['runId', 'stepUid', 'outcome', 'item']);
        Schema::table(Table::IMPORT_RUN_ITEMS, function (Blueprint $table) {
            $table->foreign('runId')->references('id')->on(Table::IMPORT_RUNS)->cascadeOnDelete();
        });
    }

    public function down(): void {}
};
