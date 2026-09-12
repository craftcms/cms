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
        if (! Schema::hasTable(Table::IMPORT_CONFIGS)) {
            Schema::create(Table::IMPORT_CONFIGS, function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('type');
                $table->string('name');
                $table->string('handle');
                $table->text('description')->nullable();
                $table->mediumText('settings')->nullable();
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->dateTime('dateDeleted')->nullable()->default(null);
                $table->char('uid', 36)->default('0');
            });
        }

        if (! Schema::hasTable(Table::IMPORT_RUNS)) {
            Schema::create(Table::IMPORT_RUNS, function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name');
                $table->string('handle');
                $table->text('description')->nullable();
                $table->text('steps');
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->dateTime('dateDeleted')->nullable()->default(null);
                $table->char('uid', 36)->default('0');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::IMPORT_CONFIGS);
        Schema::dropIfExists(Table::IMPORT_RUNS);
    }
};
