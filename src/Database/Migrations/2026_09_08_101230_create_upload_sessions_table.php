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
        if (Schema::hasTable(Table::UPLOADSESSIONS)) {
            // Sites upgraded from Craft 5.13 or later already have this table. Their uploads used Craft 5’s uploaders,
            // which Craft 6 can’t complete or clean up, so they’re dropped.
            DB::table(Table::UPLOADSESSIONS)->whereIn('uploader', ['direct', 'craft'])->delete();

            return;
        }

        Schema::create(Table::UPLOADSESSIONS, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('owner');
            $table->string('handler');
            $table->string('uploader');
            $table->string('disk');
            $table->string('filename');
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('chunkSize')->default(1);
            $table->jsonb('parameters');
            $table->jsonb('state');
            $table->jsonb('result')->nullable();
            $table->dateTime('expiresAt')->index();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::UPLOADSESSIONS);
    }
};
