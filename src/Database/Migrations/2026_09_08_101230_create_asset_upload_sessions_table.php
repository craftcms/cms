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
        if (Schema::hasTable(Table::ASSETUPLOADSESSIONS)) {
            return;
        }

        Schema::create(Table::ASSETUPLOADSESSIONS, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('owner');
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
        Schema::dropIfExists(Table::ASSETUPLOADSESSIONS);
    }
};
