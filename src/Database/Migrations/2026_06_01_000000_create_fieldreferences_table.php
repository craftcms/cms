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
        if (Schema::hasTable(Table::FIELDREFERENCES)) {
            return;
        }

        Schema::create(Table::FIELDREFERENCES, function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fieldId');
            $table->uuid('fieldInstanceUid');
            $table->integer('sourceId');
            $table->integer('sourceSiteId')->nullable();
            $table->integer('targetId');
        });

        Schema::createIndex(Table::FIELDREFERENCES, ['fieldId', 'fieldInstanceUid', 'sourceId', 'sourceSiteId', 'targetId'], unique: true);
        Schema::createIndex(Table::FIELDREFERENCES, ['fieldId', 'sourceId', 'sourceSiteId']);
        Schema::createIndex(Table::FIELDREFERENCES, ['targetId']);

        Schema::table(Table::FIELDREFERENCES, function (Blueprint $table) {
            $table->foreign('fieldId')->references('id')->on(Table::FIELDS)->cascadeOnDelete();
            $table->foreign('sourceId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete();
            $table->foreign('sourceSiteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::FIELDREFERENCES);
    }
};
