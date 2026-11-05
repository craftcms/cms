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
        if (Schema::hasTable(Table::ELEMENTS_MODELS)) {
            return;
        }

        Schema::create(Table::ELEMENTS_MODELS, function (Blueprint $table) {
            $table->integer('elementId')->primary();
            $table->string('modelType');
            $table->string('modelKey');
            $table->unique(['modelType', 'modelKey']);
            $table->foreign('elementId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::ELEMENTS_MODELS);
    }
};
