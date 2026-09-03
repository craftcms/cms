<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $foreignKey = collect(Schema::getForeignKeys(Table::ELEMENTACTIVITY))
            ->first(fn (array $foreignKey) => in_array('draftId', $foreignKey['columns'], true));

        if (! $foreignKey) {
            return;
        }

        Schema::table(Table::ELEMENTACTIVITY, fn (Blueprint $table) => $table->dropForeign($foreignKey['name']));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(Table::ELEMENTACTIVITY, fn (Blueprint $table) => $table->foreign('draftId')->references('id')->on(Table::DRAFTS)->cascadeOnDelete());
    }
};
