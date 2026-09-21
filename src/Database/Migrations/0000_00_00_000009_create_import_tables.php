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
        if (! Schema::hasTable(Table::IMPORTS)) {
            Schema::create(Table::IMPORTS, function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name');
                $table->string('handle');
                $table->text('description')->nullable();
                $table->mediumText('steps');
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->dateTime('dateDeleted')->nullable()->default(null);
                $table->char('uid', 36)->default('0');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::IMPORTS);
    }
};
