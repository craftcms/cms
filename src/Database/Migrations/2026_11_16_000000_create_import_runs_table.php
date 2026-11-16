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
        if (Schema::hasTable(Table::IMPORT_RUNS)) {
            return;
        }

        Schema::create(Table::IMPORT_RUNS, function (Blueprint $table) {
            $table->integer('id', true);
            $table->char('planUid', 36)->nullable();
            $table->string('planHandle');
            $table->string('status', 20);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->integer('userId')->nullable();
            $table->string('trigger', 20)->nullable();
            $table->longText('steps');
            $table->dateTime('dateStarted')->nullable();
            $table->dateTime('dateFinished')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->unique();
        });
        Schema::createIndex(Table::IMPORT_RUNS, ['planUid', 'planHandle', 'dateCreated']);
        Schema::table(Table::IMPORT_RUNS, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->nullOnDelete());
    }

    public function down(): void {}
};
